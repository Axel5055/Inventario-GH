<?php

namespace App\Filament\Resources\RazonSocials\Pages;

use App\Filament\Resources\RazonSocials\RazonSocialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRazonSocials extends ListRecords
{
    protected static string $resource = RazonSocialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
