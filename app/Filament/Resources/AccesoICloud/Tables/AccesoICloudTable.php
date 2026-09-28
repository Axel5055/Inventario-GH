<?php

namespace App\Filament\Resources\AccesoICloud\Tables;

use App\Models\AccesoICloud;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccesoICloudTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cuenta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AccesoICloud::TIPOS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'correo' => 'info',
                        'musica' => 'danger',
                        'opentable' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('correo')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('¡Correo copiado!'),

                // Los secretos se ven enmascarados y solo se pueden copiar.
                TextColumn::make('contrasena')
                    ->label('Contraseña')
                    ->formatStateUsing(fn () => '••••••••')
                    ->copyable()
                    ->copyMessage('¡Contraseña copiada!')
                    ->copyableState(fn (AccesoICloud $record) => $record->contrasena),

                TextColumn::make('clave_recuperacion')
                    ->label('Clave de Recuperación')
                    ->formatStateUsing(fn () => '••••••••')
                    ->copyable()
                    ->copyMessage('¡Clave copiada!')
                    ->copyableState(fn (AccesoICloud $record) => $record->clave_recuperacion)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('numero_serie')
                    ->label('Número de Serie')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('pin')
                    ->label('PIN')
                    ->formatStateUsing(fn () => '••••')
                    ->copyable()
                    ->copyMessage('¡PIN copiado!')
                    ->copyableState(fn (AccesoICloud $record) => $record->pin)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->options(AccesoICloud::TIPOS),
            ])
            ->defaultSort('cuenta')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
