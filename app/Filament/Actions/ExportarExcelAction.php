<?php

namespace App\Filament\Actions;

use App\Services\ReporteInventario;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Botones de exportación a Excel de las listas de inventario:
 *  - "Exportar a Excel" (arriba): todo lo que cumple los filtros y la búsqueda
 *    actuales, no solo la página que se ve.
 *  - "Exportar seleccionados" (acción en lote): solo las filas marcadas.
 */
class ExportarExcelAction
{
    public static function make(string $seccion): Action
    {
        return Action::make('exportarExcel')
            ->label('Exportar a Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->tooltip('Descarga en Excel los registros con los filtros y la búsqueda actuales')
            ->visible(fn () => auth()->user()?->can('viewAny', ReporteInventario::modelo($seccion)) ?? false)
            ->action(fn ($livewire) => app(ReporteInventario::class)
                ->descargarSeccion($seccion, $livewire->getFilteredSortedTableQuery()));
    }

    public static function enLote(string $seccion): BulkAction
    {
        return BulkAction::make('exportarSeleccionados')
            ->label('Exportar seleccionados')
            ->icon('heroicon-o-arrow-down-tray')
            ->deselectRecordsAfterCompletion()
            ->visible(fn () => auth()->user()?->can('viewAny', ReporteInventario::modelo($seccion)) ?? false)
            ->action(fn (Collection $records) => app(ReporteInventario::class)->descargarSeccion($seccion, $records));
    }
}
