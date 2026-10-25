<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EntregaDispositivos\EntregaDispositivoResource;
use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use App\Models\EntregaDispositivo;
use App\Models\EquipoCelular;
use App\Models\EquipoComputo;
use App\Services\CargaMasivaRegistros;
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
use Illuminate\Support\Facades\Storage;
use UnitEnum;

/**
 * Carga masiva de registros desde responsivas: la IA lee cada documento y se
 * crean registros "pendientes" que el personal revisa y valida. Se procesa un
 * archivo por solicitud (el navegador va pidiendo el siguiente) para no chocar
 * con el tiempo máximo de ejecución del servidor.
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

    /** @var array<int, array{ruta: string, nombre: string}> */
    public array $cola = [];

    /** @var array<int, array{archivo: string, estado: string, nombre: ?string, mensaje: string}> */
    public array $resultados = [];

    public int $total = 0;

    public bool $procesando = false;

    public string $tipoProceso = ExtractorResponsivaPdf::TIPO_COMPUTO;

    public string $modoProceso = 'archivos';

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
            $ruta = $estado['pdf_unico'] ?? null;
            $cola = $ruta ? [['ruta' => $ruta, 'nombre' => $estado['nombre_pdf_unico'] ?? basename($ruta)]] : [];
        } elseif ($modo === 'excel') {
            $ruta = $estado['excel'] ?? null;
            $cola = $ruta ? [['ruta' => $ruta, 'nombre' => $estado['nombre_excel'] ?? basename($ruta)]] : [];
        } else {
            $nombres = $estado['nombres_archivos'] ?? [];
            $cola = collect($estado['archivos'] ?? [])
                ->map(fn ($ruta, $clave) => ['ruta' => $ruta, 'nombre' => $nombres[$clave] ?? $nombres[$ruta] ?? basename($ruta)])
                ->values()
                ->all();
        }

        if ($cola === []) {
            Notification::make()->title('Sube al menos un archivo')->warning()->send();

            return;
        }

        $this->cola = $cola;
        $this->total = count($cola);
        $this->resultados = [];
        $this->tipoProceso = $tipo;
        $this->modoProceso = $modo;
        $this->procesando = true;

        $this->form->fill(['tipo' => $tipo, 'modo' => $modo]);
    }

    /**
     * Procesa el siguiente archivo de la cola. Devuelve true mientras queden más.
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

        $item = array_shift($this->cola);
        $servicio = app(CargaMasivaRegistros::class);

        $filas = match ($this->modoProceso) {
            'unico' => $servicio->procesarPdfUnico($this->tipoProceso, $item['ruta'], $item['nombre']),
            'excel' => $servicio->procesarExcel($this->tipoProceso, $item['ruta'], $item['nombre']),
            default => $servicio->procesarArchivo($this->tipoProceso, $item['ruta'], $item['nombre']),
        };

        $this->resultados = [...$this->resultados, ...$filas];

        if ($this->cola === []) {
            $this->terminar();

            return false;
        }

        return true;
    }

    /**
     * El navegador perdió la conexión o el servidor cortó la solicitud por tiempo.
     */
    public function procesoInterrumpido(): void
    {
        foreach ($this->cola as $item) {
            Storage::disk('local')->delete($item['ruta']);
            $this->resultados[] = ['archivo' => $item['nombre'], 'estado' => 'error', 'nombre' => null, 'mensaje' => 'No se procesó: el proceso se interrumpió. Súbelo de nuevo.'];
        }

        $this->cola = [];
        $this->terminar();
    }

    public function nuevaCarga(): void
    {
        $this->resultados = [];
        $this->total = 0;
        $this->mount();
    }

    private function terminar(): void
    {
        $this->procesando = false;

        $resultados = collect($this->resultados);
        $duplicados = $resultados->where('estado', 'duplicado')->count();
        $creados = $resultados->where('estado', 'creado')->count() + $duplicados;
        $problemas = $resultados->whereIn('estado', ['omitido', 'error'])->count();

        $avisos = array_filter([
            $duplicados > 0 ? "{$duplicados} se crearon como BAJA por posible duplicado." : null,
            $problemas > 0 ? "{$problemas} documento(s) no se pudieron crear." : null,
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
