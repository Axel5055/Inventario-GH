<?php

namespace App\Filament\Resources\Marcas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MarcasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('categoria')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'ambas'   => 'Ambas',
                        'computo' => 'Equipo de Cómputo',
                        'celular' => 'Celular',
                        default   => ucfirst($state),
                    }),

                IconColumn::make('activo')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('equipos_computo_count')
                    ->label('Equipos de Cómputo')
                    ->counts('equiposComputo'),

                TextColumn::make('equipos_celulares_count')
                    ->label('Equipos Celulares')
                    ->counts('equiposCelulares'),
            ])
            ->filters([
                SelectFilter::make('categoria')
                    ->options([
                        'ambas'   => 'Ambas',
                        'computo' => 'Equipo de Cómputo',
                        'celular' => 'Celular',
                    ]),

                TernaryFilter::make('activo'),
            ])
            ->defaultSort('nombre')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
