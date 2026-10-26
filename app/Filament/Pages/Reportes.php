<?php

namespace App\Filament\Pages;

use App\Models\RazonSocial;
use App\Models\Sucursal;
use App\Services\DescargaResponsivas;
use App\Services\ReporteInventario;
use App\Support\OpcionesOrdenadas;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use UnitEnum;

class Reportes extends Page implements HasSchemas
{
    use HasPageShield;
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes de inventario';

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.reportes';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'secciones' => array_keys($this->seccionesPermitidas()),
            'estado' => 'todos',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('¿Qué incluir?')
                    ->description('Cada sección va en su propia hoja del mismo archivo de Excel. Si marcas solo una, el archivo trae solo esa hoja.')
                    ->schema([
                        CheckboxList::make('secciones')
                            ->hiddenLabel()
                            ->options($this->seccionesPermitidas())
                            ->required()
                            ->live(),
                    ]),

                Section::make('Filtros (opcionales)')
                    ->columns(2)
                    ->schema([
                        Select::make('estado')
                            ->label('Estado')
                            ->options(['todos' => 'Todos', 'activos' => 'Solo activos', 'baja' => 'Solo bajas'])
                            ->native(false)
                            ->live()
                            ->helperText('Aplica a Equipos de Cómputo y Equipos Móviles. Dispositivos Externos no tiene baja.'),

                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->options(fn () => OpcionesOrdenadas::alfabetico(Sucursal::where('activo', true)->pluck('nombre', 'id')))
                            ->placeholder('Todas')
                            ->native(false)
                            ->searchable()
                            ->live(),

                        DatePicker::make('desde')
                            ->label('Fecha de entrega desde')
                            ->displayFormat('d/m/Y')
                            ->live(),

                        DatePicker::make('hasta')
                            ->label('Fecha de entrega hasta')
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('desde')
                            ->live(),

                        Select::make('razon_social_id')
                            ->label('Razón social')
                            ->options(fn () => OpcionesOrdenadas::alfabetico(RazonSocial::where('activo', true)->pluck('nombre', 'id')))
                            ->placeholder('Todas')
                            ->native(false)
                            ->live(),
                    ]),
            ]);
    }

    /**
     * Cantidad de registros que se exportarían con el formulario actual.
     *
     * @return array<string, int>
     */
    public function conteos(): array
    {
        $filtros = $this->filtros();
        $conteos = [];

        foreach ($this->data['secciones'] ?? [] as $seccion) {
            if (array_key_exists($seccion, $this->seccionesPermitidas())) {
                $conteos[ReporteInventario::SECCIONES[$seccion]] = app(ReporteInventario::class)->consulta($seccion, $filtros)->count();
            }
        }

        return $conteos;
    }

    public function descargar()
    {
        $this->form->validate();

        $permitidas = $this->seccionesPermitidas();
        $secciones = array_values(array_filter($this->data['secciones'] ?? [], fn ($s) => array_key_exists($s, $permitidas)));

        if ($secciones === []) {
            Notification::make()->title('Selecciona al menos una sección')->warning()->send();

            return null;
        }

        return app(ReporteInventario::class)->descargarCombinado($secciones, $this->filtros(), auth()->user()->name ?? '');
    }

    /**
     * ZIP con las responsivas de los registros que cumplen los mismos filtros.
     */
    public function descargarResponsivas()
    {
        $this->form->validate();

        $permitidas = $this->seccionesPermitidas();
        $secciones = array_values(array_filter($this->data['secciones'] ?? [], fn ($s) => array_key_exists($s, $permitidas)));

        if ($secciones === []) {
            Notification::make()->title('Selecciona al menos una sección')->warning()->send();

            return null;
        }

        $servicio = app(ReporteInventario::class);
        $porSeccion = [];

        foreach ($secciones as $seccion) {
            $porSeccion[$seccion] = $servicio->consulta($seccion, $this->filtros())->get();
        }

        try {
            return app(DescargaResponsivas::class)->zip($porSeccion);
        } catch (RuntimeException $e) {
            Notification::make()->title('No se pudo descargar')->body($e->getMessage())->warning()->send();

            return null;
        }
    }

    /**
     * Solo las secciones que el usuario puede consultar.
     *
     * @return array<string, string>
     */
    private function seccionesPermitidas(): array
    {
        return collect(ReporteInventario::SECCIONES)
            ->filter(fn ($etiqueta, $seccion) => auth()->user()?->can('viewAny', ReporteInventario::modelo($seccion)))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function filtros(): array
    {
        return collect($this->data ?? [])->only(['estado', 'desde', 'hasta', 'sucursal_id', 'razon_social_id'])->all();
    }
}
