<?php

namespace App\Filament\Resources\Sucursals\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SucursalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('razonSocial.nombre')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ciudad')
                    ->searchable()
                    ->placeholder('—'),

                IconColumn::make('activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('razon_social_id')
                    ->label('Razón Social')
                    ->relationship('razonSocial', 'nombre'),

                TernaryFilter::make('activo'),
            ])
            ->defaultSort('nombre')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
