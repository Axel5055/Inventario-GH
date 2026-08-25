<?php

namespace App\Filament\Resources\RazonSocials;

use App\Filament\Resources\RazonSocials\Pages\CreateRazonSocial;
use App\Filament\Resources\RazonSocials\Pages\EditRazonSocial;
use App\Filament\Resources\RazonSocials\Pages\ListRazonSocials;
use App\Filament\Resources\RazonSocials\Schemas\RazonSocialForm;
use App\Filament\Resources\RazonSocials\Tables\RazonSocialsTable;
use App\Models\RazonSocial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RazonSocialResource extends Resource
{
    protected static ?string $model = RazonSocial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Razones Sociales';
    protected static ?string $modelLabel = 'Razón Social';
    protected static ?string $pluralModelLabel = 'Razones Sociales';
    protected static string | UnitEnum | null $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return RazonSocialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RazonSocialsTable::configure($table);
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
            'index' => ListRazonSocials::route('/'),
            'create' => CreateRazonSocial::route('/create'),
            'edit' => EditRazonSocial::route('/{record}/edit'),
        ];
    }
}
