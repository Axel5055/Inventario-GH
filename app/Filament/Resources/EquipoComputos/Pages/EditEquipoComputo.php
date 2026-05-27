<?php

namespace App\Filament\Resources\EquipoComputos\Pages;

use App\Filament\Resources\EquipoComputos\EquipoComputoResource;
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
        ];
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
