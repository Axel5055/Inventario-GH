<?php

namespace App\Filament\Resources\EntregaDispositivos\Schemas;

use App\Models\EntregaDispositivo;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Storage;

class EntregaDispositivoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Tabs::make('Detalles del Registro')
                ->columnSpanFull()
                ->contained(false)
                ->tabs([

                    // =========================================================
                    // TAB 1 — RESUMEN
                    // =========================================================
                    Tab::make('Resumen')
                        ->icon('heroicon-o-identification')
                        ->schema([

                            Grid::make(1)
                                ->schema([
                                    Actions::make([
                                        Action::make('descargar')
                                            ->label('Descargar Responsiva')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('success')
                                            ->visible(fn($record) => filled($record->responsiva_pdf))
                                            ->action(fn($record) => response()->download(Storage::disk('local')->path($record->responsiva_pdf))),
                                    ])->alignEnd()->hiddenLabel(),
                                ]),

                            Section::make('Estado del Registro')
                                ->compact()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([

                                            TextEntry::make('tipo_movimiento')
                                                ->label('Status')
                                                ->badge()
                                                ->size(TextSize::Large)
                                                ->color(fn($state) => match ($state) {
                                                    'entrega'    => 'success',
                                                    'devolucion' => 'warning',
                                                    default      => 'gray',
                                                })
                                                ->formatStateUsing(fn($state) => match ($state) {
                                                    'entrega'    => '📦 Entrega',
                                                    'devolucion' => '↩️ Devolución',
                                                    default      => ucfirst($state),
                                                }),

                                            TextEntry::make('fecha_entrega')
                                                ->label('Fecha')
                                                ->date('d \d\e F \d\e Y')
                                                ->badge()
                                                ->color('success')
                                                ->icon('heroicon-o-calendar')
                                                ->size(TextSize::Large)
                                                ->placeholder('Sin fecha registrada'),

                                            TextEntry::make('razonSocial.nombre')
                                                ->label('Razón Social')
                                                ->icon('heroicon-o-building-office')
                                                ->weight('bold')
                                                ->badge()
                                                ->size(TextSize::Large)
                                                ->placeholder('Sin razón social')
                                                ->wrap(),
                                        ]),
                                ]),

                            Section::make('Datos del Usuario')
                                ->icon('heroicon-o-user-circle')
                                ->columns(5)
                                ->schema([
                                    TextEntry::make('nombre_usuario')
                                        ->label('Nombre Completo')
                                        ->icon('heroicon-o-user')
                                        ->weight('bold'),

                                    TextEntry::make('correo_electronico')
                                        ->label('Correo Electrónico')
                                        ->copyable()
                                        ->copyMessage('¡Copiado!')
                                        ->badge()
                                        ->color('info')
                                        ->icon('heroicon-o-envelope'),

                                    TextEntry::make('sucursal.nombre')
                                        ->label('Sucursal')
                                        ->icon('heroicon-o-map-pin')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('area.nombre')
                                        ->label('Área')
                                        ->icon('heroicon-o-rectangle-group')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('puesto')
                                        ->label('Puesto')
                                        ->icon('heroicon-o-briefcase')
                                        ->placeholder('No especificado'),
                                ]),

                            Section::make('Observaciones')
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->schema([
                                    TextEntry::make('observaciones')
                                        ->hiddenLabel()
                                        ->placeholder('Sin observaciones registradas.')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // =========================================================
                    // TAB 2 — DISPOSITIVO
                    // =========================================================
                    Tab::make('Dispositivo')
                        ->icon('heroicon-o-cube')
                        ->schema([

                            Section::make('Detalle del Dispositivo')
                                ->icon('heroicon-o-cube')
                                ->columns(4)
                                ->schema([
                                    TextEntry::make('tipo_dispositivo')
                                        ->label('Tipo de Dispositivo')
                                        ->badge()
                                        ->color('primary')
                                        ->icon('heroicon-o-cube'),

                                    TextEntry::make('descripcion')
                                        ->label('Descripción')
                                        ->columnSpan(3),

                                    TextEntry::make('marca')
                                        ->label('Marca')
                                        ->icon('heroicon-o-tag')
                                        ->placeholder('—'),

                                    TextEntry::make('modelo')
                                        ->label('Modelo')
                                        ->icon('heroicon-o-squares-2x2')
                                        ->placeholder('Sin modelo'),

                                    TextEntry::make('numero_serie')
                                        ->label('Número de Serie')
                                        ->badge()
                                        ->color('warning')
                                        ->copyable()
                                        ->copyMessage('¡Número de serie copiado!')
                                        ->icon('heroicon-o-identification')
                                        ->placeholder('—'),
                                ]),
                        ]),

                    // =========================================================
                    // TAB 3 — AUDITORÍA
                    // =========================================================
                    Tab::make('Auditoría')
                        ->icon('heroicon-o-shield-check')
                        ->schema([

                            Section::make('Registro del Sistema')
                                ->icon('heroicon-o-calendar-days')
                                ->columns(3)
                                ->schema([

                                    TextEntry::make('usuario_referencia')
                                        ->label('Usuario de Referencia')
                                        ->icon('heroicon-o-finger-print')
                                        ->copyable()
                                        ->copyMessage('¡Copiado!')
                                        ->placeholder('—'),

                                    TextEntry::make('created_at')
                                        ->label('Creado el')
                                        ->icon('heroicon-o-clock')
                                        ->dateTime('d/m/Y H:i')
                                        ->placeholder('—'),

                                    TextEntry::make('updated_at')
                                        ->label('Última Actualización')
                                        ->icon('heroicon-o-pencil-square')
                                        ->dateTime('d/m/Y H:i')
                                        ->placeholder('—'),

                                    TextEntry::make('deleted_at')
                                        ->label('Eliminado el')
                                        ->icon('heroicon-o-trash')
                                        ->dateTime('d/m/Y H:i')
                                        ->placeholder('—')
                                        ->color('danger')
                                        ->badge()
                                        ->visible(fn(EntregaDispositivo $record): bool => $record->trashed()),
                                ]),
                        ]),
                ]),
        ]);
    }
}
