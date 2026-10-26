<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Marca;
use App\Models\Sucursal;
use App\Models\TipoDispositivo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Lee una carta responsiva (PDF o foto; formato vigente o versiones viejas,
 * digitales, escaneadas o llenadas a mano) con IA y devuelve los datos que
 * encontró. Solo extrae lo que está escrito: lo demás queda en null.
 *
 * A la IA se le pasan los catálogos del sistema (sucursales, áreas, marcas…)
 * para que escriba los nombres tal como están dados de alta.
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
     *
     * @throws ErrorTemporalIA cuando la IA está saturada o no devolvió datos legibles
     */
    public function extraer(string $rutaArchivo, string $tipo): array
    {
        [$datos, $mime] = $this->leerArchivo($rutaArchivo);

        return $this->consultar(
            $datos,
            $mime,
            $this->instrucciones($tipo),
            $this->formatoJson($this->esquema($tipo)),
            $this->esquema($tipo),
            4096,
            60,
        );
    }

    /**
     * Para un PDF con VARIAS responsivas (una o más páginas cada una): devuelve un
     * arreglo con un registro por responsiva detectada. Con $desde y $hasta solo se
     * leen las responsivas que EMPIEZAN en ese rango de páginas (numeradas desde 1),
     * para procesar PDF grandes por bloques.
     *
     * @return array<int, array<string, mixed>>
     */
    public function extraerVarios(string $rutaArchivo, string $tipo, ?int $desde = null, ?int $hasta = null): array
    {
        [$datos, $mime] = $this->leerArchivo($rutaArchivo);

        $esquema = [
            'type' => 'object',
            'properties' => ['responsivas' => ['type' => 'array', 'items' => $this->esquema($tipo)]],
            'required' => ['responsivas'],
        ];

        $resultado = $this->consultar(
            $datos,
            $mime,
            $this->instrucciones($tipo, true, $desde, $hasta),
            $this->formatoJson($this->esquema($tipo), true),
            $esquema,
            32768,
            120,
        );

        $lista = array_is_list($resultado) ? $resultado : ($resultado['responsivas'] ?? []);

        return array_values(array_filter($lista, 'is_array'));
    }

    /**
     * Número de páginas de un PDF, o null si no se pudo saber. Primero se cuenta
     * en el propio archivo; si el PDF las guarda comprimidas, se le pregunta a la IA.
     */
    public function contarPaginas(string $rutaArchivo): ?int
    {
        if (! is_file($rutaArchivo)) {
            return null;
        }

        $paginas = preg_match_all('#/Type\s*/Page(?![A-Za-z])#', (string) file_get_contents($rutaArchivo));

        if ($paginas > 0) {
            return $paginas;
        }

        try {
            [$datos, $mime] = $this->leerArchivo($rutaArchivo);

            $respuesta = $this->consultar(
                $datos,
                $mime,
                'Cuenta cuántas páginas tiene este documento PDF.',
                'Responde SOLO con un objeto JSON con la clave "paginas": un número entero.',
                ['type' => 'object', 'properties' => ['paginas' => ['type' => 'integer']], 'required' => ['paginas']],
                100,
                45,
            );

            $paginas = (int) ($respuesta['paginas'] ?? 0);

            return $paginas > 0 ? $paginas : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: string} contenido en base64 y tipo MIME
     */
    private function leerArchivo(string $rutaArchivo): array
    {
        if (! is_file($rutaArchivo)) {
            throw new RuntimeException('No se encontró el archivo subido.');
        }

        return [base64_encode(file_get_contents($rutaArchivo)), mime_content_type($rutaArchivo) ?: 'application/pdf'];
    }

    /**
     * @param  array<string, mixed>  $esquema
     * @return array<string, mixed>
     */
    private function consultar(string $datos, string $mime, string $instrucciones, string $formato, array $esquema, int $maxSalida, int $timeout): array
    {
        return config('services.ia.proveedor') === 'anthropic'
            ? $this->conClaude($datos, $mime, $instrucciones, $esquema, $maxSalida, $timeout)
            : $this->conGemini($datos, $mime, $instrucciones, $formato, $esquema, $maxSalida, $timeout);
    }

    /**
     * @param  array<string, mixed>  $esquema
     * @return array<string, mixed>
     */
    private function conGemini(string $datos, string $mime, string $instrucciones, string $formato, array $esquema, int $maxSalida, int $timeout): array
    {
        $clave = config('services.gemini.key');

        if (blank($clave)) {
            throw new RuntimeException('Falta configurar GEMINI_API_KEY en el archivo .env.');
        }

        $modelos = array_values(array_unique(array_filter([
            config('services.gemini.model'),
            config('services.gemini.fallback_model'),
        ])));

        $presupuesto = config('services.gemini.thinking_budget');

        // Con $extras la respuesta se fuerza al esquema y se limita el "razonamiento"
        // del modelo (más rápido y barato). Si un modelo no los acepta, se reintenta sin ellos.
        $construir = function (bool $extras) use ($datos, $mime, $instrucciones, $formato, $esquema, $maxSalida, $presupuesto) {
            $config = [
                'responseMimeType' => 'application/json',
                'temperature' => 0,
                'maxOutputTokens' => $maxSalida,
            ];

            if ($extras) {
                $config['responseSchema'] = $this->esquemaGemini($esquema);

                if ($presupuesto !== null && $presupuesto !== '') {
                    $config['thinkingConfig'] = ['thinkingBudget' => (int) $presupuesto];
                }
            }

            return [
                'contents' => [[
                    'parts' => [
                        ['inline_data' => ['mime_type' => $mime, 'data' => $datos]],
                        ['text' => $instrucciones . "\n\n" . $formato],
                    ],
                ]],
                'generationConfig' => $config,
            ];
        };

        $extras = true;

        $enviar = function (string $modelo) use ($clave, $timeout, $construir, &$extras) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent";

            $respuesta = Http::withHeaders(['x-goog-api-key' => $clave])->acceptJson()->timeout($timeout)->post($url, $construir($extras));

            if ($extras && $respuesta->status() === 400 && preg_match('/thinking|schema|nullable|enum|ordering/i', (string) $respuesta->json('error.message'))) {
                $extras = false;
                $respuesta = Http::withHeaders(['x-goog-api-key' => $clave])->acceptJson()->timeout($timeout)->post($url, $construir(false));
            }

            return $respuesta;
        };

        // Si un modelo está saturado (429/503) tras sus reintentos, se prueba con el siguiente.
        $respuesta = null;
        $sinConexion = false;

        foreach ($modelos as $modelo) {
            for ($intento = 1; $intento <= 2; $intento++) {
                try {
                    $respuesta = $enviar($modelo);
                    $sinConexion = false;
                } catch (ConnectionException) {
                    // Sin respuesta a tiempo (la API a veces se queda colgada): se pasa al siguiente modelo.
                    $sinConexion = true;

                    break;
                }

                if (! in_array($respuesta->status(), [429, 503], true)) {
                    break 2;
                }

                if ($intento < 2) {
                    usleep(1_500_000);
                }
            }
        }

        if ($sinConexion || $respuesta === null) {
            throw new ErrorTemporalIA('La IA no respondió a tiempo. Inténtalo de nuevo en unos segundos.');
        }

        if ($respuesta->status() === 429) {
            throw new ErrorTemporalIA('Se alcanzó el límite gratuito de la IA. Espera un minuto e inténtalo de nuevo.');
        }

        if ($respuesta->status() === 503) {
            throw new ErrorTemporalIA('La IA de Google está saturada en este momento. Inténtalo de nuevo en unos segundos.');
        }

        if ($respuesta->failed()) {
            throw new RuntimeException(
                'La API respondió ' . $respuesta->status() . ': ' . ($respuesta->json('error.message') ?? 'error desconocido')
            );
        }

        if ($respuesta->json('candidates.0.finishReason') === 'MAX_TOKENS') {
            throw new RuntimeException('La respuesta de la IA se cortó por ser demasiado larga. Divide el documento en partes más pequeñas.');
        }

        $texto = collect($respuesta->json('candidates.0.content.parts', []))
            ->reject(fn ($parte) => ! empty($parte['thought']))
            ->pluck('text')
            ->filter(fn ($t) => is_string($t))
            ->implode('');

        // Por si el modelo envuelve el JSON en ```json ... ```
        $json = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $texto)), true);

        if ($texto === '' || ! is_array($json)) {
            throw new ErrorTemporalIA('La IA no devolvió datos legibles del documento.');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $esquema
     * @return array<string, mixed>
     */
    private function conClaude(string $datos, string $mime, string $instrucciones, array $esquema, int $maxSalida, int $timeout): array
    {
        $clave = config('services.anthropic.key');

        if (blank($clave)) {
            throw new RuntimeException('Falta configurar ANTHROPIC_API_KEY en el archivo .env.');
        }

        $archivo = [
            'type' => $mime === 'application/pdf' ? 'document' : 'image',
            'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $datos],
        ];

        try {
            $respuesta = Http::withHeaders([
                'x-api-key' => $clave,
                'anthropic-version' => '2023-06-01',
            ])
                ->acceptJson()
                ->timeout($timeout)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model'),
                    'max_tokens' => min($maxSalida, 16000),
                    'tools' => [[
                        'name' => 'registrar_datos',
                        'description' => 'Registra los datos que aparecen escritos en el documento.',
                        'input_schema' => $esquema,
                    ]],
                    'tool_choice' => ['type' => 'tool', 'name' => 'registrar_datos'],
                    'messages' => [[
                        'role' => 'user',
                        'content' => [$archivo, ['type' => 'text', 'text' => $instrucciones]],
                    ]],
                ]);
        } catch (ConnectionException) {
            throw new ErrorTemporalIA('La IA no respondió a tiempo. Inténtalo de nuevo en unos segundos.');
        }

        if (in_array($respuesta->status(), [429, 503, 529], true)) {
            throw new ErrorTemporalIA('La IA está saturada en este momento. Inténtalo de nuevo en unos segundos.');
        }

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

        throw new ErrorTemporalIA('La IA no devolvió datos legibles del documento.');
    }

    private function instrucciones(string $tipo, bool $varios = false, ?int $desde = null, ?int $hasta = null): string
    {
        $documento = match ($tipo) {
            self::TIPO_CELULAR => 'una carta responsiva de resguardo de equipo móvil/celular',
            self::TIPO_DISPOSITIVOS => 'un formato de entrega o devolución de un dispositivo externo o accesorio (disco duro, cable, monitor, mouse, teclado, tablet, impresora, punto de venta, etc.)',
            default => 'un formato de entrega de equipo de cómputo',
        };

        $encabezado = $varios
            ? "Este PDF reúne VARIAS responsivas de Grupo Hunan (cada una es {$documento} y puede ocupar una o más páginas). Devuelve un registro por cada responsiva distinta, en el orden en que aparecen; no mezcles datos de una responsiva con los de otra y no repitas una misma responsiva. Ignora las páginas que no sean una responsiva (portadas, hojas en blanco, anexos)."
            : "Este documento es {$documento} de Grupo Hunan.";

        if ($varios && $desde !== null && $hasta !== null) {
            $encabezado .= " Procesa ÚNICAMENTE las responsivas que EMPIEZAN en las páginas {$desde} a {$hasta} del PDF (páginas numeradas desde 1). Si una de ellas continúa después de la página {$hasta}, léela completa. Ignora las que empezaron antes de la página {$desde}, aunque su última página caiga dentro de este rango. Si en ese rango no empieza ninguna responsiva, devuelve una lista vacía.";
        }

        $catalogos = $this->catalogos($tipo);

        return <<<TXT
{$encabezado} Puede ser la versión actual o una anterior, y puede ser un PDF digital, un escaneo o una foto (posiblemente inclinada), impresa o llenada a mano.

Extrae únicamente los datos del formulario que estén realmente escritos o marcados. Reglas:
- Si un campo está vacío, en blanco o ilegible, devuélvelo como null. Nunca inventes ni deduzcas valores.
- Ignora el texto fijo del formato (cláusulas, políticas, encabezados, firmas, nombres de quienes autorizan).
- Fechas en formato YYYY-MM-DD.
- Copia los valores tal como están escritos (sin corregir mayúsculas ni ortografía), salvo los campos con opciones cerradas y los catálogos de abajo.
- Casillas: marca solo las que tengan una X o palomita visible.
- En números de serie, IMEI y teléfonos revisa con cuidado los caracteres que se parecen (0 y O, 1 e I, 5 y S, 8 y B). Los IMEI tienen 15 dígitos y los teléfonos 10.
- El formato viejo (FR-FEI-IT-001) usa otros nombres: "Unidad" = sucursal, "SN" = número de serie, "Disco Duro" = almacenamiento, "STATUS DE LA RESPONSIVA" (ALTA/BAJA/CAMBIOS) = tipo de movimiento, y casillas para el tipo de equipo (Escritorio, Laptop, Ipod/Ipad/Tablet, Impresora) y para el sistema operativo (MAC/Android, Windows 10, Windows 11).
{$catalogos}
TXT;
    }

    /**
     * Catálogos del sistema para que la IA escriba los nombres tal como están dados
     * de alta. Si el texto del documento no corresponde a ninguno, lo deja como está.
     */
    private function catalogos(string $tipo): string
    {
        $listas = [
            'Sucursal' => Sucursal::where('activo', true)->orderBy('nombre')->pluck('nombre'),
        ];

        if ($tipo !== self::TIPO_CELULAR) {
            $listas['Área'] = Area::where('activo', true)->orderBy('nombre')->pluck('nombre');
        }

        $listas['Marca'] = match ($tipo) {
            self::TIPO_CELULAR => Marca::activas()->deCelular()->orderBy('nombre')->pluck('nombre'),
            self::TIPO_DISPOSITIVOS => Marca::activas()->orderBy('nombre')->pluck('nombre'),
            default => Marca::activas()->deComputo()->orderBy('nombre')->pluck('nombre'),
        };

        if ($tipo === self::TIPO_DISPOSITIVOS) {
            $listas['Tipo de dispositivo'] = TipoDispositivo::where('activo', true)->orderBy('nombre')->pluck('nombre');
        }

        $lineas = [];

        foreach ($listas as $etiqueta => $nombres) {
            if ($nombres->isNotEmpty()) {
                $lineas[] = "- {$etiqueta}: " . $nombres->implode(' | ');
            }
        }

        if ($lineas === []) {
            return '';
        }

        return "\nCatálogos del sistema. Si lo escrito en el documento corresponde claramente a una de estas opciones (aunque tenga errores de dedo, abreviaturas o mayúsculas distintas), devuelve EXACTAMENTE el nombre de la lista. Si no corresponde a ninguna, copia el texto tal como está escrito:\n" . implode("\n", $lineas);
    }

    /**
     * Lista de claves esperadas, para los modelos que no usan esquema formal.
     *
     * @param  array<string, mixed>  $esquema
     */
    private function formatoJson(array $esquema, bool $varios = false): string
    {
        $lineas = [];

        foreach ($esquema['properties'] as $clave => $definicion) {
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
     * El esquema JSON de Claude convertido al subconjunto de OpenAPI que usa Gemini
     * (tipos en mayúsculas, "nullable" en lugar de listas de tipos).
     *
     * @param  array<string, mixed>  $esquema
     * @return array<string, mixed>
     */
    private function esquemaGemini(array $esquema): array
    {
        $tipo = $esquema['type'] ?? 'string';
        $nullable = false;

        if (is_array($tipo)) {
            $nullable = in_array('null', $tipo, true);
            $tipo = array_values(array_diff($tipo, ['null']))[0] ?? 'string';
        }

        $salida = ['type' => strtoupper($tipo)];

        if ($nullable) {
            $salida['nullable'] = true;
        }

        if (isset($esquema['description'])) {
            $salida['description'] = $esquema['description'];
        }

        if (isset($esquema['enum'])) {
            $salida['enum'] = array_values(array_filter($esquema['enum'], fn ($v) => $v !== null));
            $salida['format'] = 'enum';
        }

        if (isset($esquema['properties'])) {
            $salida['properties'] = array_map(fn ($p) => $this->esquemaGemini($p), $esquema['properties']);
            $salida['propertyOrdering'] = array_keys($esquema['properties']);
        }

        if (isset($esquema['required'])) {
            $salida['required'] = $esquema['required'];
        }

        if (isset($esquema['items'])) {
            $salida['items'] = $this->esquemaGemini($esquema['items']);
        }

        return $salida;
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
                'sucursal' => $texto + ['description' => 'Unidad / Sucursal.'],
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
                'sucursal' => $texto + ['description' => 'Unidad / Sucursal.'],
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
