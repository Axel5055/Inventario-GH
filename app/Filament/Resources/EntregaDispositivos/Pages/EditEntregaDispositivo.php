<?php

namespace App\Filament\Resources\EntregaDispositivos\Pages;

use App\Filament\Resources\EntregaDispositivos\EntregaDispositivoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEntregaDispositivo extends EditRecord
{
    protected static string $resource = EntregaDispositivoResource::class;

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
