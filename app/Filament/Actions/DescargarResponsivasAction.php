<?php

namespace App\Filament\Actions;

use App\Services\DescargaResponsivas;
use App\Services\ReporteInventario;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Descarga en un ZIP las responsivas de las listas de inventario:
 *  - "Descargar responsivas" (arriba): las de todos los registros que cumplen los
 *    filtros y la búsqueda actuales.
 *  - "Descargar responsivas" (acción en lote): solo las de las filas marcadas.
 */
class DescargarResponsivasAction
{
    public static function make(string $seccion): Action
    {
        return Action::make('descargarResponsivas')
            ->label('Descargar responsivas')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('gray')
            ->tooltip('Descarga en un ZIP las responsivas de los registros con los filtros y la búsqueda actuales')
            ->visible(fn () => auth()->user()?->can('viewAny', ReporteInventario::modelo($seccion)) ?? false)
            ->action(fn ($livewire) => self::descargar($seccion, $livewire->getFilteredSortedTableQuery()->get()));
    }

    public static function enLote(string $seccion): BulkAction
    {
        return BulkAction::make('descargarResponsivasSeleccion')
            ->label('Descargar responsivas')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->deselectRecordsAfterCompletion()
            ->visible(fn () => auth()->user()?->can('viewAny', ReporteInventario::modelo($seccion)) ?? false)
            ->action(fn (Collection $records) => self::descargar($seccion, $records));
    }

    public static function descargar(string $seccion, iterable $registros)
    {
        try {
            return app(DescargaResponsivas::class)->zipSeccion($seccion, $registros);
        } catch (RuntimeException $e) {
            Notification::make()->title('No se pudo descargar')->body($e->getMessage())->warning()->send();

            return null;
        }
    }
}
