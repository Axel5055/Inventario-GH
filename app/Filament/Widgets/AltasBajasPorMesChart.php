<?php

namespace App\Filament\Widgets;

use App\Models\EquipoComputo;
use App\Models\EquipoCelular;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AltasBajasPorMesChart extends ChartWidget
{
    protected ?string $heading = 'Altas vs Bajas (últimos 6 meses)';
    protected static ?int $sort = 3;
    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $meses  = collect(range(5, 0))->map(fn($i) => now()->subMonths($i));
        $labels = $meses->map(fn($m) => $m->translatedFormat('M Y'))->toArray();

        $altas = $meses->map(function ($mes) {
            return EquipoComputo::whereMonth('fecha_entrega', $mes->month)
                ->whereYear('fecha_entrega', $mes->year)
                ->count()
                + EquipoCelular::whereMonth('fecha_entrega', $mes->month)
                ->whereYear('fecha_entrega', $mes->year)
                ->count();
        })->toArray();

        $bajas = $meses->map(function ($mes) {
            return EquipoComputo::whereNotNull('fecha_baja')
                ->whereMonth('fecha_baja', $mes->month)
                ->whereYear('fecha_baja', $mes->year)
                ->count()
                + EquipoCelular::whereNotNull('fecha_baja')
                ->whereMonth('fecha_baja', $mes->month)
                ->whereYear('fecha_baja', $mes->year)
                ->count();
        })->toArray();

        return [
            'datasets' => [
                [
                    'label'           => 'Altas',
                    'data'            => $altas,
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Bajas',
                    'data'            => $bajas,
                    'borderColor'     => '#ef4444',
                    'backgroundColor' => 'rgba(239,68,68,0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
