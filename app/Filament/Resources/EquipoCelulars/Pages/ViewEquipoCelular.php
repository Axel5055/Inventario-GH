<?php

namespace App\Filament\Resources\EquipoCelulars\Pages;

use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipoCelular extends ViewRecord
{
    protected static string $resource = EquipoCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
