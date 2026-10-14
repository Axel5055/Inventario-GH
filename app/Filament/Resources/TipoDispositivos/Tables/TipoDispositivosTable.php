<?php

namespace App\Filament\Resources\TipoDispositivos\Tables;

use App\Models\EntregaDispositivo;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TipoDispositivosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('activo')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('registros')
                    ->label('Registros')
                    ->state(fn ($record) => EntregaDispositivo::where('tipo_dispositivo', $record->nombre)->count()),
            ])
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->defaultSort('nombre')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn ($record) => EntregaDispositivo::where('tipo_dispositivo', $record->nombre)->exists()
                        ? 'Este tipo ya se usó en registros de dispositivos: esos registros conservarán el nombre, pero el tipo dejará de aparecer para elegir. Si solo quieres ocultarlo, mejor desactívalo.'
                        : '¿Seguro que quieres eliminar este tipo de dispositivo?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
