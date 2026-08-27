<?php

namespace App\Filament\Resources\EquipoCelulars\Schemas;

use App\Models\EquipoCelular;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Storage;

class EquipoCelularInfolist
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
                                            ->action(fn($record) => response()->download(
                                                Storage::disk('local')->path($record->responsiva_pdf),
                                                preg_replace('/[\/\\\\:*?"<>|]/', '', $record->nombre_usuario) . '.pdf'
                                            )),
                                    ])->alignEnd()->hiddenLabel(),
                                ]),

                            Section::make('Estado del Registro')
                                ->compact()
                                ->schema([
                                    Grid::make(4)
                                        ->schema([

                                            TextEntry::make('tipo_movimiento')
                                                ->label('Status')
                                                ->badge()
                                                ->size(TextSize::Large)
                                                ->color(fn($state) => match ($state) {
                                                    'alta'              => 'success',
                                                    'baja'              => 'danger',
                                                    'cambio_equipo'     => 'warning',
                                                    'reasignacion'      => 'warning',
                                                    'mantenimiento'     => 'info',
                                                    'prestamo_temporal' => 'info',
                                                    default             => 'gray',
                                                })
                                                ->formatStateUsing(fn($state) => match ($state) {
                                                    'alta'              => '🟢 Alta',
                                                    'baja'              => '🔴 Baja',
                                                    'cambio_equipo'     => '🔄 Cambio de Equipo',
                                                    'reasignacion'      => '🔁 Reasignación',
                                                    'mantenimiento'     => '🔧 Mantenimiento',
                                                    'prestamo_temporal' => '⏱ Préstamo Temporal',
                                                    default             => ucfirst($state),
                                                }),

                                            TextEntry::make('fecha_entrega')
                                                ->label('Fecha de Entrega')
                                                ->dateTime('d \d\e F \d\e Y h:i A')
                                                ->badge()
                                                ->color('gray')
                                                ->icon('heroicon-o-calendar')
                                                ->size(TextSize::Large)
                                                ->placeholder('Sin fecha registrada'),

                                            TextEntry::make('fecha_baja')
                                                ->label('Fecha Baja')
                                                ->dateTime('d \d\e F \d\e Y h:i A')
                                                ->badge()
                                                ->color('danger')
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
                                ->columns(4)
                                ->schema([

                                    TextEntry::make('nombre_usuario')
                                        ->label('Nombre Completo')
                                        ->icon('heroicon-o-user')
                                        ->weight('bold'),

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
                    // TAB 2 — ESPECIFICACIONES
                    // =========================================================
                    Tab::make('Especificaciones')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->schema([

                            Section::make('Ficha Técnica del Equipo')
                                ->icon('heroicon-o-cpu-chip')
                                ->columns(4)
                                ->schema([

                                    TextEntry::make('tipo_equipo')
                                        ->label('Tipo de Equipo')
                                        ->badge()
                                        ->color('primary')
                                        ->icon('heroicon-o-device-phone-mobile')
                                        ->formatStateUsing(fn($state) => match ($state) {
                                            'celular' => '📱 Celular',
                                            'tablet'  => '📟 Tablet',
                                            'ipad'    => '🍎 iPad',
                                            'otro'    => '📦 Otro',
                                            default   => ucfirst($state),
                                        }),

                                    TextEntry::make('marca.nombre')
                                        ->label('Marca')
                                        ->icon('heroicon-o-tag')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('modelo')
                                        ->label('Modelo')
                                        ->icon('heroicon-o-squares-2x2')
                                        ->placeholder('Sin modelo'),

                                    IconEntry::make('esta_activo')
                                        ->label('Equipo Activo')
                                        ->state(fn($record) => $record->tipo_movimiento !== 'baja' && ! $record->trashed())
                                        ->boolean(),
                                ]),

                            Grid::make(3)
                                ->schema([

                                    Section::make('Número Telefónico')
                                        ->icon('heroicon-o-phone')
                                        ->schema([
                                            TextEntry::make('numero_telefonico')
                                                ->hiddenLabel()
                                                ->icon('heroicon-o-phone')
                                                ->copyable()
                                                ->copyMessage('¡Número copiado!')
                                                ->copyMessageDuration(1500)
                                                ->placeholder('No especificado'),
                                        ]),

                                    Section::make('IMEI')
                                        ->icon('heroicon-o-identification')
                                        ->schema([
                                            TextEntry::make('imei')
                                                ->hiddenLabel()
                                                ->badge()
                                                ->color('warning')
                                                ->copyable()
                                                ->copyMessage('¡IMEI copiado!')
                                                ->copyMessageDuration(1500)
                                                ->placeholder('No especificado'),
                                        ]),

                                    Section::make('ICCID (SIM)')
                                        ->icon('heroicon-o-credit-card')
                                        ->schema([
                                            TextEntry::make('iccid')
                                                ->hiddenLabel()
                                                ->badge()
                                                ->color('info')
                                                ->copyable()
                                                ->copyMessage('¡ICCID copiado!')
                                                ->copyMessageDuration(1500)
                                                ->placeholder('No especificado'),
                                        ]),
                                ]),
                        ]),

                    // =========================================================
                    // TAB 3 — HISTÓRICO
                    // =========================================================
                    Tab::make('Histórico')
                        ->icon('heroicon-o-clock')
                        ->schema([

                            Section::make('Historial de Equipos Asignados')
                                ->icon('heroicon-o-clock')
                                ->description(
                                    fn($record) =>
                                    '📋 Total de registros del usuario: ' .
                                        EquipoCelular::where('nombre_usuario', $record->nombre_usuario)->count()
                                )
                                ->schema([

                                    RepeatableEntry::make('historial_equipos')
                                        ->hiddenLabel()
                                        ->state(function ($record) {
                                            return EquipoCelular::query()
                                                ->where('nombre_usuario', $record->nombre_usuario)
                                                ->orderByDesc('fecha_entrega')
                                                ->get()
                                                ->map(fn($item) => [
                                                    'id'              => $item->id,
                                                    'tipo_movimiento' => $item->tipo_movimiento,
                                                    'fecha_entrega'   => $item->fecha_entrega,
                                                    'tipo_equipo'     => $item->tipo_equipo,
                                                    'marca'           => optional($item->marca)->nombre ?? '—',
                                                    'modelo'          => $item->modelo,
                                                    'imei'            => $item->imei,
                                                    'is_current'      => $item->id === $record->id,
                                                ]);
                                        })
                                        ->schema([

                                            TextEntry::make('tipo_movimiento')
                                                ->label('Movimiento')
                                                ->badge()
                                                ->icon(fn($state) => match ($state) {
                                                    'alta'              => 'heroicon-o-arrow-up-circle',
                                                    'baja'              => 'heroicon-o-arrow-down-circle',
                                                    'cambio_equipo'     => 'heroicon-o-arrows-right-left',
                                                    'reasignacion'      => 'heroicon-o-user-group',
                                                    'mantenimiento'     => 'heroicon-o-wrench-screwdriver',
                                                    'prestamo_temporal' => 'heroicon-o-clock',
                                                    default             => 'heroicon-o-question-mark-circle',
                                                })
                                                ->color(fn($state) => match ($state) {
                                                    'alta'              => 'success',
                                                    'baja'              => 'danger',
                                                    'cambio_equipo'     => 'warning',
                                                    'reasignacion'      => 'warning',
                                                    'mantenimiento'     => 'info',
                                                    'prestamo_temporal' => 'info',
                                                    default             => 'gray',
                                                })
                                                ->formatStateUsing(fn($state) => match ($state) {
                                                    'alta'              => 'Alta',
                                                    'baja'              => 'Baja',
                                                    'cambio_equipo'     => 'Cambio de Equipo',
                                                    'reasignacion'      => 'Reasignación',
                                                    'mantenimiento'     => 'Mantenimiento',
                                                    'prestamo_temporal' => 'Préstamo Temporal',
                                                    default             => ucfirst($state),
                                                }),

                                            TextEntry::make('fecha_entrega')
                                                ->label('Fecha')
                                                ->icon('heroicon-o-calendar')
                                                ->date('d/m/Y'),

                                            TextEntry::make('tipo_equipo')
                                                ->label('Tipo')
                                                ->badge()
                                                ->color('primary')
                                                ->formatStateUsing(fn($state) => match ($state) {
                                                    'celular' => '📱 Celular',
                                                    'tablet'  => '📟 Tablet',
                                                    'ipad'    => '🍎 iPad',
                                                    'otro'    => '📦 Otro',
                                                    default   => ucfirst($state),
                                                }),

                                            TextEntry::make('marca')
                                                ->label('Marca')
                                                ->icon('heroicon-o-tag')
                                                ->placeholder('—'),

                                            TextEntry::make('modelo')
                                                ->label('Modelo')
                                                ->icon('heroicon-o-device-phone-mobile')
                                                ->placeholder('—'),

                                            TextEntry::make('imei')
                                                ->label('IMEI')
                                                ->icon('heroicon-o-identification')
                                                ->copyable()
                                                ->copyMessage('¡IMEI copiado!')
                                                ->placeholder('—'),

                                            TextEntry::make('is_current')
                                                ->label('Estado')
                                                ->badge()
                                                ->icon(
                                                    fn($state) => $state
                                                        ? 'heroicon-o-check-circle'
                                                        : 'heroicon-o-archive-box'
                                                )
                                                ->formatStateUsing(fn($state) => $state ? 'Actual' : 'Histórico')
                                                ->color(fn($state) => $state ? 'success' : 'gray'),

                                            TextEntry::make('id')
                                                ->label('Detalle')
                                                ->visible(fn($state, $get) => ! $get('is_current'))
                                                ->url(fn($state) => route(
                                                    'filament.admin.resources.equipo-celulars.view',
                                                    $state
                                                ))
                                                ->badge()
                                                ->icon('heroicon-o-eye')
                                                ->color('primary')
                                                ->formatStateUsing(fn() => 'Ver detalle'),
                                        ])
                                        ->columns(8),
                                ]),
                        ]),

                    // =========================================================
                    // TAB 4 — AUDITORÍA
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
                                        ->visible(fn(EquipoCelular $record): bool => $record->trashed()),
                                ]),
                        ]),
                ]),
        ]);
    }
}
