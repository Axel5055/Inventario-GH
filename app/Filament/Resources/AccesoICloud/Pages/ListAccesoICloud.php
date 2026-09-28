<?php

namespace App\Filament\Resources\AccesoICloud\Pages;

use App\Filament\Resources\AccesoICloud\AccesoICloudResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccesoICloud extends ListRecords
{
    protected static string $resource = AccesoICloudResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
