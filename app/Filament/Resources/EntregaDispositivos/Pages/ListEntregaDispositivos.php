<?php

namespace App\Filament\Resources\EntregaDispositivos\Pages;

use App\Filament\Resources\EntregaDispositivos\EntregaDispositivoResource;
use App\Filament\Actions\DescargarResponsivasAction;
use App\Filament\Actions\ExportarExcelAction;
use App\Services\ReporteInventario;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntregaDispositivos extends ListRecords
{
    protected static string $resource = EntregaDispositivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportarExcelAction::make(ReporteInventario::DISPOSITIVOS),
            DescargarResponsivasAction::make(ReporteInventario::DISPOSITIVOS),
            CreateAction::make(),
        ];
    }
}
