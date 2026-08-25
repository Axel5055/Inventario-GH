<?php

namespace App\Filament\Resources\RazonSocials\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RazonSocialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre / Razón Social')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('rfc')
                    ->label('RFC')
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),

                Toggle::make('activo')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Las razones sociales inactivas dejan de aparecer para elegir en los formularios de equipo.'),
            ]);
    }
}
