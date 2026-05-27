<?php

namespace App\Filament\Resources\EquipoComputos\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EquipoComputosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo_movimiento')
                    ->label('Status')
                    ->badge()
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

                TextColumn::make('fecha_entrega')
                    ->label('Entrega')
                    ->date('d \d\e F \d\e Y')
                    ->sortable(),

                TextColumn::make('nombre_usuario')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('correo_electronico')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('razonSocial.nombre')
                    ->label('Razón Social')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->toggleable(),

                TextColumn::make('tipo_equipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'laptop'      => 'Laptop',
                        'desktop'     => 'Desktop / PC',
                        'all_in_one'  => 'All in One',
                        'workstation' => 'Workstation',
                        'mini_pc'     => 'Mini PC',
                        default       => 'Desconocido',
                    }),

                TextColumn::make('marca.nombre')
                    ->label('Marca')
                    ->searchable(),

                TextColumn::make('numero_serie')
                    ->label('No. Serie')
                    ->searchable()
                    ->copyable(),
            ])
            ->filters([
                SelectFilter::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'alta'              => '🟢 Alta',
                        'baja'              => '🔴 Baja',
                        'cambio_equipo'     => '🔄 Cambio de Equipo',
                        'reasignacion'      => '🔁 Reasignación',
                        'mantenimiento'     => '🔧 Mantenimiento',
                        'prestamo_temporal' => '⏱ Préstamo Temporal',
                    ]),

                SelectFilter::make('razon_social_id')
                    ->label('Razón Social')
                    ->relationship('razonSocial', 'nombre'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('dar_de_baja')
                    ->label('Dar de Baja')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->tipo_movimiento !== 'baja')
                    ->requiresConfirmation()
                    ->modalHeading('Dar de Baja el Equipo')
                    ->modalDescription('Se cambiará el status a "Baja" y se registrará la fecha de baja. Esta acción puede revertirse editando el registro.')
                    ->modalSubmitActionLabel('Confirmar Baja')
                    ->form([
                        DateTimePicker::make('fecha_baja')
                            ->label('Fecha de Baja')
                            ->default(now())
                            ->required()
                            ->seconds(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'tipo_movimiento' => 'baja',
                            'fecha_baja'      => $data['fecha_baja'],
                        ]);

                        Notification::make()
                            ->title('Equipo dado de baja')
                            ->body("El equipo de {$record->nombre_usuario} fue dado de baja correctamente.")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
