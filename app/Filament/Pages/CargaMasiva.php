<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EntregaDispositivos\EntregaDispositivoResource;
use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use App\Models\EntregaDispositivo;
use App\Models\EquipoCelular;
use App\Models\EquipoComputo;
use App\Services\CargaMasivaRegistros;
use App\Services\ErrorTemporalIA;
use App\Services\ExcelRegistros;
use App\Services\ExtractorResponsivaPdf;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use RuntimeException;
use Throwable;
use UnitEnum;

/**
 * Carga masiva de registros desde responsivas: la IA lee cada documento y se
 * crean registros "pendientes" que el personal revisa y valida. Se procesa un
 * bloque pequeño por solicitud (un archivo, unas páginas de un PDF grande o unas
 * filas de un Excel) y el navegador va pidiendo el siguiente, para no chocar con
 * el tiempo máximo de ejecución del servidor.
 *
 * Si la IA está saturada, el bloque se reintenta solo (con espera) y, si sigue
 * fallando, se conserva el archivo para reintentarlo con un botón. Todo lo creado
 * lleva el identificador de la carga para poder deshacerla completa.
 */
class CargaMasiva extends Page implements HasSchemas
{
    use HasPageShield;
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Carga Masiva';

    protected static ?string $title = 'Carga masiva de registros';

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.carga-masiva';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Veces que se reintenta un bloque cuando la IA está saturada, antes de dejarlo para reintento manual. */
    private const REINTENTOS = 2;

    // Todo lo del proceso es de solo lectura para el navegador: el cliente no puede
    // alterar rutas de archivos ni el avance.

    /**
     * Bloques pendientes. Cada uno: ruta, nombre, etiqueta, intentos, no_antes_de y,
     * según el modo, desde/hasta (páginas) o fila_desde/cantidad (Excel).
     *
     * @var array<int, array<string, mixed>>
     */
    #[Locked]
    public array $cola = [];

    /**
     * Bloques que fallaron por saturación de la IA y conservan su archivo.
     *
     * @var array<int, array<string, mixed>>
     */
    #[Locked]
    public array $fallidos = [];

    /** @var array<int, array<string, mixed>> */
    #[Locked]
    public array $resultados = [];

    #[Locked]
    public int $total = 0;

    #[Locked]
    public bool $procesando = false;

    #[Locked]
    public string $tipoProceso = ExtractorResponsivaPdf::TIPO_COMPUTO;

    #[Locked]
    public string $modoProceso = 'archivos';

    /** Identificador de la carga en curso (columna lote_carga de los registros). */
    #[Locked]
    public ?string $lote = null;

    protected function getHeaderActions(): array
    {
        $permitidos = $this->tiposPermitidos();

        return [
            Action::make('plantillaComputo')
                ->label('Plantilla Excel · Cómputo')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => array_key_exists(ExtractorResponsivaPdf::TIPO_COMPUTO, $permitidos))
                ->action(fn () => app(ExcelRegistros::class)->plantilla(ExtractorResponsivaPdf::TIPO_COMPUTO)),

            Action::make('plantillaCelular')
                ->label('Plantilla Excel · Móviles')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => array_key_exists(ExtractorResponsivaPdf::TIPO_CELULAR, $permitidos))
                ->action(fn () => app(ExcelRegistros::class)->plantilla(ExtractorResponsivaPdf::TIPO_CELULAR)),

            Action::make('plantillaDispositivos')
                ->label('Plantilla Excel · Dispositivos')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => array_key_exists(ExtractorResponsivaPdf::TIPO_DISPOSITIVOS, $permitidos))
                ->action(fn () => app(ExcelRegistros::class)->plantilla(ExtractorResponsivaPdf::TIPO_DISPOSITIVOS)),
        ];
    }

    public function mount(): void
    {
        try {
            CargaMasivaRegistros::limpiarTemporales();
        } catch (Throwable $e) {
            report($e);
        }

        $this->form->fill([
            'tipo' => array_key_first($this->tiposPermitidos()),
            'modo' => 'archivos',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('1. ¿Qué vas a cargar?')
                    ->schema([
                        Select::make('tipo')
                            ->label('Tipo de registro')
                            ->options($this->tiposPermitidos())
                            ->required()
                            ->native(false),

                        Radio::make('modo')
                            ->label('¿Cómo vienen las responsivas?')
                            ->options([
                                'archivos' => 'Un archivo por responsiva',
                                'unico' => 'Un solo PDF con varias responsivas',
                                'excel' => 'Archivo de Excel con una fila por equipo',
                            ])
                            ->descriptions([
                                'archivos' => 'Puedes subir varios archivos a la vez (PDF, JPG o PNG). Cada PDF se adjunta como responsiva del registro creado; las fotos solo se leen.',
                                'unico' => 'Solo se crean los registros: no se adjunta ninguna responsiva, porque el PDF trae varias juntas.',
                                'excel' => 'Usa la plantilla (botón «Plantilla Excel» arriba). No usa IA ni adjunta responsivas; solo crea los registros. Hasta ' . ExcelRegistros::MAX_FILAS . ' filas por archivo.',
                            ])
                            ->required()
                            ->live(),
                    ]),

                Section::make('2. Sube los archivos')
                    ->description('Los registros se crean como pendientes de revisión; lo que no se pueda leer (o lo que dejes vacío en el Excel) queda vacío y se anota en Observaciones. Si el equipo ya está activo en el sistema (mismo número de serie, teléfono o IMEI, o mismo nombre y modelo), el nuevo se crea igual pero como BAJA y pendiente, con la advertencia en Observaciones.')
                    ->schema([
                        FileUpload::make('archivos')
                            ->label('Responsivas')
                            ->multiple()
                            ->maxFiles(30)
                            ->maxSize(10240)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->disk('local')
                            ->directory('tmp/carga-masiva')
                            ->storeFileNamesIn('nombres_archivos')
                            ->visible(fn ($get) => $get('modo') === 'archivos')
                            ->required(fn ($get) => $get('modo') === 'archivos'),

                        FileUpload::make('pdf_unico')
                            ->label('PDF con varias responsivas')
                            ->maxSize(12288)
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('local')
                            ->directory('tmp/carga-masiva')
                            ->storeFileNamesIn('nombre_pdf_unico')
                            ->helperText('Máximo 12 MB. Si es más grande, divídelo en varios PDF.')
                            ->visible(fn ($get) => $get('modo') === 'unico')
                            ->required(fn ($get) => $get('modo') === 'unico'),

                        FileUpload::make('excel')
                            ->label('Archivo de Excel')
                            ->maxSize(5120)
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->disk('local')
                            ->directory('tmp/carga-masiva')
                            ->storeFileNamesIn('nombre_excel')
                            ->helperText('.xlsx o .csv, con los datos en la primera hoja y los encabezados de la plantilla en la primera fila.')
                            ->visible(fn ($get) => $get('modo') === 'excel')
                            ->required(fn ($get) => $get('modo') === 'excel'),
                    ]),
            ]);
    }

    public function iniciar(): void
    {
        $estado = $this->form->getState();

        $tipo = $estado['tipo'] ?? null;
        abort_unless(array_key_exists($tipo, $this->tiposPermitidos()), 403);

        $modo = $estado['modo'] ?? 'archivos';

        if ($modo === 'unico') {
            $archivos = $this->soloRutasValidas([['ruta' => $estado['pdf_unico'] ?? null, 'nombre' => $estado['nombre_pdf_unico'] ?? null]]);
        } elseif ($modo === 'excel') {
            $archivos = $this->soloRutasValidas([['ruta' => $estado['excel'] ?? null, 'nombre' => $estado['nombre_excel'] ?? null]]);
        } else {
            $nombres = $estado['nombres_archivos'] ?? [];
            $archivos = $this->soloRutasValidas(collect($estado['archivos'] ?? [])
                ->map(fn ($ruta, $clave) => ['ruta' => $ruta, 'nombre' => $nombres[$clave] ?? $nombres[$ruta] ?? null])
                ->values()
                ->all());
        }

        if ($archivos === []) {
            Notification::make()->title('Sube al menos un archivo')->warning()->send();

            return;
        }

        try {
            $cola = [];
            foreach ($archivos as $archivo) {
                array_push($cola, ...$this->bloquesDe($modo, $tipo, $archivo));
            }
        } catch (RuntimeException $e) {
            // Un Excel que no sirve se rechaza completo y de inmediato, con el motivo.
            foreach ($archivos as $archivo) {
                Storage::disk('local')->delete($archivo['ruta']);
            }

            Notification::make()->title('No se pudo leer el archivo')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->cola = $cola;
        $this->fallidos = [];
        $this->total = count($cola);
        $this->resultados = [];
        $this->tipoProceso = $tipo;
        $this->modoProceso = $modo;
        $this->lote = now()->format('ymd-His') . '-u' . (auth()->id() ?? 0) . '-' . Str::lower(Str::random(4));
        $this->procesando = true;

        $this->form->fill(['tipo' => $tipo, 'modo' => $modo]);
    }

    /**
     * Procesa el siguiente bloque de la cola. Devuelve true mientras queden más.
     */
    public function procesarSiguiente(): bool
    {
        if (! $this->procesando) {
            return false;
        }

        abort_unless(array_key_exists($this->tipoProceso, $this->tiposPermitidos()), 403);

        if ($this->cola === []) {
            $this->terminar();

            return false;
        }

        // La lectura con IA puede tardar: se amplía el límite de este solo paso.
        @set_time_limit(420);

        // Un bloque que se está reintentando espera su turno; si no hay otro listo, se espera.
        $indice = collect($this->cola)->search(fn ($b) => ($b['no_antes_de'] ?? 0) <= time());

        if ($indice === false) {
            $indice = collect($this->cola)->keys()->sortBy(fn ($k) => $this->cola[$k]['no_antes_de'])->first();
            sleep(max(1, min(40, $this->cola[$indice]['no_antes_de'] - time())));
        }

        $bloque = $this->cola[$indice];
        unset($this->cola[$indice]);
        $this->cola = array_values($this->cola);

        $servicio = app(CargaMasivaRegistros::class);

        try {
            $filas = match ($this->modoProceso) {
                'unico' => $servicio->procesarPdfUnico($this->tipoProceso, $bloque['ruta'], $bloque['nombre'], $bloque['desde'] ?? null, $bloque['hasta'] ?? null, $this->lote),
                'excel' => $servicio->procesarExcel($this->tipoProceso, $bloque['ruta'], $bloque['nombre'], (int) ($bloque['fila_desde'] ?? 0), $bloque['cantidad'] ?? null, $this->lote),
                default => $servicio->procesarArchivo($this->tipoProceso, $bloque['ruta'], $bloque['nombre'], $this->lote),
            };

            $this->resultados = [...$this->resultados, ...$filas];
        } catch (ErrorTemporalIA $e) {
            $bloque['intentos'] = ($bloque['intentos'] ?? 0) + 1;

            if ($bloque['intentos'] <= self::REINTENTOS) {
                // Va al final de la cola y no se vuelve a intentar hasta pasado un rato.
                $bloque['no_antes_de'] = time() + (int) config('services.ia.espera_reintento', 20) * $bloque['intentos'];
                $this->cola[] = $bloque;

                return true;
            }

            $this->fallidos[] = $bloque;
            $this->resultados[] = $this->filaReintentable($bloque, $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            $this->resultados[] = ['archivo' => $bloque['etiqueta'], 'estado' => 'error', 'nombre' => null, 'mensaje' => $e->getMessage()];
        }

        $this->liberarArchivo($bloque['ruta']);

        if ($this->cola === []) {
            $this->terminar();

            return false;
        }

        return true;
    }

    /**
     * El navegador perdió la conexión o el servidor cortó la solicitud por tiempo:
     * lo que faltaba se conserva para reintentarlo.
     */
    public function procesoInterrumpido(): void
    {
        foreach ($this->cola as $bloque) {
            $this->fallidos[] = $bloque;
            $this->resultados[] = $this->filaReintentable($bloque, 'No se procesó: el proceso se interrumpió.');
        }

        $this->cola = [];
        $this->terminar();
    }

    /**
     * Vuelve a poner en la cola lo que no se pudo procesar por saturación de la IA.
     */
    public function reintentarFallidos(): void
    {
        abort_unless(array_key_exists($this->tipoProceso, $this->tiposPermitidos()), 403);

        if ($this->procesando || $this->fallidos === []) {
            return;
        }

        $this->resultados = array_values(array_filter($this->resultados, fn ($fila) => empty($fila['reintentable'])));
        $this->cola = array_map(fn ($b) => [...$b, 'intentos' => 0, 'no_antes_de' => 0], $this->fallidos);
        $this->fallidos = [];
        $this->total = count($this->cola);
        $this->procesando = true;
    }

    public function nuevaCarga(): void
    {
        foreach ($this->fallidos as $bloque) {
            Storage::disk('local')->delete($bloque['ruta']);
        }

        $this->fallidos = [];
        $this->resultados = [];
        $this->total = 0;
        $this->lote = null;
        $this->mount();
    }

    /**
     * Elimina lo que creó esta carga y sigue pendiente de revisión (con sus
     * responsivas adjuntas). Lo que ya se revisó o editó se conserva.
     */
    public function deshacerCargaAction(): Action
    {
        return Action::make('deshacerCarga')
            ->label('Deshacer esta carga')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn () => $this->puedeDeshacer())
            ->requiresConfirmation()
            ->modalHeading('¿Deshacer esta carga?')
            ->modalDescription(fn () => 'Se eliminarán de forma definitiva los ' . $this->pendientesDelLote() . ' registros que creó esta carga y que siguen pendientes de revisión, junto con las responsivas adjuntas. Los que ya revisaste o editaste no se tocan.')
            ->modalSubmitActionLabel('Sí, eliminar')
            ->action(function () {
                abort_unless($this->puedeDeshacer(), 403);

                $disco = Storage::disk('local');
                $eliminados = 0;

                foreach ($this->consultaDelLote()->where('estado_revision', 'pendiente')->get() as $registro) {
                    foreach (['responsiva_pdf', 'responsiva_baja_pdf'] as $campo) {
                        if (filled($registro->{$campo})) {
                            $disco->delete($registro->{$campo});
                        }
                    }

                    $registro->forceDelete();
                    $eliminados++;
                }

                $conservados = $this->consultaDelLote()->count();

                $this->nuevaCarga();

                Notification::make()
                    ->title($eliminados . ' registro(s) eliminado(s)')
                    ->body($conservados > 0 ? "{$conservados} ya estaban revisados y se conservaron." : null)
                    ->success()
                    ->send();
            });
    }

    private function puedeDeshacer(): bool
    {
        return ! $this->procesando
            && $this->lote !== null
            && auth()->user()?->can('deleteAny', $this->modeloProceso())
            && $this->pendientesDelLote() > 0;
    }

    private function pendientesDelLote(): int
    {
        return $this->consultaDelLote()->where('estado_revision', 'pendiente')->count();
    }

    private function consultaDelLote()
    {
        return $this->modeloProceso()::query()->where('lote_carga', $this->lote);
    }

    /**
     * @return class-string<Model>
     */
    private function modeloProceso(): string
    {
        return match ($this->tipoProceso) {
            ExtractorResponsivaPdf::TIPO_CELULAR => EquipoCelular::class,
            ExtractorResponsivaPdf::TIPO_DISPOSITIVOS => EntregaDispositivo::class,
            default => EquipoComputo::class,
        };
    }

    /**
     * Divide un archivo en los bloques que se procesarán por separado.
     *
     * @param  array{ruta: string, nombre: string}  $archivo
     * @return array<int, array<string, mixed>>
     *
     * @throws RuntimeException cuando el Excel no se puede leer
     */
    private function bloquesDe(string $modo, string $tipo, array $archivo): array
    {
        $servicio = app(CargaMasivaRegistros::class);
        $base = $archivo + ['etiqueta' => $archivo['nombre'], 'intentos' => 0, 'no_antes_de' => 0];

        if ($modo === 'unico') {
            $paginas = $servicio->contarPaginasPdf($archivo['ruta']);
            $por = CargaMasivaRegistros::PAGINAS_POR_BLOQUE;

            if ($paginas === null || $paginas <= $por) {
                return [$base];
            }

            $bloques = [];
            for ($desde = 1; $desde <= $paginas; $desde += $por) {
                $hasta = min($desde + $por - 1, $paginas);
                $bloques[] = [...$base, 'desde' => $desde, 'hasta' => $hasta, 'etiqueta' => "{$archivo['nombre']} · páginas {$desde} a {$hasta} de {$paginas}"];
            }

            return $bloques;
        }

        if ($modo === 'excel') {
            $filas = $servicio->contarFilasExcel($tipo, $archivo['ruta'], $archivo['nombre']);
            $por = CargaMasivaRegistros::FILAS_POR_BLOQUE;

            if ($filas <= $por) {
                return [$base];
            }

            $bloques = [];
            for ($desde = 0; $desde < $filas; $desde += $por) {
                $hasta = min($desde + $por, $filas);
                $bloques[] = [...$base, 'fila_desde' => $desde, 'cantidad' => $por, 'etiqueta' => "{$archivo['nombre']} · filas " . ($desde + 1) . " a {$hasta} de {$filas}"];
            }

            return $bloques;
        }

        return [$base];
    }

    /**
     * Solo rutas dentro de la carpeta temporal de esta página: el estado del
     * formulario viene del navegador y no debe poder apuntar a otros archivos.
     *
     * @param  array<int, array{ruta: mixed, nombre: mixed}>  $archivos
     * @return array<int, array{ruta: string, nombre: string}>
     */
    private function soloRutasValidas(array $archivos): array
    {
        $validos = [];

        foreach ($archivos as $archivo) {
            $ruta = $archivo['ruta'] ?? null;

            if (is_string($ruta) && str_starts_with($ruta, CargaMasivaRegistros::CARPETA_TEMPORAL . '/') && ! str_contains($ruta, '..')) {
                $validos[] = ['ruta' => $ruta, 'nombre' => (string) ($archivo['nombre'] ?? basename($ruta))];
            }
        }

        return $validos;
    }

    /**
     * Borra el archivo subido cuando ya no lo necesita ningún bloque (de la cola o
     * pendiente de reintento).
     */
    private function liberarArchivo(string $ruta): void
    {
        $enUso = collect([...$this->cola, ...$this->fallidos])->contains(fn ($b) => $b['ruta'] === $ruta);

        if (! $enUso) {
            Storage::disk('local')->delete($ruta);
        }
    }

    /**
     * @param  array<string, mixed>  $bloque
     * @return array<string, mixed>
     */
    private function filaReintentable(array $bloque, string $motivo): array
    {
        return [
            'archivo' => $bloque['etiqueta'],
            'estado' => 'error',
            'nombre' => null,
            'mensaje' => $motivo . ' Puedes reintentarlo con el botón «Reintentar».',
            'reintentable' => true,
        ];
    }

    private function terminar(): void
    {
        $this->procesando = false;

        // Un PDF con varias responsivas se lee por bloques: si ninguno encontró nada, se avisa aquí.
        if ($this->modoProceso === 'unico' && $this->resultados === []) {
            $this->resultados[] = ['archivo' => 'PDF', 'estado' => 'error', 'nombre' => null, 'mensaje' => 'No se detectó ninguna responsiva en el PDF.'];
        }

        $resultados = collect($this->resultados);
        $duplicados = $resultados->where('estado', 'duplicado')->count();
        $creados = $resultados->where('estado', 'creado')->count() + $duplicados;
        $problemas = $resultados->whereIn('estado', ['omitido', 'error'])->count();
        $porReintentar = count($this->fallidos);

        $avisos = array_filter([
            $duplicados > 0 ? "{$duplicados} se crearon como BAJA por posible duplicado." : null,
            $problemas > 0 ? "{$problemas} documento(s) no se pudieron crear." : null,
            $porReintentar > 0 ? "{$porReintentar} se pueden reintentar con el botón «Reintentar»." : null,
        ]);

        Notification::make()
            ->title($creados . ' registro(s) creado(s) como pendientes')
            ->body($avisos ? implode(' ', $avisos) . ' Revisa el detalle.' : 'Ya puedes revisarlos y validarlos.')
            ->{$avisos ? 'warning' : 'success'}()
            ->send();
    }

    public function urlPendientes(): string
    {
        $recurso = match ($this->tipoProceso) {
            ExtractorResponsivaPdf::TIPO_CELULAR => EquipoCelularResource::class,
            ExtractorResponsivaPdf::TIPO_DISPOSITIVOS => EntregaDispositivoResource::class,
            default => EquipoComputoResource::class,
        };

        return $recurso::getUrl('index', ['tableFilters' => ['estado_revision' => ['value' => 'pendiente']]]);
    }

    /**
     * Tipos que el usuario puede crear.
     *
     * @return array<string, string>
     */
    private function tiposPermitidos(): array
    {
        $usuario = auth()->user();

        return collect([
            ExtractorResponsivaPdf::TIPO_COMPUTO => ['Equipos de Cómputo', EquipoComputo::class],
            ExtractorResponsivaPdf::TIPO_CELULAR => ['Equipos Móviles', EquipoCelular::class],
            ExtractorResponsivaPdf::TIPO_DISPOSITIVOS => ['Dispositivos Externos', EntregaDispositivo::class],
        ])
            ->filter(fn ($def) => $usuario?->can('create', $def[1]))
            ->map(fn ($def) => $def[0])
            ->all();
    }
}
