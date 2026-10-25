<?php

namespace App\Filament\Resources\EntregaDispositivos\Pages;

use App\Filament\Actions\CargarDesdePdfAction;
use App\Filament\Resources\EntregaDispositivos\EntregaDispositivoResource;
use App\Services\ExtractorResponsivaPdf;
use Filament\Resources\Pages\CreateRecord;

class CreateEntregaDispositivo extends CreateRecord
{
    protected static string $resource = EntregaDispositivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CargarDesdePdfAction::make(ExtractorResponsivaPdf::TIPO_DISPOSITIVOS),
        ];
    }
}
