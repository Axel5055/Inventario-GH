<?php

namespace App\Filament\Resources\AccesoICloud\Schemas;

use App\Models\AccesoICloud;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccesoICloudInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cuenta')
                ->icon('heroicon-o-cloud')
                ->columns(2)
                ->schema([
                    TextEntry::make('cuenta')
                        ->label('Cuenta')
                        ->icon('heroicon-o-user-circle')
                        ->copyable()
                        ->copyMessage('¡Cuenta copiada!'),

                    TextEntry::make('tipo')
                        ->label('Tipo')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => AccesoICloud::TIPOS[$state] ?? $state)
                        ->color(fn (string $state) => match ($state) {
                            'correo' => 'info',
                            'musica' => 'danger',
                            'opentable' => 'warning',
                            default => 'gray',
                        }),

                    TextEntry::make('correo')
                        ->label('Correo')
                        ->icon('heroicon-o-envelope')
                        ->copyable()
                        ->copyMessage('¡Correo copiado!'),

                    TextEntry::make('numero_serie')
                        ->label('Número de Serie')
                        ->icon('heroicon-o-hashtag')
                        ->copyable()
                        ->copyMessage('¡Número de serie copiado!')
                        ->placeholder('No registrado'),
                ]),

            Section::make('Accesos')
                ->icon('heroicon-o-key')
                ->columns(3)
                ->schema([
                    TextEntry::make('contrasena')
                        ->label('Contraseña')
                        ->icon('heroicon-o-lock-closed')
                        ->copyable()
                        ->copyMessage('¡Contraseña copiada!'),

                    TextEntry::make('clave_recuperacion')
                        ->label('Clave de Recuperación')
                        ->icon('heroicon-o-shield-check')
                        ->copyable()
                        ->copyMessage('¡Clave copiada!')
                        ->placeholder('No registrada'),

                    TextEntry::make('pin')
                        ->label('PIN')
                        ->icon('heroicon-o-finger-print')
                        ->copyable()
                        ->copyMessage('¡PIN copiado!')
                        ->placeholder('No registrado'),
                ]),

            Section::make('Registro')
                ->icon('heroicon-o-clock')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Creado')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('Última modificación')
                        ->dateTime('d/m/Y H:i'),
                ]),
        ]);
    }
}
