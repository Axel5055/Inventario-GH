<?php

namespace App\Filament\Widgets;

use App\Models\EquipoComputo;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UltimosEquiposWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Últimos Equipos Registrados';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EquipoComputo::query()
                    ->with(['marca', 'razonSocial', 'sucursal'])
                    ->whereNull('fecha_baja')
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre_usuario')
                    ->label('Usuario')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tipo_equipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'laptop'      => 'primary',
                        'desktop'     => 'info',
                        'all_in_one'  => 'success',
                        'workstation' => 'warning',
                        'mini_pc'     => 'gray',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'laptop'      => 'Laptop',
                        'desktop'     => 'Desktop',
                        'all_in_one'  => 'All-in-One',
                        'workstation' => 'Workstation',
                        'mini_pc'     => 'Mini PC',
                        default       => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('marca.nombre')
                    ->label('Marca'),

                Tables\Columns\TextColumn::make('modelo')
                    ->label('Modelo'),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal'),

                Tables\Columns\TextColumn::make('fecha_entrega')
                    ->label('Fecha Entrega')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
