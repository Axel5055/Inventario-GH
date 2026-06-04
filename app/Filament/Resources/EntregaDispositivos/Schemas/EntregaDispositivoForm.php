<?php

namespace App\Filament\Resources\EntregaDispositivos\Schemas;

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
                                                ->options([
                                                    'entrega'    => '📦 Entrega',
                                                    'devolucion' => '↩️ Devolución',
                                                ])
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
                                        ->schema([

                                            TextInput::make('nombre_usuario')
                                                ->label('Nombre Completo del Usuario')
                                                ->required()
                                                ->maxLength(255)
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(
                                                    fn($state, $set) =>
                                                    $set('usuario_referencia', strtolower(str_replace(' ', '.', $state ?? '')))
                                                ),

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
                                                        ->native(false),
                                                    TextInput::make('nombre')->required(),
                                                    TextInput::make('ciudad')->required(),
                                                ]),

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
                                                ->options([
                                                    'disco_duro' => '💾 Disco Duro',
                                                    'cable'      => '🔌 Cable',
                                                    'accesorio'  => '🧩 Accesorio',
                                                    'teclado'    => '⌨️ Teclado',
                                                    'mouse'      => '🖱️ Mouse',
                                                    'monitor'    => '🖥️ Monitor',
                                                    'otro'       => '📦 Otro',
                                                ])
                                                ->required()
                                                ->native(false)
                                                ->live(),

                                            TextInput::make('descripcion')
                                                ->label('Descripción')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Ej: Disco duro externo 1TB, Cable HDMI 2m...'),

                                            TextInput::make('marca')
                                                ->label('Marca')
                                                ->maxLength(100)
                                                ->placeholder('Ej: Samsung, Logitech...'),

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
                                                ->disk('public')
                                                ->maxSize(10240)
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
