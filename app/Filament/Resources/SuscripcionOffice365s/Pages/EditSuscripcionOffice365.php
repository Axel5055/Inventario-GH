<?php

namespace App\Filament\Resources\SuscripcionOffice365s\Pages;

use App\Filament\Resources\SuscripcionOffice365s\SuscripcionOffice365Resource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSuscripcionOffice365 extends EditRecord
{
    protected static string $resource = SuscripcionOffice365Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
