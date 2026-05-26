<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipoComputos extends ListRecords
{
    protected static string $resource = EquipoComputoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
