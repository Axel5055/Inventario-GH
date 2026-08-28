<?php

namespace App\Filament\Resources\SuscripcionOffice365s\Tables;

use App\Models\SuscripcionOffice365;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuscripcionOffice365sTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('correo')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('¡Correo copiado!'),

                TextColumn::make('fecha_compra')
                    ->label('Fecha de Compra')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fecha_fin')
                    ->label('Termina el')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('dias_restantes')
                    ->label('Días Restantes')
                    ->state(fn(SuscripcionOffice365 $record) => match (true) {
                        $record->dias_restantes < 0 => 'Venció hace ' . abs($record->dias_restantes) . ' días',
                        $record->dias_restantes === 0 => 'Vence hoy',
                        default => $record->dias_restantes . ' días',
                    })
                    ->badge()
                    ->color(fn(SuscripcionOffice365 $record) => match (true) {
                        $record->dias_restantes < 0 => 'danger',
                        $record->dias_restantes <= 15 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn(SuscripcionOffice365 $record) => match (true) {
                        $record->dias_restantes < 0 => 'heroicon-o-x-circle',
                        $record->dias_restantes <= 15 => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-check-circle',
                    }),
            ])
            ->defaultSort('fecha_fin')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
