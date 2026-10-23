<?php

namespace App\Services;

use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularForm;
use App\Filament\Resources\EquipoComputos\Schemas\EquipoComputoForm;
use App\Models\EquipoCelular;
use App\Models\EquipoComputo;
use App\Models\Marca;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Carga masiva de equipos de cómputo o móviles a partir de responsivas (PDF,
 * escaneo o foto) leídas con IA. Todo se crea como "pendiente" de revisión
 * (igual que el formulario público): lo que la IA no logra leer queda vacío y
 * se anota en Observaciones para que el personal lo complete y valide.
 *
 * Nunca da de baja registros existentes: si el equipo ya está activo
 * (mismo número de serie, o mismo teléfono/IMEI en móviles) el registro se
 * omite y se informa para que se resuelva a mano.
 */
class CargaMasivaRegistros
{
    public function __construct(
        private readonly ExtractorResponsivaPdf $extractor,
        private readonly ResponsivaAFormulario $mapeador,
    ) {}

    /**
     * Un archivo = una responsiva. Si es PDF se adjunta al registro creado.
     *
     * @return array<int, array{archivo: string, estado: string, nombre: ?string, mensaje: string}>
     */
    public function procesarArchivo(string $tipo, string $rutaRelativa, string $nombreOriginal): array
    {
        $disco = Storage::disk('local');

        try {
            $crudo = $this->conReintento(fn () => $this->extractor->extraer($disco->path($rutaRelativa), $tipo));

            $esPdf = $disco->mimeType($rutaRelativa) === 'application/pdf';

            return [$this->crear($tipo, $crudo, $nombreOriginal, $esPdf ? $rutaRelativa : null)];
        } catch (Throwable $e) {
            report($e);

            return [$this->fila($nombreOriginal, 'error', null, $e->getMessage())];
        } finally {
            // Si el PDF se adjuntó ya fue movido; en cualquier otro caso es temporal.
            $disco->delete($rutaRelativa);
        }
    }

    /**
     * Un PDF con varias responsivas: solo se crean los registros (sin adjuntos).
     *
     * @return array<int, array{archivo: string, estado: string, nombre: ?string, mensaje: string}>
     */
    public function procesarPdfUnico(string $tipo, string $rutaRelativa, string $nombreOriginal): array
    {
        $disco = Storage::disk('local');

        try {
            $lista = $this->conReintento(fn () => $this->extractor->extraerVarios($disco->path($rutaRelativa), $tipo));

            if ($lista === []) {
                return [$this->fila($nombreOriginal, 'error', null, 'No se detectó ninguna responsiva en el PDF.')];
            }

            $filas = [];
            foreach ($lista as $i => $crudo) {
                $filas[] = $this->crear($tipo, $crudo, $nombreOriginal . ' · responsiva ' . ($i + 1), null);
            }

            return $filas;
        } catch (Throwable $e) {
            report($e);

            return [$this->fila($nombreOriginal, 'error', null, $e->getMessage())];
        } finally {
            $disco->delete($rutaRelativa);
        }
    }

    /**
     * @param  array<string, mixed>  $crudo  lo que devolvió la IA
     * @param  string|null  $adjunto  ruta (disco local) del PDF a adjuntar como responsiva
     * @return array{archivo: string, estado: string, nombre: ?string, mensaje: string}
     */
    private function crear(string $tipo, array $crudo, string $origen, ?string $adjunto): array
    {
        $celular = $tipo === ExtractorResponsivaPdf::TIPO_CELULAR;
        ['datos' => $d, 'avisos' => $avisos] = $this->mapeador->mapear($crudo, $tipo);

        $identificadores = $celular
            ? ['nombre_usuario', 'imei', 'modelo', 'numero_telefonico']
            : ['nombre_usuario', 'numero_serie', 'modelo', 'correo_electronico'];

        if (collect($identificadores)->every(fn ($campo) => blank($d[$campo] ?? null))) {
            return $this->fila($origen, 'omitido', null, 'No se detectó información de una responsiva.');
        }

        $d['tipo_movimiento'] ??= 'alta';
        $esBaja = $d['tipo_movimiento'] === 'baja';

        // Nunca se da de baja a nadie desde aquí: si ya hay un registro activo, se omite.
        if (! $esBaja) {
            $conflicto = $celular
                ? EquipoCelularForm::buscarConflictos($d, null)->map(fn ($c) => $c['registro']->nombre_usuario . ' — ' . implode(' y ', $c['motivos']))->first()
                : (($e = EquipoComputoForm::buscarConflictoNumeroSerie($d['numero_serie'] ?? null, null))
                    ? $e->nombre_usuario . ' — mismo número de serie (' . $e->numero_serie . ')'
                    : null);

            if ($conflicto) {
                return $this->fila($origen, 'omitido', $d['nombre_usuario'] ?? null, "Ya existe un registro activo: {$conflicto}. Resuélvelo manualmente.");
            }
        }

        // Campos obligatorios en BD que no se pudieron leer: se completan con un valor
        // provisional y se avisa en Observaciones.
        $faltantes = [];
        $revisar = function (string $campo, string $etiqueta) use (&$d, &$faltantes) {
            if (blank($d[$campo] ?? null)) {
                $faltantes[] = $etiqueta;
            }
        };

        foreach ([
            'sucursal_id' => 'Sucursal', 'area_id' => 'Área', 'marca_id' => 'Marca', 'modelo' => 'Modelo',
        ] + ($celular
            ? ['imei' => 'IMEI', 'numero_telefonico' => 'Número telefónico', 'tipo_equipo' => 'Tipo de equipo']
            : ['numero_serie' => 'Número de serie', 'correo_electronico' => 'Correo', 'tipo_equipo' => 'Tipo de equipo']) as $campo => $etiqueta) {
            $revisar($campo, $etiqueta);
        }

        $d['nombre_usuario'] = $d['nombre_usuario'] ?? Str::limit('Sin nombre (' . $origen . ')', 250, '');
        if (blank($crudo['nombre_completo'] ?? $crudo['nombre_colaborador'] ?? null)) {
            $faltantes[] = 'Nombre';
        }

        $d['fecha_entrega'] ??= now()->format('Y-m-d H:i:s');
        $d['tipo_equipo'] ??= $celular ? 'celular' : 'laptop';
        $d['modelo'] ??= '';

        if (! $celular) {
            $d['correo_electronico'] ??= '';
            $d['numero_serie'] ??= '';
            $this->resolverMarcaComputo($d, $crudo['marca'] ?? null);
        }

        if ($esBaja) {
            $d['fecha_baja'] = $d['fecha_entrega'];
        }

        $notas = array_filter([
            $d['observaciones'] ?? null,
            '⚠ Carga masiva (' . $origen . '): pendiente de revisar'
                . ($faltantes ? ' — no se detectó: ' . implode(', ', array_unique($faltantes)) : '') . '.',
            $avisos ? 'Avisos: ' . implode(' ', $avisos) : null,
        ]);
        $d['observaciones'] = implode("\n", $notas);

        $d['origen_registro'] = 'masivo';
        $d['estado_revision'] = 'pendiente';

        $modelo = $celular ? EquipoCelular::class : EquipoComputo::class;

        if ($adjunto) {
            $d['responsiva_pdf'] = $this->adjuntar($adjunto, $d['nombre_usuario'], $celular);
        }

        $registro = $modelo::create($d);

        return $this->fila(
            $origen,
            'creado',
            $registro->nombre_usuario,
            $faltantes ? 'Creado como pendiente. Falta: ' . implode(', ', array_unique($faltantes)) . '.' : 'Creado como pendiente.',
        );
    }

    /**
     * Igual que el formulario público: una marca que no está en el catálogo cae en
     * «Otra» y se conserva el texto detectado.
     *
     * @param  array<string, mixed>  $d
     */
    private function resolverMarcaComputo(array &$d, ?string $marcaDetectada): void
    {
        if (blank($marcaDetectada)) {
            return;
        }

        $d['marca_detectada'] = Str::limit(trim($marcaDetectada), 250, '');

        if (blank($d['marca_id'] ?? null)) {
            $d['marca_id'] = Marca::firstOrCreate(['nombre' => 'Otra'], ['categoria' => 'ambas', 'activo' => true])->id;
        }
    }

    /**
     * Mueve el PDF subido a la carpeta de responsivas con el nombre de la persona.
     */
    private function adjuntar(string $rutaTemporal, string $nombre, bool $celular): string
    {
        $destino = ($celular ? 'responsivas/celulares/' : 'responsivas/computo/') . Str::slug($nombre ?: 'equipo') . '-' . Str::random(6) . '.pdf';

        Storage::disk('local')->move($rutaTemporal, $destino);

        return $destino;
    }

    /**
     * El nivel gratuito de la IA limita las solicitudes por minuto: ante un 429 se
     * espera y se reintenta una vez antes de marcar el archivo con error.
     */
    private function conReintento(\Closure $accion): mixed
    {
        try {
            return $accion();
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), 'límite gratuito')) {
                throw $e;
            }

            sleep(20);

            return $accion();
        }
    }

    /**
     * @return array{archivo: string, estado: string, nombre: ?string, mensaje: string}
     */
    private function fila(string $archivo, string $estado, ?string $nombre, string $mensaje): array
    {
        return compact('archivo', 'estado', 'nombre', 'mensaje');
    }
}
