<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipoComputo extends ViewRecord
{
    protected static string $resource = EquipoComputoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
