<?php

namespace App\Filament\Resources\EquipoCelulars\Pages;

use App\Filament\Actions\CargarDesdePdfAction;
use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use App\Services\ExtractorResponsivaPdf;
use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularForm;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipoCelular extends CreateRecord
{
    protected static string $resource = EquipoCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CargarDesdePdfAction::make(ExtractorResponsivaPdf::TIPO_CELULAR),
            EquipoCelularForm::confirmarBajaDuplicadoAction(),
        ];
    }

    public function create(bool $another = false): void
    {
        if (EquipoCelularForm::conflictosDe($this)->isNotEmpty()) {
            $this->mountAction('confirmarBajaDuplicado', ['another' => $another]);

            return;
        }

        parent::create($another);
    }
}
