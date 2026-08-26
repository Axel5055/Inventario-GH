<?php

namespace App\Filament\Resources\EquipoCelulars\Pages;

use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularForm;
use App\Models\EquipoCelular;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipoCelular extends EditRecord
{
    protected static string $resource = EquipoCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            EquipoCelularForm::confirmarBajaImeiDuplicadoAction(),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        /** @var EquipoCelular $record */
        $record = $this->getRecord();

        $conflicto = EquipoCelularForm::buscarConflictoImei($this->data['imei'] ?? null, $record);

        if ($conflicto) {
            $this->mountAction('confirmarBajaImeiDuplicado', [
                'conflictoId' => $conflicto->id,
                'mensaje' => "Ya existe un equipo ACTIVO con este IMEI, asignado a «{$conflicto->nombre_usuario}». ¿Deseas darlo de baja para reutilizarlo en este registro?",
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
