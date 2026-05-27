<?php

namespace App\Filament\Resources\EquipoComputos\Schemas;

use App\Models\Area;
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
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EquipoComputoForm
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

                                    // ── Columna izquierda: movimiento y fechas ──
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
                                                ->required()
                                                ->native(false),

                                            DateTimePicker::make('fecha_entrega')
                                                ->label('Fecha de Entrega')
                                                ->required()
                                                ->seconds(false)
                                                ->default(now())
                                                ->displayFormat('d/m/Y'),

                                            Select::make('razon_social_id')
                                                ->label('Razón Social')
                                                ->relationship('razonSocial', 'nombre')
                                                ->options(RazonSocial::where('activo', true)->pluck('nombre', 'id'))
                                                ->required()
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(fn($set) => $set('sucursal_id', null))
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

                                            TextInput::make('correo_electronico')
                                                ->label('Correo Electrónico')
                                                ->email()
                                                ->required()
                                                ->maxLength(255)
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn($state, $set) => $set('usuario_referencia', $state)),

                                            TextInput::make('puesto')
                                                ->label('Puesto')
                                                ->maxLength(255),

                                            Select::make('sucursal_id')
                                                ->label('Sucursal')
                                                ->relationship('sucursal', 'nombre')
                                                ->options(Sucursal::where('activo', true)->pluck('nombre', 'id'))
                                                ->required()
                                                ->native(false)
                                                ->live()
                                                ->createOptionForm([
                                                    Select::make('razon_social_id')
                                                        ->label('Razón Social')
                                                        ->options(RazonSocial::where('activo', true)->pluck('nombre', 'id'))
                                                        ->required()
                                                        ->native(false)
                                                        ->live()
                                                        ->afterStateUpdated(fn($set) => $set('sucursal_id', null)),
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
                        ->icon('heroicon-o-computer-desktop')
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
                                                    'laptop'      => 'Laptop',
                                                    'escritorio'  => 'Desktop / PC',
                                                    'all_in_one'  => 'All in One',
                                                    'workstation' => 'Workstation',
                                                    'mini_pc'     => 'Mini PC',
                                                ])
                                                ->required()
                                                ->native(false),

                                            Select::make('marca_id')
                                                ->label('Marca')
                                                ->relationship('marca', 'nombre')
                                                ->options(Marca::activas()->deComputo()->pluck('nombre', 'id'))
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

                                            TextInput::make('numero_serie')
                                                ->label('Número de Serie')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(200),

                                            TextInput::make('procesador')
                                                ->label('Procesador')
                                                ->maxLength(200),

                                            TextInput::make('ram')
                                                ->label('RAM')
                                                ->placeholder('Ej: 16 GB DDR4')
                                                ->maxLength(100),

                                            TextInput::make('almacenamiento')
                                                ->label('Almacenamiento')
                                                ->placeholder('Ej: 512 GB SSD NVMe')
                                                ->maxLength(200)
                                                ->columnSpan(1),

                                            Textarea::make('observaciones')
                                                ->label('Observaciones')
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ]),

                                    // ── Panel lateral derecho (1/3 del ancho) ──
                                    Grid::make(1)
                                        ->columnSpan(1)
                                        ->schema([

                                            Section::make('Accesos')
                                                ->icon('heroicon-o-key')
                                                ->schema([
                                                    TextInput::make('usuario_equipo')
                                                        ->label('Usuario del Equipo')
                                                        ->maxLength(150),

                                                    TextInput::make('pin_password')
                                                        ->label('PIN / Contraseña')
                                                        ->password()
                                                        ->revealable()
                                                        ->maxLength(255),
                                                ]),

                                            Section::make('Responsiva')
                                                ->icon('heroicon-o-document-text')
                                                ->schema([
                                                    FileUpload::make('responsiva_pdf')
                                                        ->label('Cargar PDF')
                                                        ->acceptedFileTypes(['application/pdf'])
                                                        ->maxSize(10240)
                                                        ->downloadable()
                                                        ->openable(),
                                                ]),
                                        ]),
                                ]),
                        ]),

                    // ─────────────────────────────────────────────
                    //  TAB 3 · Software y Seguridad
                    // ─────────────────────────────────────────────
                    Tab::make('Software y Seguridad')
                        ->icon('heroicon-o-shield-check')
                        ->schema([

                            Grid::make(2)
                                ->schema([

                                    // ── Sistema Operativo ──
                                    Section::make('Sistema Operativo')
                                        ->icon('heroicon-o-window')
                                        ->columnSpan(1)
                                        ->schema([
                                            Select::make('windows_version')
                                                ->label('Versión de Windows')
                                                ->options([
                                                    'Windows 10 Home'       => 'Windows 10 Home',
                                                    'Windows 10 Pro'        => 'Windows 10 Pro',
                                                    'Windows 11 Home'       => 'Windows 11 Home',
                                                    'Windows 11 Pro'        => 'Windows 11 Pro',
                                                    'Windows 11 Enterprise' => 'Windows 11 Enterprise',
                                                ])
                                                ->required()
                                                ->native(false),

                                            TextInput::make('windows_key')
                                                ->label('Clave / Key de Windows')
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),
                                        ]),

                                    // ── Antivirus ──
                                    Section::make('Antivirus')
                                        ->icon('heroicon-o-bug-ant')
                                        ->columnSpan(1)
                                        ->schema([
                                            Select::make('antivirus_nombre')
                                                ->label('Antivirus')
                                                ->options([
                                                    'Windows Defender' => 'Windows Defender',
                                                    'Avast'            => 'Avast',
                                                    'Norton'           => 'Norton',
                                                    'Malwarebytes'     => 'Malwarebytes',
                                                ])
                                                ->native(false),

                                            Grid::make(2)
                                                ->schema([
                                                    DatePicker::make('antivirus_fecha_instalacion')
                                                        ->label('Fecha de Instalación')
                                                        ->seconds(false),

                                                    DatePicker::make('antivirus_vigencia')
                                                        ->label('Vigencia')
                                                        ->seconds(false),
                                                ]),
                                        ]),

                                    // ── Microsoft Office (ancho completo) ──
                                    Section::make('Microsoft Office')
                                        ->icon('heroicon-o-document-duplicate')
                                        ->columnSpanFull()
                                        ->columns(3)
                                        ->schema([
                                            Select::make('office_version')
                                                ->label('Versión de Office')
                                                ->options([
                                                    'Microsoft 365' => 'Microsoft 365',
                                                    'Office 2021'   => 'Office 2021',
                                                    'Office 2019'   => 'Office 2019',
                                                    'Office 2016'   => 'Office 2016',
                                                    'Sin Office'    => 'Sin Office',
                                                ])
                                                ->required()
                                                ->native(false),

                                            TextInput::make('correo_office')
                                                ->label('Correo de la Cuenta')
                                                ->email()
                                                ->maxLength(255)
                                                ->live(debounce: 500),

                                            TextInput::make('office_clave')
                                                ->label('Clave de Office')
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),
                                        ]),
                                ]),

                            // ── Sección colapsable: Outlook + BitLocker ──
                            Section::make('Outlook y BitLocker')
                                ->icon('heroicon-o-lock-closed')
                                ->description('Campos opcionales para cuentas de correo y cifrado de disco.')
                                ->columnSpanFull()
                                ->collapsed()
                                ->columns(2)
                                ->schema([

                                    Fieldset::make('Cuenta de Outlook')
                                        ->columns(3)
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('outlook_correo')
                                                ->label('Correo Outlook')
                                                ->email()
                                                ->maxLength(255),

                                            TextInput::make('outlook_password')
                                                ->label('Contraseña')
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),

                                            TextInput::make('outlook_correo_recuperacion')
                                                ->label('Correo de Recuperación')
                                                ->email()
                                                ->maxLength(255),
                                        ]),

                                    Fieldset::make('BitLocker')
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('bitlocker_key')
                                                ->label('Clave de Recuperación BitLocker')
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }
}
