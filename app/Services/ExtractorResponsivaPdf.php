<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lee una carta responsiva (PDF o foto; formato vigente o versiones viejas,
 * digitales, escaneadas o llenadas a mano) con IA y devuelve los datos que
 * encontró. Solo extrae lo que está escrito: lo demás queda en null.
 *
 * Proveedores: Gemini (tiene nivel gratuito) o Claude, según AI_PROVIDER.
 */
class ExtractorResponsivaPdf
{
    public const TIPO_COMPUTO = 'computo';

    public const TIPO_CELULAR = 'celular';

    public const TIPO_DISPOSITIVOS = 'dispositivos';

    /**
     * @return array<string, mixed>
     */
    public function extraer(string $rutaArchivo, string $tipo): array
    {
        if (! is_file($rutaArchivo)) {
            throw new RuntimeException('No se encontró el archivo subido.');
        }

        $mime = mime_content_type($rutaArchivo) ?: 'application/pdf';
        $datos = base64_encode(file_get_contents($rutaArchivo));

        return config('services.ia.proveedor') === 'anthropic'
            ? $this->conClaude($datos, $mime, $tipo)
            : $this->conGemini($datos, $mime, $tipo);
    }

    /**
     * Para un PDF con VARIAS responsivas (una o más páginas cada una): devuelve un
     * arreglo con un registro por responsiva detectada.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extraerVarios(string $rutaArchivo, string $tipo): array
    {
        if (! is_file($rutaArchivo)) {
            throw new RuntimeException('No se encontró el archivo subido.');
        }

        $mime = mime_content_type($rutaArchivo) ?: 'application/pdf';
        $datos = base64_encode(file_get_contents($rutaArchivo));

        $resultado = config('services.ia.proveedor') === 'anthropic'
            ? $this->conClaude($datos, $mime, $tipo, varios: true)
            : $this->conGemini($datos, $mime, $tipo, varios: true);

        $lista = array_is_list($resultado) ? $resultado : ($resultado['responsivas'] ?? []);

        return array_values(array_filter($lista, 'is_array'));
    }

    /**
     * @return array<string, mixed>
     */
    private function conGemini(string $datos, string $mime, string $tipo, bool $varios = false): array
    {
        $clave = config('services.gemini.key');

        if (blank($clave)) {
            throw new RuntimeException('Falta configurar GEMINI_API_KEY en el archivo .env.');
        }

        $modelos = array_values(array_unique(array_filter([
            config('services.gemini.model'),
            config('services.gemini.fallback_model'),
        ])));

        $payload = [
            'contents' => [[
                'parts' => [
                    ['inline_data' => ['mime_type' => $mime, 'data' => $datos]],
                    ['text' => $this->instrucciones($tipo, $varios) . "\n\n" . $this->formatoJson($tipo, $varios)],
                ],
            ]],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0,
            ],
        ];

        // Si un modelo está saturado (429/503) tras sus reintentos, se prueba con el siguiente.
        foreach ($modelos as $modelo) {
            for ($intento = 1; $intento <= 2; $intento++) {
                $respuesta = Http::withHeaders(['x-goog-api-key' => $clave])
                    ->acceptJson()
                    ->timeout($varios ? 420 : 180)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent", $payload);

                if (! in_array($respuesta->status(), [429, 503], true)) {
                    break 2;
                }

                if ($intento < 2) {
                    usleep(1_500_000);
                }
            }
        }

        if ($respuesta->status() === 429) {
            throw new RuntimeException('Se alcanzó el límite gratuito de la IA. Espera un minuto e inténtalo de nuevo.');
        }

        if ($respuesta->status() === 503) {
            throw new RuntimeException('La IA de Google está saturada en este momento. Inténtalo de nuevo en unos segundos.');
        }

        if ($respuesta->failed()) {
            throw new RuntimeException(
                'La API respondió ' . $respuesta->status() . ': ' . ($respuesta->json('error.message') ?? 'error desconocido')
            );
        }

        $texto = $respuesta->json('candidates.0.content.parts.0.text');

        if (! is_string($texto)) {
            throw new RuntimeException('La IA no devolvió datos legibles del documento.');
        }

        // Por si el modelo envuelve el JSON en ```json ... ```
        $json = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $texto)), true);

        if (! is_array($json)) {
            throw new RuntimeException('La IA no devolvió datos legibles del documento.');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function conClaude(string $datos, string $mime, string $tipo, bool $varios = false): array
    {
        $clave = config('services.anthropic.key');

        if (blank($clave)) {
            throw new RuntimeException('Falta configurar ANTHROPIC_API_KEY en el archivo .env.');
        }

        $archivo = [
            'type' => $mime === 'application/pdf' ? 'document' : 'image',
            'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $datos],
        ];

        $respuesta = Http::withHeaders([
            'x-api-key' => $clave,
            'anthropic-version' => '2023-06-01',
        ])
            ->acceptJson()
            ->timeout($varios ? 420 : 180)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => $varios ? 16000 : 2000,
                'tools' => [[
                    'name' => 'registrar_datos',
                    'description' => $varios
                        ? 'Registra los datos de cada responsiva que aparece en el documento.'
                        : 'Registra los datos que aparecen escritos en la responsiva.',
                    'input_schema' => $varios
                        ? ['type' => 'object', 'properties' => ['responsivas' => ['type' => 'array', 'items' => $this->esquema($tipo)]], 'required' => ['responsivas']]
                        : $this->esquema($tipo),
                ]],
                'tool_choice' => ['type' => 'tool', 'name' => 'registrar_datos'],
                'messages' => [[
                    'role' => 'user',
                    'content' => [$archivo, ['type' => 'text', 'text' => $this->instrucciones($tipo, $varios)]],
                ]],
            ]);

        if ($respuesta->failed()) {
            throw new RuntimeException(
                'La API respondió ' . $respuesta->status() . ': ' . ($respuesta->json('error.message') ?? 'error desconocido')
            );
        }

        foreach ($respuesta->json('content', []) as $bloque) {
            if (($bloque['type'] ?? null) === 'tool_use') {
                return $bloque['input'] ?? [];
            }
        }

        throw new RuntimeException('La IA no devolvió datos legibles del documento.');
    }

    private function instrucciones(string $tipo, bool $varios = false): string
    {
        $documento = match ($tipo) {
            self::TIPO_CELULAR => 'una carta responsiva de resguardo de equipo móvil/celular',
            self::TIPO_DISPOSITIVOS => 'un formato de entrega o devolución de un dispositivo externo o accesorio (disco duro, cable, monitor, mouse, teclado, tablet, impresora, punto de venta, etc.)',
            default => 'un formato de entrega de equipo de cómputo',
        };

        $encabezado = $varios
            ? "Este PDF reúne VARIAS responsivas de Grupo Hunan (cada una es {$documento} y puede ocupar una o más páginas). Devuelve un registro por cada responsiva distinta, en el orden en que aparecen; no mezcles datos de una responsiva con los de otra y no repitas una misma responsiva. Ignora las páginas que no sean una responsiva (portadas, hojas en blanco, anexos)."
            : "Este documento es {$documento} de Grupo Hunan.";

        return <<<TXT
{$encabezado} Puede ser la versión actual o una anterior, y puede ser un PDF digital, un escaneo o una foto (posiblemente inclinada), impresa o llenada a mano.

Extrae únicamente los datos del formulario que estén realmente escritos o marcados. Reglas:
- Si un campo está vacío, en blanco o ilegible, devuélvelo como null. Nunca inventes ni deduzcas valores.
- Ignora el texto fijo del formato (cláusulas, políticas, encabezados, firmas, nombres de quienes autorizan).
- Fechas en formato YYYY-MM-DD.
- Copia los valores tal como están escritos (sin corregir mayúsculas ni ortografía), salvo los campos con opciones cerradas.
- Casillas: marca solo las que tengan una X o palomita visible.
- El formato viejo (FR-FEI-IT-001) usa otros nombres: "Unidad" = sucursal, "SN" = número de serie, "Disco Duro" = almacenamiento, "STATUS DE LA RESPONSIVA" (ALTA/BAJA/CAMBIOS) = tipo de movimiento, y casillas para el tipo de equipo (Escritorio, Laptop, Ipod/Ipad/Tablet, Impresora) y para el sistema operativo (MAC/Android, Windows 10, Windows 11).
TXT;
    }

    /**
     * @return array<string, mixed>
     */
    private function esquema(string $tipo): array
    {
        return match ($tipo) {
            self::TIPO_CELULAR => $this->esquemaCelular(),
            self::TIPO_DISPOSITIVOS => $this->esquemaDispositivos(),
            default => $this->esquemaComputo(),
        };
    }

    /**
     * Lista de claves esperadas, para los modelos que no usan esquema formal.
     */
    private function formatoJson(string $tipo, bool $varios = false): string
    {
        $lineas = [];

        foreach ($this->esquema($tipo)['properties'] as $clave => $definicion) {
            $detalle = ($definicion['type'] ?? null) === 'array'
                ? 'lista de textos'
                : (isset($definicion['enum'])
                    ? 'uno de: ' . implode(', ', array_filter($definicion['enum'])) . ' (o null)'
                    : 'texto (o null)');
            $nota = isset($definicion['description']) ? ' — ' . $definicion['description'] : '';
            $lineas[] = "- {$clave}: {$detalle}{$nota}";
        }

        if ($varios) {
            return "Responde SOLO con un objeto JSON con la clave \"responsivas\": una lista con un objeto por cada responsiva del documento. Cada objeto tiene exactamente estas claves (null si el dato no aparece):\n" . implode("\n", $lineas);
        }

        return "Responde SOLO con un objeto JSON con exactamente estas claves (null si el dato no aparece):\n" . implode("\n", $lineas);
    }

    /**
     * @return array<string, mixed>
     */
    private function esquemaComputo(): array
    {
        $texto = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'tipo_movimiento' => [
                    'type' => ['string', 'null'],
                    'enum' => ['alta', 'baja', 'cambio_equipo', 'reasignacion', 'mantenimiento', 'prestamo_temporal', null],
                    'description' => 'Casilla marcada en "Tipo de movimiento" o "Status" (Alta/Entrega inicial=alta, Baja/Devolución=baja, Cambios=cambio_equipo).',
                ],
                'fecha_movimiento' => $texto + ['description' => 'Fecha de movimiento, YYYY-MM-DD.'],
                'nombre_completo' => $texto,
                'area' => $texto + ['description' => 'Área / Departamento.'],
                'sucursal' => $texto + ['description' => 'Unidad / Sucursal (tal como está escrita, aunque tenga errores de dedo).'],
                'puesto' => $texto,
                'extension' => $texto,
                'correo_corporativo' => $texto,
                'telefono_directo' => $texto,
                'tipo_equipo' => [
                    'type' => ['string', 'null'],
                    'enum' => ['laptop', 'desktop', 'all_in_one', 'workstation', 'mini_pc', null],
                    'description' => 'Tipo de equipo, escrito o por casilla marcada (Escritorio/PC=desktop). Null si es impresora, tablet u otro no listado.',
                ],
                'marca' => $texto,
                'modelo' => $texto,
                'numero_serie' => $texto,
                'sistema_operativo' => $texto + ['description' => 'Texto escrito o casilla marcada, ej. "Windows 11" (no incluyas 32/64 bits).'],
                'ram' => $texto,
                'procesador' => $texto,
                'almacenamiento' => $texto,
                'observaciones' => $texto,
                'accesorios' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Accesorios con casilla marcada (y el texto de "Otro" si lo hay).',
                ],
            ],
            'required' => ['nombre_completo', 'marca', 'modelo', 'numero_serie'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function esquemaCelular(): array
    {
        $texto = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'fecha' => $texto + ['description' => 'Fecha de la carta ("Ciudad de México, a ..."), YYYY-MM-DD.'],
                'nombre_colaborador' => $texto,
                'puesto_area' => $texto + ['description' => 'Valor tal cual del campo "Puesto / Área".'],
                'telefono' => $texto + ['description' => 'Teléfono / número asignado.'],
                'curp' => $texto,
                'marca' => $texto,
                'modelo' => $texto,
                'imei' => $texto,
                'iccid' => $texto + ['description' => 'ICCID / SIM.'],
                'sucursal' => $texto + ['description' => 'Sucursal / Centro de costo.'],
                'tipo_equipo' => [
                    'type' => ['string', 'null'],
                    'enum' => ['celular', 'tablet', 'ipad', 'otro', null],
                ],
                'observaciones' => $texto,
            ],
            'required' => ['nombre_colaborador', 'marca', 'modelo', 'imei'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function esquemaDispositivos(): array
    {
        $texto = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'tipo_movimiento' => [
                    'type' => ['string', 'null'],
                    'enum' => ['entrega', 'devolucion', null],
                    'description' => 'Entrega (se le da el dispositivo a la persona) o devolucion (lo regresa).',
                ],
                'fecha' => $texto + ['description' => 'Fecha de la entrega o devolución, YYYY-MM-DD.'],
                'nombre_completo' => $texto + ['description' => 'Persona que recibe o devuelve el dispositivo.'],
                'correo' => $texto + ['description' => 'Correo electrónico de la persona.'],
                'area' => $texto + ['description' => 'Área / Departamento.'],
                'sucursal' => $texto + ['description' => 'Unidad / Sucursal (tal como está escrita).'],
                'tipo_dispositivo' => $texto + ['description' => 'Qué tipo de dispositivo es (disco duro, cable, monitor, mouse, teclado, tablet, impresora, punto de venta…).'],
                'descripcion' => $texto + ['description' => 'Descripción del dispositivo tal como está escrita.'],
                'marca' => $texto,
                'modelo' => $texto,
                'numero_serie' => $texto,
                'observaciones' => $texto,
            ],
            'required' => ['nombre_completo', 'descripcion'],
        ];
    }
}
