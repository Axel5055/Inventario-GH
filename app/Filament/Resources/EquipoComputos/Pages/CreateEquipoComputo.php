<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use App\Filament\Resources\EquipoComputos\Schemas\EquipoComputoForm;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipoComputo extends CreateRecord
{
    protected static string $resource = EquipoComputoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EquipoComputoForm::confirmarBajaSerieDuplicadaAction(),
        ];
    }

    public function create(bool $another = false): void
    {
        $conflicto = EquipoComputoForm::buscarConflictoNumeroSerie($this->data['numero_serie'] ?? null, null);

        if ($conflicto) {
            $this->mountAction('confirmarBajaSerieDuplicada', [
                'conflictoId' => $conflicto->id,
                'mensaje' => "Ya existe un equipo ACTIVO con este número de serie, asignado a «{$conflicto->nombre_usuario}». ¿Deseas darlo de baja para reutilizar el número en este nuevo registro?",
                'another' => $another,
            ]);

            return;
        }

        parent::create($another);
    }
}
