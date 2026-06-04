<?php

namespace App\Filament\Resources\EquipoCelulars;

use App\Filament\Resources\EquipoCelulars\Pages\CreateEquipoCelular;
use App\Filament\Resources\EquipoCelulars\Pages\EditEquipoCelular;
use App\Filament\Resources\EquipoCelulars\Pages\ListEquipoCelulars;
use App\Filament\Resources\EquipoCelulars\Pages\ViewEquipoCelular;
use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularForm;
use App\Filament\Resources\EquipoCelulars\Schemas\EquipoCelularInfolist;
use App\Filament\Resources\EquipoCelulars\Tables\EquipoCelularsTable;
use App\Filament\Traits\HasRoleAccess;
use App\Models\EquipoCelular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EquipoCelularResource extends Resource
{

    protected static ?string $model = EquipoCelular::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Equipos Moviles';
    protected static ?string $modelLabel = 'Equipo Movil';
    protected static ?string $pluralModelLabel = 'Equipos Moviles';
    protected static string | UnitEnum | null $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nombre_usuario';

    public static function form(Schema $schema): Schema
    {
        return EquipoCelularForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipoCelularInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipoCelularsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipoCelulars::route('/'),
            'create' => CreateEquipoCelular::route('/create'),
            'view' => ViewEquipoCelular::route('/{record}'),
            'edit' => EditEquipoCelular::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
