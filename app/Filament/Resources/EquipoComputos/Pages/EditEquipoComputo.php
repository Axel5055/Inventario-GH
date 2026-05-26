<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipoComputo extends EditRecord
{
    protected static string $resource = EquipoComputoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
