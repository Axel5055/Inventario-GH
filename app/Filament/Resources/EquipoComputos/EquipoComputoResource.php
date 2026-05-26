<?php

namespace App\Filament\Resources\EquipoComputos;

use App\Filament\Resources\EquipoComputos\Pages\CreateEquipoComputo;
use App\Filament\Resources\EquipoComputos\Pages\EditEquipoComputo;
use App\Filament\Resources\EquipoComputos\Pages\ListEquipoComputos;
use App\Filament\Resources\EquipoComputos\Pages\ViewEquipoComputo;
use App\Filament\Resources\EquipoComputos\Schemas\EquipoComputoForm;
use App\Filament\Resources\EquipoComputos\Schemas\EquipoComputoInfolist;
use App\Filament\Resources\EquipoComputos\Tables\EquipoComputosTable;
use App\Models\EquipoComputo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EquipoComputoResource extends Resource
{
    protected static ?string $model = EquipoComputo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Equipos de Cómputo';
    protected static ?string $modelLabel = 'Equipo de Cómputo';
    protected static ?string $pluralModelLabel = 'Equipos de Cómputo';
    protected static string | UnitEnum | null $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nombre_usuario';

    public static function form(Schema $schema): Schema
    {
        return EquipoComputoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipoComputoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipoComputosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEquipoComputos::route('/'),
            'create' => CreateEquipoComputo::route('/create'),
            'view'   => ViewEquipoComputo::route('/{record}'),
            'edit'   => EditEquipoComputo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
