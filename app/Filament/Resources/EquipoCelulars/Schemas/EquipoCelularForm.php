<?php

namespace App\Filament\Resources\EquipoCelulars\Schemas;

use App\Support\AltaRapidaCatalogo;
use App\Support\OpcionesOrdenadas;
use App\Models\Area;
use App\Models\EquipoCelular;
use App\Models\Marca;
use App\Models\RazonSocial;
use App\Models\Sucursal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EquipoCelularForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Tabs::make('Formulario')
                ->columnSpanFull()
                ->contained(false)
                ->tabs([

                    // ─────────────────────────────────────────────
                    //  TAB 1 · Información
                    // ─────────────────────────────────────────────
                    Tab::make('Información')
                        ->icon('heroicon-o-information-circle')
                        ->schema([

                            Grid::make(2)
                                ->schema([

                                    // ── Columna izquierda: movimiento ──
                                    Section::make('Movimiento')
                                        ->icon('heroicon-o-arrow-path')
                                        ->columnSpan(1)
                                        ->schema([

                                            Select::make('tipo_movimiento')
                                                ->label('Tipo de Movimiento')
                                                ->options(OpcionesOrdenadas::alfabetico([
                                                    'alta'              => '🟢 Alta',
                                                    'baja'              => '🔴 Baja',
                                                    'cambio_equipo'     => '🔄 Cambio de Equipo',
                                                    'reasignacion'      => '🔁 Reasignación',
                                                    'mantenimiento'     => '🔧 Mantenimiento',
                                                    'prestamo_temporal' => '⏱ Préstamo Temporal',
                                                ]))
                                                ->default('alta')
                                                ->required()
                                                ->native(false),

                                            DateTimePicker::make('fecha_entrega')
                                                ->label('Fecha de Entrega')
                                                ->required()
                                                ->seconds(false)
                                                ->default(now()),

                                            Select::make('razon_social_id')
                                                ->label('Razón Social')
                                                ->relationship('razonSocial', 'nombre')
                                                ->options(OpcionesOrdenadas::alfabetico(RazonSocial::where('activo', true)->pluck('nombre', 'id')))
                                                ->required()
                                                ->native(false)
                                                ->createOptionForm([
                                                    TextInput::make('nombre')->required(),
                                                    TextInput::make('rfc')->required(),
                                                ])
                                                ->createOptionUsing(fn (array $data) => AltaRapidaCatalogo::crear(\App\Models\RazonSocial::class, $data, 'la razón social')),
                                        ]),

                                    // ── Columna derecha: datos del usuario ──
                                    Section::make('Datos del Usuario')
                                        ->icon('heroicon-o-user')
                                        ->columnSpan(1)
                                        ->columns(2)
                                        ->schema([

                                            TextInput::make('nombre_usuario')
                                                ->label('Nombre Completo')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if (!$get('correo_electronico')) {
                                                        $set('usuario_referencia', strtolower(str_replace(' ', '.', $state ?? '')));
                                                    }
                                                }),

                                            Select::make('sucursal_id')
                                                ->label('Sucursal')
                                                ->relationship('sucursal', 'nombre')
                                                ->options(OpcionesOrdenadas::alfabetico(Sucursal::where('activo', true)->pluck('nombre', 'id')))
                                                ->required()
                                                ->native(false)
                                                ->searchable()
                                                ->createOptionForm([
                                                    TextInput::make('nombre')->required(),
                                                    TextInput::make('ciudad')->required(),
                                                ])
                                                ->createOptionUsing(fn (array $data) => AltaRapidaCatalogo::crear(\App\Models\Sucursal::class, $data, 'la sucursal')),

                                            Select::make('area_id')
                                                ->label('Área')
                                                ->relationship('area', 'nombre')
                                                ->options(OpcionesOrdenadas::alfabetico(Area::where('activo', true)->pluck('nombre', 'id')))
                                                ->required()
                                                ->native(false)
                                                ->createOptionForm([
                                                    TextInput::make('nombre')->required(),
                                                ])
                                                ->createOptionUsing(fn (array $data) => AltaRapidaCatalogo::crear(\App\Models\Area::class, $data, 'el área')),

                                            Hidden::make('usuario_referencia'),
                                        ]),
                                ]),
                        ]),

                    // ─────────────────────────────────────────────
                    //  TAB 2 · Equipo
                    // ─────────────────────────────────────────────
                    Tab::make('Equipo')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->schema([

                            Grid::make(3)
                                ->schema([

                                    // ── Ficha técnica (2/3 del ancho) ──
                                    Section::make('Ficha Técnica')
                                        ->icon('heroicon-o-cpu-chip')
                                        ->columnSpan(2)
                                        ->columns(2)
                                        ->schema([

                                            Select::make('tipo_equipo')
                                                ->label('Tipo de Equipo')
                                                ->options(OpcionesOrdenadas::alfabetico([
                                                    'celular' => '📱 Celular',
                                                    'tablet'  => '📟 Tablet',
                                                    'ipad'    => '🍎 iPad',
                                                    'otro'    => '📦 Otro',
                                                ]))
                                                ->required()
                                                ->native(false),

                                            Select::make('marca_id')
                                                ->label('Marca')
                                                ->relationship('marca', 'nombre')
                                                ->options(
                                                    OpcionesOrdenadas::alfabetico(Marca::activas()->deCelular()->pluck('nombre', 'id'))
                                                )
                                                ->required()
                                                ->native(false)
                                                ->searchable()
                                                ->createOptionForm([
                                                    TextInput::make('nombre')
                                                        ->label('Nombre de la Marca')
                                                        ->required(),
                                                    Select::make('categoria')
                                                        ->label('¿La marca es para..?')
                                                        ->options(OpcionesOrdenadas::alfabetico([
                                                            'ambas'   => 'Ambas',
                                                            'computo' => 'Equipo de Cómputo',
                                                            'celular' => 'Celular',
                                                        ]))
                                                        ->required(),
                                                ])
                                                ->createOptionUsing(fn (array $data) => AltaRapidaCatalogo::crear(\App\Models\Marca::class, $data, 'la marca', 'celular')),

                                            TextInput::make('modelo')
                                                ->label('Modelo')
                                                ->required()
                                                ->maxLength(150),

                                            TextInput::make('numero_telefonico')
                                                ->label('Número Telefónico')
                                                ->tel()
                                                ->maxLength(20),

                                            TextInput::make('imei')
                                                ->label('IMEI')
                                                ->maxLength(20)
                                                ->placeholder('15 dígitos')
                                                ->helperText('Si ya existe un equipo activo con este IMEI, al guardar se te preguntará si deseas darlo de baja para reutilizarlo.'),

                                            TextInput::make('iccid')
                                                ->label('ICCID (SIM)')
                                                ->maxLength(22)
                                                ->placeholder('19–22 dígitos'),

                                            TextInput::make('curp')
                                                ->label('CURP')
                                                ->maxLength(20)
                                                ->placeholder('18 dígitos')
                                                ->columnSpanFull(),

                                            Textarea::make('observaciones')
                                                ->label('Observaciones')
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ]),

                                    // ── Panel lateral derecho (1/3 del ancho) ──
                                    Section::make('Responsiva')
                                        ->icon('heroicon-o-document-text')
                                        ->columnSpan(1)
                                        ->schema([
                                            FileUpload::make('responsiva_pdf')
                                                ->label('Cargar PDF')
                                                ->acceptedFileTypes(['application/pdf'])
                                                ->directory('responsivas/celulares')
                                                ->disk('local')
                                                ->maxSize(10240)
                                                ->getUploadedFileNameForStorageUsing(
                                                    fn($get) => Str::slug($get('nombre_usuario') ?: 'equipo') . '-' . Str::random(6) . '.pdf'
                                                )
                                                ->downloadable()
                                                ->openable(),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }

    /**
     * Registros ACTIVOS (tipo_movimiento distinto de 'baja') que chocan con el que
     * se está guardando:
     *
     *  - Mismo número telefónico: la misma línea se está entregando en otro
     *    equipo (el IMEI cambia porque es otro celular). Lo vigente es el
     *    registro nuevo; el anterior pasa a baja.
     *  - Mismo IMEI: el mismo equipo físico no puede estar activo dos veces.
     *
     * Si el registro que se guarda es una baja no hay nada que reemplazar. Al
     * editar, el teléfono solo se revisa si cambió (o si el registro dejó de
     * ser baja), para no dar de baja registros más recientes al corregir uno viejo.
     *
     * @param  array<string, mixed>  $data  estado del formulario
     * @return Collection<int, array{registro: EquipoCelular, motivos: array<int, string>}>
     */
    public static function buscarConflictos(array $data, ?EquipoCelular $record): Collection
    {
        $conflictos = collect();

        if (($data['tipo_movimiento'] ?? null) === 'baja') {
            return $conflictos;
        }

        $agregar = function (EquipoCelular $encontrado, string $motivo) use (&$conflictos) {
            $actual = $conflictos->get($encontrado->id, ['registro' => $encontrado, 'motivos' => []]);
            $actual['motivos'][] = $motivo;
            $conflictos->put($encontrado->id, $actual);
        };

        $activos = fn () => EquipoCelular::query()
            ->with('marca')
            ->where('tipo_movimiento', '!=', 'baja')
            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()));

        $telefono = self::soloDigitos($data['numero_telefonico'] ?? null);

        $telefonoCambio = ! $record
            || self::soloDigitos($record->numero_telefonico) !== $telefono
            || $record->tipo_movimiento === 'baja';

        if (strlen($telefono) >= 7 && $telefonoCambio) {
            // Se comparan solo los dígitos (ignora espacios, guiones, paréntesis, +) y
            // los últimos 10, para que "+52 55 1234 5678" y "5512345678" sean el mismo.
            $expresion = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(numero_telefonico, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', '')";
            $comparar = strlen($telefono) >= 10 ? "RIGHT({$expresion}, 10) = ?" : "{$expresion} = ?";
            $valor = strlen($telefono) >= 10 ? substr($telefono, -10) : $telefono;

            foreach ($activos()->whereRaw($comparar, [$valor])->get() as $encontrado) {
                $agregar($encontrado, 'mismo número telefónico (' . $encontrado->numero_telefonico . ')');
            }
        }

        $imei = trim((string) ($data['imei'] ?? ''));

        if ($imei !== '') {
            foreach ($activos()->where('imei', $imei)->get() as $encontrado) {
                $agregar($encontrado, 'mismo IMEI (' . $encontrado->imei . ')');
            }
        }

        return $conflictos;
    }

    private static function soloDigitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    /**
     * Acción oculta (sin botón visible) registrada en las páginas de Crear y
     * Editar. Se monta programáticamente al guardar cuando hay registros
     * activos en conflicto. Si el usuario confirma, esos registros pasan a
     * baja y se reintenta guardar el actual (que queda como el vigente); si
     * cancela, no se guarda nada. Los conflictos se vuelven a calcular al
     * confirmar, no se confía en datos enviados desde el navegador.
     */
    public static function confirmarBajaDuplicadoAction(): Action
    {
        return Action::make('confirmarBajaDuplicado')
            ->extraAttributes(['style' => 'display: none;'])
            ->modalHeading('Ya existe un registro activo')
            ->modalDescription(function ($livewire): HtmlString {
                $lineas = self::conflictosDe($livewire)->map(function (array $c) {
                    /** @var EquipoCelular $r */
                    $r = $c['registro'];
                    $equipo = trim(($r->marca?->nombre ?? '') . ' ' . $r->modelo);

                    return '<li><b>' . e($r->nombre_usuario) . '</b> — ' . e(implode(' y ', $c['motivos']))
                        . ($equipo !== '' ? ' (' . e($equipo) . ')' : '') . '</li>';
                })->implode('');

                return new HtmlString(
                    '<p>Estos registros siguen activos y coinciden con el que estás guardando:</p>'
                    . '<ul style="list-style: disc; margin: .5rem 0 .5rem 1.25rem;">' . $lineas . '</ul>'
                    . '<p>Si continúas, <b>pasarán a status Baja</b> y quedará vigente el registro que estás guardando.</p>'
                );
            })
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalSubmitActionLabel('Sí, dar de baja el anterior y guardar')
            ->modalCancelActionLabel('No, cancelar')
            ->color('danger')
            ->action(function (array $arguments, $livewire) {
                foreach (self::conflictosDe($livewire) as $conflicto) {
                    $conflicto['registro']->update([
                        'tipo_movimiento' => 'baja',
                        'fecha_baja' => now(),
                    ]);
                }

                if (method_exists($livewire, 'create')) {
                    $livewire->create($arguments['another'] ?? false);
                } elseif (method_exists($livewire, 'save')) {
                    $livewire->save();
                }
            });
    }

    /**
     * @return Collection<int, array{registro: EquipoCelular, motivos: array<int, string>}>
     */
    public static function conflictosDe($livewire): Collection
    {
        $record = $livewire instanceof EditRecord ? $livewire->getRecord() : null;

        return self::buscarConflictos($livewire->data ?? [], $record);
    }
}
