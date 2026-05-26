<?php

namespace App\Filament\Resources\EntregaDispositivos\Schemas;

use App\Models\EntregaDispositivo;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EntregaDispositivoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre_usuario'),
                TextEntry::make('usuario_referencia')
                    ->placeholder('-'),
                TextEntry::make('razonSocial.id')
                    ->label('Razon social'),
                TextEntry::make('sucursal.id')
                    ->label('Sucursal'),
                TextEntry::make('fecha_entrega')
                    ->date(),
                TextEntry::make('tipo_dispositivo')
                    ->badge(),
                TextEntry::make('descripcion'),
                TextEntry::make('marca')
                    ->placeholder('-'),
                TextEntry::make('modelo')
                    ->placeholder('-'),
                TextEntry::make('numero_serie')
                    ->placeholder('-'),
                TextEntry::make('tipo_movimiento')
                    ->badge(),
                TextEntry::make('observaciones')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('responsiva_pdf')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(EntregaDispositivo $record): bool => $record->trashed()),
            ]);
    }
}
