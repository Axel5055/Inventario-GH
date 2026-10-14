<?php

namespace App\Filament\Resources\EquipoCelulars\Pages;

use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use App\Filament\Actions\ExportarExcelAction;
use App\Services\ReporteInventario;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipoCelulars extends ListRecords
{
    protected static string $resource = EquipoCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportarExcelAction::make(ReporteInventario::CELULAR),
            CreateAction::make(),
        ];
    }
}
