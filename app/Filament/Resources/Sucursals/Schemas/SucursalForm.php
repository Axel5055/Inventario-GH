<?php

namespace App\Filament\Resources\Sucursals\Schemas;

use App\Models\RazonSocial;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SucursalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('razon_social_id')
                    ->label('Razón Social')
                    ->relationship('razonSocial', 'nombre')
                    ->options(RazonSocial::where('activo', true)->pluck('nombre', 'id'))
                    ->required()
                    ->native(false)
                    ->searchable(),

                TextInput::make('nombre')
                    ->label('Nombre de la Sucursal')
                    ->required()
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
