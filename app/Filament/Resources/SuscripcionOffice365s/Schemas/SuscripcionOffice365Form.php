<?php

namespace App\Filament\Resources\SuscripcionOffice365s\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SuscripcionOffice365Form
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

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

                DatePicker::make('fecha_compra')
                    ->label('Fecha de Compra')
                    ->required()
                    ->default(now())
                    ->displayFormat('d/m/Y')
                    ->live(),

                DatePicker::make('fecha_fin')
                    ->label('Fecha en que Termina')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('fecha_compra')
                    ->live()
                    ->helperText(function ($get) {
                        $fin = $get('fecha_fin');

                        if (blank($fin)) {
                            return null;
                        }

                        $dias = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($fin)->startOfDay(), false);

                        if ($dias < 0) {
                            return 'Ya venció hace ' . abs($dias) . ' días.';
                        }

                        if ($dias === 0) {
                            return 'Vence hoy.';
                        }

                        return "Quedarían {$dias} días a partir de hoy.";
                    }),
            ]);
    }
}
