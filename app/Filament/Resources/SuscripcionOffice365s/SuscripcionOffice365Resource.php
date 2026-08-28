<?php

namespace App\Filament\Resources\SuscripcionOffice365s;

use App\Filament\Resources\SuscripcionOffice365s\Pages\CreateSuscripcionOffice365;
use App\Filament\Resources\SuscripcionOffice365s\Pages\EditSuscripcionOffice365;
use App\Filament\Resources\SuscripcionOffice365s\Pages\ListSuscripcionOffice365s;
use App\Filament\Resources\SuscripcionOffice365s\Schemas\SuscripcionOffice365Form;
use App\Filament\Resources\SuscripcionOffice365s\Tables\SuscripcionOffice365sTable;
use App\Models\SuscripcionOffice365;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SuscripcionOffice365Resource extends Resource
{
    protected static ?string $model = SuscripcionOffice365::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Office 365';
    protected static ?string $modelLabel = 'Suscripción de Office 365';
    protected static ?string $pluralModelLabel = 'Suscripciones de Office 365';
    protected static string|UnitEnum|null $navigationGroup = 'Suscripciones';
    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return SuscripcionOffice365Form::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuscripcionOffice365sTable::configure($table);
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
            'index' => ListSuscripcionOffice365s::route('/'),
            'create' => CreateSuscripcionOffice365::route('/create'),
            'edit' => EditSuscripcionOffice365::route('/{record}/edit'),
        ];
    }
}
