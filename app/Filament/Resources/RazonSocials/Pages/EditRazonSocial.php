<?php

namespace App\Filament\Resources\RazonSocials\Pages;

use App\Filament\Resources\RazonSocials\RazonSocialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRazonSocial extends EditRecord
{
    protected static string $resource = RazonSocialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
