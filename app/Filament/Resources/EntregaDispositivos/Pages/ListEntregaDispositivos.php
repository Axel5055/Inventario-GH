<?php

namespace App\Filament\Resources\EntregaDispositivos\Pages;

use App\Filament\Resources\EntregaDispositivos\EntregaDispositivoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntregaDispositivos extends ListRecords
{
    protected static string $resource = EntregaDispositivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
