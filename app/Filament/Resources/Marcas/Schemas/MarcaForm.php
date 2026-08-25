<?php

namespace App\Filament\Resources\Marcas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MarcaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre de la Marca')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('categoria')
                    ->label('¿La marca es para..?')
                    ->options([
                        'ambas'   => 'Ambas',
                        'computo' => 'Equipo de Cómputo',
                        'celular' => 'Celular',
                    ])
                    ->required()
                    ->native(false),

                Toggle::make('activo')
                    ->label('Activa')
                    ->default(true)
                    ->helperText('Las marcas inactivas dejan de aparecer para elegir en los formularios de equipo.'),
            ]);
    }
}
