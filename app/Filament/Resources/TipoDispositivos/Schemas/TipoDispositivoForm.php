<?php

namespace App\Filament\Resources\TipoDispositivos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TipoDispositivoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del Tipo')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('Si lo renombras, los registros anteriores conservan el nombre con el que se capturaron.'),

                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Los tipos inactivos dejan de aparecer para elegir en el formulario de dispositivos.'),
            ]);
    }
}
