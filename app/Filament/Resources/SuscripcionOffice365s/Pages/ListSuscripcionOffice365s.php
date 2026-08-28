<?php

namespace App\Filament\Resources\SuscripcionOffice365s\Pages;

use App\Filament\Resources\SuscripcionOffice365s\SuscripcionOffice365Resource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuscripcionOffice365s extends ListRecords
{
    protected static string $resource = SuscripcionOffice365Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
