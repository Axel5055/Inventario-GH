<?php

namespace App\Filament\Resources\EquipoCelulars\Pages;

use App\Filament\Resources\EquipoCelulars\EquipoCelularResource;
use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularForm;
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
            EquipoCelularForm::confirmarBajaDuplicadoAction(),
        ];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        if (EquipoCelularForm::conflictosDe($this)->isNotEmpty()) {
            $this->mountAction('confirmarBajaDuplicado');

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

        // Guardar desde el panel significa que el personal ya revisó y completó
        // el registro (incluyendo los que llegaron pendientes por carga masiva).
        $data['estado_revision'] = 'completo';

        return $data;
    }
}
