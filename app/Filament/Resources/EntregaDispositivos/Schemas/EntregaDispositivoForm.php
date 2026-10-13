<?php

namespace App\Filament\Resources\EntregaDispositivos\Schemas;

use App\Support\AltaRapidaCatalogo;
use App\Support\OpcionesOrdenadas;
use App\Models\Area;
use App\Models\TipoDispositivo;
use App\Models\Marca;
use App\Models\RazonSocial;
use App\Models\Sucursal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EntregaDispositivoForm
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
                                                    'entrega'    => '📦 Entrega',
                                                    'devolucion' => '↩️ Devolución',
                                                ]))
                                                ->default('entrega')
                                                ->required()
                                                ->native(false),

                                            DatePicker::make('fecha_entrega')
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
                                                ->label('Nombre Completo del Usuario')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(
                                                    fn($state, $set) =>
                                                    $set('usuario_referencia', strtolower(str_replace(' ', '.', $state ?? '')))
                                                ),

                                            TextInput::make('correo_electronico')
                                                ->label('Correo Electrónico')
                                                ->email()
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),

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
                    //  TAB 2 · Dispositivo
                    // ─────────────────────────────────────────────
                    Tab::make('Dispositivo')
                        ->icon('heroicon-o-device-tablet')
                        ->schema([

                            Grid::make(3)
                                ->schema([

                                    // ── Detalle del dispositivo (2/3 del ancho) ──
                                    Section::make('Detalle del Dispositivo')
                                        ->icon('heroicon-o-cube')
                                        ->columnSpan(2)
                                        ->columns(2)
                                        ->schema([

                                            Select::make('tipo_dispositivo')
                                                ->label('Tipo de Dispositivo')
                                                // Catálogo editable: si el tipo no existe se agrega con el "+".
                                                ->options(fn ($get) => OpcionesOrdenadas::alfabetico(
                                                    TipoDispositivo::where('activo', true)->pluck('nombre', 'nombre')
                                                        ->when(filled($get('tipo_dispositivo')), fn ($t) => $t->put($get('tipo_dispositivo'), $get('tipo_dispositivo')))
                                                ))
                                                ->required()
                                                ->native(false)
                                                ->searchable()
                                                ->live()
                                                ->createOptionForm([
                                                    TextInput::make('nombre')
                                                        ->label('Nombre del Tipo de Dispositivo')
                                                        ->required()
                                                        ->maxLength(100),
                                                ])
                                                ->createOptionUsing(fn (array $data) => AltaRapidaCatalogo::crear(
                                                    TipoDispositivo::class,
                                                    $data,
                                                    'el tipo de dispositivo',
                                                    llave: 'nombre',
                                                )),

                                            TextInput::make('descripcion')
                                                ->label('Descripción')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Ej: Disco duro externo 1TB, Cable HDMI 2m...'),

                                            // Todas las marcas del catálogo (sin filtrar por categoría). Se guarda el
                                            // nombre, así que se conserva la marca de registros anteriores aunque ya
                                            // no esté en el catálogo.
                                            Select::make('marca')
                                                ->label('Marca')
                                                ->options(fn ($get) => OpcionesOrdenadas::alfabetico(
                                                    Marca::activas()->pluck('nombre', 'nombre')
                                                        ->when(filled($get('marca')), fn ($m) => $m->put($get('marca'), $get('marca')))
                                                ))
                                                ->native(false)
                                                ->searchable()
                                                ->placeholder('Selecciona o agrega una marca')
                                                ->createOptionForm([
                                                    TextInput::make('nombre')
                                                        ->label('Nombre de la Marca')
                                                        ->required()
                                                        ->maxLength(100)
                                                        ->helperText('Quedará como «pendiente»: un administrador definirá después si también aplica a equipos de cómputo o celulares.'),
                                                ])
                                                ->createOptionUsing(fn (array $data) => AltaRapidaCatalogo::crear(
                                                    Marca::class,
                                                    $data + ['categoria' => 'pendiente'],
                                                    'la marca',
                                                    llave: 'nombre',
                                                )),

                                            TextInput::make('modelo')
                                                ->label('Modelo')
                                                ->maxLength(150)
                                                ->placeholder('Ej: T7 Shield, MX Master 3...'),

                                            TextInput::make('numero_serie')
                                                ->label('Número de Serie')
                                                ->maxLength(200)
                                                ->placeholder('Opcional')
                                                ->columnSpanFull(),

                                            Textarea::make('observaciones')
                                                ->label('Observaciones')
                                                ->placeholder('Estado del dispositivo, condiciones de entrega, etc.')
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ]),

                                    // ── Panel lateral derecho: responsiva (1/3 del ancho) ──
                                    Section::make('Responsiva')
                                        ->icon('heroicon-o-document-text')
                                        ->columnSpan(1)
                                        ->schema([
                                            FileUpload::make('responsiva_pdf')
                                                ->label('Cargar PDF')
                                                ->acceptedFileTypes(['application/pdf'])
                                                ->directory('responsivas/dispositivos')
                                                ->disk('local')
                                                ->maxSize(10240)
                                                ->getUploadedFileNameForStorageUsing(
                                                    fn($get) => Str::slug($get('nombre_usuario') ?: 'equipo') . '-' . Str::random(6) . '.pdf'
                                                )
                                                ->downloadable()
                                                ->openable()
                                                ->helperText('Opcional. Máximo 10MB.'),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }
}
