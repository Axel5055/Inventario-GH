<?php

namespace App\Filament\Resources\AccesoICloud;

use App\Filament\Resources\AccesoICloud\Pages\CreateAccesoICloud;
use App\Filament\Resources\AccesoICloud\Pages\EditAccesoICloud;
use App\Filament\Resources\AccesoICloud\Pages\ListAccesoICloud;
use App\Filament\Resources\AccesoICloud\Pages\ViewAccesoICloud;
use App\Filament\Resources\AccesoICloud\Schemas\AccesoICloudForm;
use App\Filament\Resources\AccesoICloud\Schemas\AccesoICloudInfolist;
use App\Filament\Resources\AccesoICloud\Tables\AccesoICloudTable;
use App\Models\AccesoICloud;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AccesoICloudResource extends Resource
{
    protected static ?string $model = AccesoICloud::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static ?string $navigationLabel = 'iCloud';
    protected static ?string $modelLabel = 'Acceso iCloud';
    protected static ?string $pluralModelLabel = 'Accesos iCloud';
    protected static string|UnitEnum|null $navigationGroup = 'Accesos';
    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'accesos-icloud';

    protected static ?string $recordTitleAttribute = 'cuenta';

    public static function form(Schema $schema): Schema
    {
        return AccesoICloudForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccesoICloudInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccesoICloudTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccesoICloud::route('/'),
            'create' => CreateAccesoICloud::route('/create'),
            'view' => ViewAccesoICloud::route('/{record}'),
            'edit' => EditAccesoICloud::route('/{record}/edit'),
        ];
    }
}
