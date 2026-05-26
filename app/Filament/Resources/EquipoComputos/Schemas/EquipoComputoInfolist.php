<?php

namespace App\Filament\Resources\EquipoComputos\Schemas;

use App\Models\EquipoComputo;
use Carbon\Carbon;
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
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class EquipoComputoInfolist
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

                                    Placeholder::make('estado_responsiva')
                                        ->hiddenLabel()
                                        ->size(TextSize::Large)
                                        ->content(
                                            fn($record) =>
                                            blank($record->responsiva_pdf) ? 'Sin responsiva' : ''
                                        )
                                        ->badge()
                                        ->color(
                                            fn($record) =>
                                            blank($record->responsiva_pdf) ? 'danger' : 'success'
                                        )
                                        ->alignEnd(),

                                    Actions::make([
                                        Action::make('descargar')
                                            ->label('Descargar Responsiva')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('success')
                                            ->visible(fn($record) => filled($record->responsiva_pdf))
                                            ->action(fn($record) => response()->download(Storage::path($record->responsiva_pdf))),
                                    ])->alignEnd()->hiddenLabel(),
                                ]),

                            // --- Encabezado de estado ---
                            Section::make('Estado del Registro')
                                ->schema([
                                    Grid::make(3)
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
                                                ->label('Fecha de entrega')
                                                ->date('d \d\e F \d\e Y')
                                                ->badge()
                                                ->color('gray')
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
                                ])
                                ->compact(),

                            // --- Datos del usuario ---
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
                                        ->copyMessageDuration(1500)
                                        ->badge()
                                        ->color('info')
                                        ->icon(Heroicon::Envelope),

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

                            // --- Observaciones ---
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
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([

                            Section::make('Ficha Técnica del Equipo')
                                ->icon('heroicon-o-cpu-chip')
                                ->columns(4)
                                ->schema([
                                    TextEntry::make('tipo_equipo')
                                        ->label('Tipo de Equipo')
                                        ->badge()
                                        ->color('primary')
                                        ->icon('heroicon-o-computer-desktop')
                                        ->formatStateUsing(fn($state) => match ($state) {
                                            'laptop'      => 'Laptop',
                                            'desktop'     => 'Desktop / PC',
                                            'all_in_one'  => 'All in One',
                                            'workstation' => 'Workstation',
                                            'mini_pc'     => 'Mini PC',
                                            default       => 'Desconocido',
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

                                    TextEntry::make('numero_serie')
                                        ->label('Número de Serie')
                                        ->badge()
                                        ->color('warning')
                                        ->copyable()
                                        ->copyMessage('¡Número de serie copiado!')
                                        ->copyMessageDuration(1500)
                                        ->icon('heroicon-o-identification'),
                                ]),

                            // Componentes del equipo
                            Grid::make(3)
                                ->schema([
                                    Section::make('Procesador')
                                        ->icon('heroicon-o-cpu-chip')
                                        ->schema([
                                            TextEntry::make('procesador')
                                                ->hiddenLabel()
                                                ->placeholder('No especificado')
                                                ->icon('heroicon-o-cpu-chip'),
                                        ]),

                                    Section::make('Memoria RAM')
                                        ->icon('heroicon-o-server')
                                        ->schema([
                                            TextEntry::make('ram')
                                                ->hiddenLabel()
                                                ->badge()
                                                ->color('info')
                                                ->placeholder('No especificado'),
                                        ]),

                                    Section::make('Almacenamiento')
                                        ->icon('heroicon-o-circle-stack')
                                        ->schema([
                                            TextEntry::make('almacenamiento')
                                                ->hiddenLabel()
                                                ->badge()
                                                ->color('success')
                                                ->placeholder('No especificado'),
                                        ]),
                                ]),

                            // Accesos
                            Section::make('Accesos del Equipo')
                                ->icon('heroicon-o-key')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('usuario_equipo')
                                        ->label('Usuario del Equipo')
                                        ->icon('heroicon-o-user-circle')
                                        ->copyable()
                                        ->copyMessage('¡Usuario copiado!')
                                        ->placeholder('No configurado'),

                                    Actions::make([
                                        Action::make('ver_password')
                                            ->label('Ver Contraseña / PIN')
                                            ->icon('heroicon-o-eye')
                                            ->color('warning')
                                            ->modalHeading('🔑 Contraseña del Equipo')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Cerrar')
                                            ->modalWidth('sm')
                                            ->modalContent(fn($record) => new HtmlString("
                                                <div style='
                                                    background: #1e293b;
                                                    border-radius: 12px;
                                                    padding: 28px;
                                                    text-align: center;
                                                    margin: 12px 0;
                                                    border: 1px solid #334155;
                                                '>
                                                    <p style='color:#94a3b8; font-size:12px; margin-bottom:8px; text-transform:uppercase; letter-spacing:2px;'>
                                                        PIN / Contraseña
                                                    </p>
                                                    <p style='
                                                        color: #f1f5f9;
                                                        font-size: 28px;
                                                        font-weight: 700;
                                                        letter-spacing: 6px;
                                                        font-family: monospace;
                                                        margin: 0;
                                                    '>
                                                        {$record->pin_password}
                                                    </p>
                                                </div>
                                            ")),
                                    ])
                                        ->label('Contraseña / PIN')
                                        ->visible(fn($record) => filled($record->pin_password)),
                                ]),
                        ]),

                    // =========================================================
                    // TAB 3 — SOFTWARE Y SEGURIDAD
                    // =========================================================
                    Tab::make('Software y Seguridad')
                        ->icon('heroicon-o-shield-check')
                        ->schema([

                            // Antivirus
                            Section::make('Antivirus')
                                ->icon('heroicon-o-shield-check')
                                ->columns(4)
                                ->schema([
                                    TextEntry::make('antivirus_nombre')
                                        ->label('Antivirus Instalado')
                                        ->badge()
                                        ->color('info')
                                        ->icon('heroicon-o-shield-check')
                                        ->placeholder('Sin antivirus'),

                                    TextEntry::make('antivirus_fecha_instalacion')
                                        ->label('Fecha de Instalación')
                                        ->date('d/m/Y')
                                        ->icon('heroicon-o-calendar')
                                        ->placeholder('—'),

                                    TextEntry::make('antivirus_vigencia')
                                        ->label('Fecha de Vigencia')
                                        ->date('d/m/Y')
                                        ->icon('heroicon-o-calendar-days')
                                        ->placeholder('—'),

                                    TextEntry::make('antivirus_vigencia')
                                        ->label('Estado de Vigencia')
                                        ->state(function ($record) {
                                            if (! $record->antivirus_vigencia) return 'Sin fecha';
                                            $dias = now()->startOfDay()
                                                ->diffInDays(Carbon::parse($record->antivirus_vigencia)->startOfDay(), false);
                                            if ($dias < 0) return 'Vencido hace ' . abs($dias) . ' días';
                                            if ($dias === 0) return 'Vence hoy';
                                            return $dias . ' días restantes';
                                        })
                                        ->badge()
                                        ->color(function ($record) {
                                            if (! $record->antivirus_vigencia) return 'gray';
                                            $dias = now()->startOfDay()
                                                ->diffInDays(Carbon::parse($record->antivirus_vigencia)->startOfDay(), false);
                                            return match (true) {
                                                $dias < 0   => 'danger',
                                                $dias <= 30 => 'warning',
                                                default     => 'success',
                                            };
                                        })
                                        ->icon(function ($record) {
                                            if (! $record->antivirus_vigencia) return 'heroicon-o-question-mark-circle';
                                            $dias = now()->startOfDay()
                                                ->diffInDays(Carbon::parse($record->antivirus_vigencia)->startOfDay(), false);
                                            return match (true) {
                                                $dias < 0   => 'heroicon-o-x-circle',
                                                $dias <= 30 => 'heroicon-o-exclamation-triangle',
                                                default     => 'heroicon-o-check-circle',
                                            };
                                        }),
                                ]),

                            // Windows + Office en grid lado a lado
                            Grid::make(2)
                                ->schema([
                                    Section::make('Windows')
                                        ->icon('heroicon-o-computer-desktop')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('windows_version')
                                                ->label('Versión')
                                                ->badge()
                                                ->color('info')
                                                ->placeholder('No registrado'),

                                            TextEntry::make('windows_key')
                                                ->label('Clave de Activación')
                                                ->icon('heroicon-o-key')
                                                ->copyable()
                                                ->copyMessage('¡Clave copiada!')
                                                ->placeholder('No registrada'),
                                        ]),

                                    Section::make('Microsoft Office')
                                        ->icon('heroicon-o-document-duplicate')
                                        ->columns(3)
                                        ->schema([
                                            TextEntry::make('office_version')
                                                ->label('Versión')
                                                ->badge()
                                                ->color('info')
                                                ->placeholder('No registrado'),

                                            TextEntry::make('office_serie')
                                                ->label('Serie')
                                                ->copyable()
                                                ->copyMessage('¡Serie copiada!')
                                                ->placeholder('—'),

                                            TextEntry::make('office_clave')
                                                ->label('Clave')
                                                ->icon('heroicon-o-key')
                                                ->copyable()
                                                ->copyMessage('¡Clave copiada!')
                                                ->placeholder('—'),
                                        ]),
                                ]),

                            // Correo y BitLocker
                            Section::make('Correo Outlook y BitLocker')
                                ->icon('heroicon-o-lock-closed')
                                ->columns(3)
                                ->collapsed()
                                ->schema([
                                    TextEntry::make('outlook_correo')
                                        ->label('Correo Outlook')
                                        ->icon(Heroicon::Envelope)
                                        ->copyable()
                                        ->copyMessage('¡Correo copiado!')
                                        ->placeholder('No registrado'),

                                    TextEntry::make('outlook_correo_recuperacion')
                                        ->label('Correo de Recuperación')
                                        ->icon('heroicon-o-envelope-open')
                                        ->copyable()
                                        ->placeholder('No registrado'),

                                    TextEntry::make('bitlocker_key')
                                        ->label('Clave BitLocker')
                                        ->icon('heroicon-o-lock-closed')
                                        ->copyable()
                                        ->copyMessage('¡Clave BitLocker copiada!')
                                        ->placeholder('No registrado'),
                                ]),
                        ]),

                    // =========================================================
                    // TAB 4 — HISTÓRICO
                    // =========================================================
                    Tab::make('Histórico')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Section::make('Historial de Equipos Asignados')
                                ->icon('heroicon-o-clock')
                                ->description(
                                    fn($record) =>
                                    '📋 Total de registros del usuario: ' .
                                        EquipoComputo::where('correo_electronico', $record->correo_electronico)->count()
                                )
                                ->schema([
                                    RepeatableEntry::make('historial_equipos')
                                        ->hiddenLabel()
                                        ->state(function ($record) {
                                            return EquipoComputo::query()
                                                ->where('correo_electronico', $record->correo_electronico)
                                                ->orderByDesc('fecha_entrega')
                                                ->get()
                                                ->map(fn($item) => [
                                                    'id'              => $item->id,
                                                    'tipo_movimiento' => $item->tipo_movimiento,
                                                    'fecha_entrega'   => $item->fecha_entrega,
                                                    'marca'           => optional($item->marca)->nombre ?? '—',
                                                    'modelo'          => $item->modelo,
                                                    'numero_serie'    => $item->numero_serie,
                                                    'activo'          => $item->activo,
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

                                            TextEntry::make('marca')
                                                ->label('Marca')
                                                ->icon('heroicon-o-tag')
                                                ->placeholder('—'),

                                            TextEntry::make('modelo')
                                                ->label('Modelo')
                                                ->icon('heroicon-o-computer-desktop')
                                                ->placeholder('—'),

                                            TextEntry::make('numero_serie')
                                                ->label('No. Serie')
                                                ->icon('heroicon-o-identification')
                                                ->copyable()
                                                ->copyMessage('¡Serie copiada!')
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
                                                    'filament.admin.resources.equipo-computos.view',
                                                    $state
                                                ))
                                                ->badge()
                                                ->icon('heroicon-o-eye')
                                                ->color('primary')
                                                ->formatStateUsing(fn() => 'Ver detalle'),
                                        ])
                                        ->columns(7),
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
                                        ->visible(fn(EquipoComputo $record): bool => $record->trashed()),
                                ]),
                        ]),

                ]),
        ]);
    }
}
