<?php

namespace App\Filament\Resources\EntregaDispositivos\Tables;

use App\Support\OpcionesOrdenadas;
use App\Filament\Actions\DescargarResponsivasAction;
use App\Filament\Actions\ExportarExcelAction;
use App\Services\ReporteInventario;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EntregaDispositivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('estado_revision')
                    ->label('Revisión')
                    ->badge()
                    ->color(fn($state) => $state === 'pendiente' ? 'warning' : 'success')
                    ->formatStateUsing(fn($state) => $state === 'pendiente' ? '🟡 Pendiente' : '✅ Completo')
                    ->toggleable(),

                TextColumn::make('tipo_movimiento')
                    ->label('Movimiento')
                    ->badge()
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
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('area.nombre')
                    ->label('Área')
                    ->toggleable(),
                TextColumn::make('fecha_entrega')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('tipo_dispositivo')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('descripcion')
                    ->searchable(),
                TextColumn::make('marca')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('modelo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('numero_serie')
                    ->label('No. Serie')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creadoPor.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('actualizadoPor.name')
                    ->label('Editado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado_revision')
                    ->label('Revisión')
                    ->options(OpcionesOrdenadas::alfabetico([
                        'pendiente' => '🟡 Pendiente',
                        'completo'  => '✅ Completo',
                    ])),

                SelectFilter::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->options(OpcionesOrdenadas::alfabetico([
                        'entrega'    => '📦 Entrega',
                        'devolucion' => '↩️ Devolución',
                    ])),

                SelectFilter::make('razon_social_id')
                    ->label('Razón Social')
                    ->relationship('razonSocial', 'nombre'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportarExcelAction::enLote(ReporteInventario::DISPOSITIVOS),
                    DescargarResponsivasAction::enLote(ReporteInventario::DISPOSITIVOS),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
