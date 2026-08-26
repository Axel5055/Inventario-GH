<?php

namespace App\Filament\Resources\EquipoCelulars\Pages;

use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularForm;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipoCelular extends CreateRecord
{
    protected static string $resource = EquipoCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EquipoCelularForm::confirmarBajaImeiDuplicadoAction(),
        ];
    }

    public function create(bool $another = false): void
    {
        $conflicto = EquipoCelularForm::buscarConflictoImei($this->data['imei'] ?? null, null);

        if ($conflicto) {
            $this->mountAction('confirmarBajaImeiDuplicado', [
                'conflictoId' => $conflicto->id,
                'mensaje' => "Ya existe un equipo ACTIVO con este IMEI, asignado a «{$conflicto->nombre_usuario}». ¿Deseas darlo de baja para reutilizarlo en este nuevo registro?",
                'another' => $another,
            ]);

            return;
        }

        parent::create($another);
    }
}
