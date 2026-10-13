<?php

namespace App\Filament\Resources\TipoDispositivos\Pages;

use App\Filament\Resources\TipoDispositivos\TipoDispositivoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTipoDispositivos extends ListRecords
{
    protected static string $resource = TipoDispositivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
