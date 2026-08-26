<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
use App\Filament\Resources\EquipoComputos\Schemas\EquipoComputoForm;
use App\Models\EquipoComputo;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipoComputo extends EditRecord
{
    protected static string $resource = EquipoComputoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            EquipoComputoForm::confirmarBajaSerieDuplicadaAction(),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        /** @var EquipoComputo $record */
        $record = $this->getRecord();

        $conflicto = EquipoComputoForm::buscarConflictoNumeroSerie($this->data['numero_serie'] ?? null, $record);

        if ($conflicto) {
            $this->mountAction('confirmarBajaSerieDuplicada', [
                'conflictoId' => $conflicto->id,
                'mensaje' => "Ya existe un equipo ACTIVO con este número de serie, asignado a «{$conflicto->nombre_usuario}». ¿Deseas darlo de baja para reutilizar el número en este registro?",
            ]);

            return;
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    /**
     * Si el tipo_movimiento cambia a cualquier valor distinto de 'baja',
     * se limpia la fecha_baja para que quede en null.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['tipo_movimiento'] ?? null) !== 'baja') {
            $data['fecha_baja'] = null;
        }

        return $data;
    }
}
