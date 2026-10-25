<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Actions\CargarDesdePdfAction;
use App\Filament\Actions\CargarInfoEquipoAction;
use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use App\Services\ExtractorResponsivaPdf;
use App\Filament\Resources\EquipoComputos\Schemas\EquipoComputoForm;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipoComputo extends CreateRecord
{
    protected static string $resource = EquipoComputoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CargarDesdePdfAction::make(ExtractorResponsivaPdf::TIPO_COMPUTO),
            CargarInfoEquipoAction::make(),
            EquipoComputoForm::confirmarBajaSerieDuplicadaAction(),
            EquipoComputoForm::confirmarMismoCorreoAction(),
        ];
    }

    /** true cuando el usuario ya decidió qué hacer con los equipos del mismo correo. */
    public bool $correoConfirmado = false;

    public function create(bool $another = false): void
    {
        $conflicto = EquipoComputoForm::buscarConflictoNumeroSerie($this->data['numero_serie'] ?? null, null, $this->data['tipo_movimiento'] ?? null);

        if ($conflicto) {
            $this->mountAction('confirmarBajaSerieDuplicada', [
                'conflictoId' => $conflicto->id,
                'mensaje' => "Ya existe un equipo ACTIVO con este número de serie, asignado a «{$conflicto->nombre_usuario}»" . ($conflicto->fecha_entrega ? ' (fecha de entrega: ' . $conflicto->fecha_entrega->format('d/m/Y') . ')' : '') . ". ¿Deseas darlo de baja para reutilizar el número en este nuevo registro?",
                'another' => $another,
            ]);

            return;
        }

        // Misma persona con equipo activo: se pregunta si se da de baja, pero el
        // registro se guarda en cualquier caso (se valida antes de preguntar).
        if (! $this->correoConfirmado && EquipoComputoForm::mismoCorreoDe($this)->isNotEmpty()) {
            $this->form->validate();
            $this->mountAction('confirmarMismoCorreo', ['another' => $another]);

            return;
        }

        parent::create($another);

        $this->correoConfirmado = false;
    }
}
