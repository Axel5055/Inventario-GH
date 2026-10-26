<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use App\Filament\Actions\DescargarResponsivasAction;
use App\Filament\Actions\ExportarExcelAction;
use App\Services\ReporteInventario;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipoComputos extends ListRecords
{
    protected static string $resource = EquipoComputoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportarExcelAction::make(ReporteInventario::COMPUTO),
            DescargarResponsivasAction::make(ReporteInventario::COMPUTO),
            CreateAction::make(),
        ];
    }
}
