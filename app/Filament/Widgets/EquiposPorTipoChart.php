<?php

namespace App\Filament\Widgets;

use App\Models\EquipoComputo;
use App\Models\EquipoCelular;
use Filament\Widgets\ChartWidget;

class EquiposPorTipoChart extends ChartWidget
{
    protected ?string $heading = 'Equipos de Cómputo por Tipo';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $tipos = EquipoComputo::whereNull('fecha_baja')
            ->selectRaw('tipo_equipo, COUNT(*) as total')
            ->groupBy('tipo_equipo')
            ->pluck('total', 'tipo_equipo')
            ->toArray();

        $labels = array_map(fn($t) => match ($t) {
            'laptop'      => 'Laptop',
            'desktop'     => 'Desktop',
            'all_in_one'  => 'All-in-One',
            'workstation' => 'Workstation',
            'mini_pc'     => 'Mini PC',
            default       => ucfirst($t),
        }, array_keys($tipos));

        return [
            'datasets' => [
                [
                    'label'           => 'Equipos activos',
                    'data'            => array_values($tipos),
                    'backgroundColor' => [
                        '#f59e0b',
                        '#3b82f6',
                        '#10b981',
                        '#8b5cf6',
                        '#ef4444',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
