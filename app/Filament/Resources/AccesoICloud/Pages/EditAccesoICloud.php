<?php

namespace App\Filament\Resources\AccesoICloud\Pages;

use App\Filament\Resources\AccesoICloud\AccesoICloudResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAccesoICloud extends EditRecord
{
    protected static string $resource = AccesoICloudResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
