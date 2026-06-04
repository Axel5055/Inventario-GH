<?php

namespace App\Filament\Widgets;

use App\Models\EquipoComputo;
use App\Models\EquipoCelular;
use App\Models\EntregaDispositivo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventarioStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $computosActivos  = EquipoComputo::whereNull('fecha_baja')->count();
        $computosTotal    = EquipoComputo::count();
        $celularesActivos = EquipoCelular::whereNull('fecha_baja')->count();
        $celularesTotal   = EquipoCelular::count();
        $entregasTotal    = EntregaDispositivo::count();
        $entregasMes      = EntregaDispositivo::whereMonth('fecha_entrega', now()->month)
            ->whereYear('fecha_entrega', now()->year)
            ->count();

        // Bajas del mes actual
        $bajasMes = EquipoComputo::whereMonth('fecha_baja', now()->month)
            ->whereYear('fecha_baja', now()->year)
            ->count()
            + EquipoCelular::whereMonth('fecha_baja', now()->month)
            ->whereYear('fecha_baja', now()->year)
            ->count();

        return [
            Stat::make('Equipos de Cómputo Activos', $computosActivos)
                ->description("$computosTotal registros totales")
                ->descriptionIcon('heroicon-o-computer-desktop')
                ->color('primary')
                ->chart(
                    EquipoComputo::selectRaw('COUNT(*) as count')
                        ->whereNull('fecha_baja')
                        ->whereMonth('created_at', '>=', now()->subMonths(5)->month)
                        ->groupByRaw('MONTH(created_at)')
                        ->orderByRaw('MONTH(created_at)')
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Equipos Celulares Activos', $celularesActivos)
                ->description("$celularesTotal registros totales")
                ->descriptionIcon('heroicon-o-device-phone-mobile')
                ->color('success')
                ->chart(
                    EquipoCelular::selectRaw('COUNT(*) as count')
                        ->whereNull('fecha_baja')
                        ->whereMonth('created_at', '>=', now()->subMonths(5)->month)
                        ->groupByRaw('MONTH(created_at)')
                        ->orderByRaw('MONTH(created_at)')
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Entregas de Dispositivos', $entregasTotal)
                ->description("$entregasMes entregas este mes")
                ->descriptionIcon('heroicon-o-archive-box-arrow-down')
                ->color('warning'),

            Stat::make('Bajas este Mes', $bajasMes)
                ->description('Equipos dados de baja en ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color($bajasMes > 0 ? 'danger' : 'gray'),
        ];
    }
}
