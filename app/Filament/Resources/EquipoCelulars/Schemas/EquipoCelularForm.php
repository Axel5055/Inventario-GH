<?php

namespace App\Filament\Resources\EquipoCelulars\Schemas;

use App\Models\Area;
use App\Models\EquipoCelular;
use App\Models\Marca;
use App\Models\RazonSocial;
use App\Models\Sucursal;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

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
                                                ->options([
                                                    'alta'              => '🟢 Alta',
                                                    'baja'              => '🔴 Baja',
                                                    'cambio_equipo'     => '🔄 Cambio de Equipo',
                                                    'reasignacion'      => '🔁 Reasignación',
                                                    'mantenimiento'     => '🔧 Mantenimiento',
                                                    'prestamo_temporal' => '⏱ Préstamo Temporal',
                                                ])
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
                                                ->options(RazonSocial::where('activo', true)->pluck('nombre', 'id'))
                                                ->required()
                                                ->native(false)
                                                ->createOptionForm([
                                                    TextInput::make('nombre')->required(),
                                                    TextInput::make('rfc')->required(),
                                                ]),
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

                                            TextInput::make('puesto')
                                                ->label('Puesto')
                                                ->maxLength(255),

                                            Select::make('sucursal_id')
                                                ->label('Sucursal')
                                                ->relationship('sucursal', 'nombre')
                                                ->options(Sucursal::where('activo', true)->pluck('nombre', 'id'))
                                                ->required()
                                                ->native(false)
                                                ->searchable()
                                                ->createOptionForm([
                                                    TextInput::make('nombre')->required(),
                                                    TextInput::make('ciudad')->required(),
                                                ]),

                                            Select::make('area_id')
                                                ->label('Área')
                                                ->relationship('area', 'nombre')
                                                ->options(Area::where('activo', true)->pluck('nombre', 'id'))
                                                ->required()
                                                ->native(false)
                                                ->createOptionForm([
                                                    TextInput::make('nombre')->required(),
                                                ]),

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
                                                ->options([
                                                    'celular' => '📱 Celular',
                                                    'tablet'  => '📟 Tablet',
                                                    'ipad'    => '🍎 iPad',
                                                    'otro'    => '📦 Otro',
                                                ])
                                                ->required()
                                                ->native(false),

                                            Select::make('marca_id')
                                                ->label('Marca')
                                                ->relationship('marca', 'nombre')
                                                ->options(
                                                    Marca::activas()->deCelular()->pluck('nombre', 'id')
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
                                                        ->options([
                                                            'ambas'   => 'Ambas',
                                                            'computo' => 'Equipo de Cómputo',
                                                            'celular' => 'Celular',
                                                        ])
                                                        ->required(),
                                                ]),

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
                                                ->helperText('El mismo IMEI se puede reutilizar si el equipo anterior con ese IMEI ya está de baja (equipo reciclado a otra persona).')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, ?EquipoCelular $record) {
                                                    self::avisarSiImeiActivo($state, $record);
                                                })
                                                ->rules([
                                                    fn(?EquipoCelular $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                                        $conflicto = self::buscarConflictoImei($value, $record);

                                                        if ($conflicto) {
                                                            $fail("Ya existe un equipo ACTIVO con este IMEI, asignado a «{$conflicto->nombre_usuario}». Debes darlo de baja antes de reutilizarlo.");
                                                        }
                                                    },
                                                ]),

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
                                                ->downloadable()
                                                ->openable(),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }

    /**
     * Un mismo IMEI es válido en varios registros históricos (equipo
     * reciclado a otra persona), pero solo uno puede estar activo
     * (tipo_movimiento distinto de 'baja') a la vez.
     */
    private static function buscarConflictoImei(?string $imei, ?EquipoCelular $record): ?EquipoCelular
    {
        if (blank($imei)) {
            return null;
        }

        return EquipoCelular::query()
            ->where('imei', $imei)
            ->where('tipo_movimiento', '!=', 'baja')
            ->when($record, fn($query) => $query->whereKeyNot($record->getKey()))
            ->first();
    }

    private static function avisarSiImeiActivo(?string $imei, ?EquipoCelular $record): void
    {
        $conflicto = self::buscarConflictoImei($imei, $record);

        if (! $conflicto) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('IMEI en uso')
            ->body("Ya existe un equipo ACTIVO con este IMEI, asignado a «{$conflicto->nombre_usuario}». Puedes darlo de baja para reutilizarlo en este nuevo registro.")
            ->persistent()
            ->actions([
                NotificationAction::make('dar_de_baja_equipo_celular_' . $conflicto->id)
                    ->label('Dar de baja ese equipo')
                    ->color('danger')
                    ->button()
                    ->action(function () use ($conflicto) {
                        $conflicto->update([
                            'tipo_movimiento' => 'baja',
                            'fecha_baja' => now(),
                        ]);

                        Notification::make()
                            ->title('Equipo anterior dado de baja')
                            ->success()
                            ->send();
                    }),
            ])
            ->send();
    }
}
