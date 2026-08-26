<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Blade;
use Spatie\Activitylog\Models\Activity;

class ActividadRecienteWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected static ?string $heading = 'Actividad Reciente';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->with('causer')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->html()
                    ->formatStateUsing(fn($record) => self::usuarioConRol($record))
                    ->placeholder('Sistema'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Acción'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cuándo')
                    ->since()
                    ->dateTooltip('d/m/Y H:i'),
            ])
            ->paginated(false);
    }

    private static function usuarioConRol($record): string
    {
        $causante = $record->causer;
        $nombre = e($causante?->name ?? 'Sistema');

        $rol = $causante?->getRoleNames()->first();

        if (! $rol) {
            return $nombre;
        }

        $color = $rol === 'super_admin' ? 'danger' : 'gray';

        $badge = Blade::render(
            '<x-filament::badge :color="$color" size="xs">{{ $rol }}</x-filament::badge>',
            ['color' => $color, 'rol' => $rol]
        );

        return "<div class=\"flex items-center gap-x-2\"><span class=\"font-semibold\">{$nombre}</span>{$badge}</div>";
    }
}
