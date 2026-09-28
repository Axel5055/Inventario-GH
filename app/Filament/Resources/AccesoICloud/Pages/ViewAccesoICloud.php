<?php

namespace App\Filament\Resources\AccesoICloud\Pages;

use App\Filament\Resources\AccesoICloud\AccesoICloudResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccesoICloud extends ViewRecord
{
    protected static string $resource = AccesoICloudResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
