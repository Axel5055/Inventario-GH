<?php

namespace App\Filament\Resources\AccesoICloud\Schemas;

use App\Models\AccesoICloud;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccesoICloudForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('cuenta')
                    ->label('Cuenta')
                    ->required()
                    ->maxLength(255),

                Select::make('tipo')
                    ->label('Tipo')
                    ->options(AccesoICloud::TIPOS)
                    ->required()
                    ->native(false),

                TextInput::make('correo')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('contrasena')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->maxLength(255),

                TextInput::make('clave_recuperacion')
                    ->label('Clave de Recuperación')
                    ->password()
                    ->revealable()
                    ->maxLength(255),

                TextInput::make('numero_serie')
                    ->label('Número de Serie')
                    ->maxLength(255),

                TextInput::make('pin')
                    ->label('PIN')
                    ->password()
                    ->revealable()
                    ->maxLength(255),
            ]);
    }
}
