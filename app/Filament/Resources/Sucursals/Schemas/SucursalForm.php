<?php

namespace App\Filament\Resources\Sucursals\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SucursalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre de la Sucursal')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('ciudad')
                    ->label('Ciudad')
                    ->maxLength(255),

                Toggle::make('activo')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Las sucursales inactivas dejan de aparecer para elegir en los formularios de equipo.'),
            ]);
    }
}
