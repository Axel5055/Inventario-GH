<?php

namespace App\Filament\Resources\EntregaDispositivos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EntregaDispositivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_usuario')
                    ->searchable(),
                TextColumn::make('usuario_referencia')
                    ->searchable(),
                TextColumn::make('razonSocial.id')
                    ->searchable(),
                TextColumn::make('sucursal.id')
                    ->searchable(),
                TextColumn::make('fecha_entrega')
                    ->date()
                    ->sortable(),
                TextColumn::make('tipo_dispositivo')
                    ->badge(),
                TextColumn::make('descripcion')
                    ->searchable(),
                TextColumn::make('marca')
                    ->searchable(),
                TextColumn::make('modelo')
                    ->searchable(),
                TextColumn::make('numero_serie')
                    ->searchable(),
                TextColumn::make('tipo_movimiento')
                    ->badge(),
                IconColumn::make('activo')
                    ->boolean(),
                TextColumn::make('responsiva_pdf')
                    ->searchable(),
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
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
