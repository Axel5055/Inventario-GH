<?php

namespace App\Filament\Resources\TipoDispositivos\Pages;

use App\Filament\Resources\TipoDispositivos\TipoDispositivoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTipoDispositivo extends EditRecord
{
    protected static string $resource = TipoDispositivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
