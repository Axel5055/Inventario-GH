<?php

namespace App\Filament\Resources\EntregaDispositivos\Pages;

use App\Filament\Resources\EntregaDispositivos\EntregaDispositivoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEntregaDispositivo extends ViewRecord
{
    protected static string $resource = EntregaDispositivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
