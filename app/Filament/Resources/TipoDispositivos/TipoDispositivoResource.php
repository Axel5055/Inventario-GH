<?php

namespace App\Filament\Resources\TipoDispositivos;

use App\Filament\Resources\TipoDispositivos\Pages\CreateTipoDispositivo;
use App\Filament\Resources\TipoDispositivos\Pages\EditTipoDispositivo;
use App\Filament\Resources\TipoDispositivos\Pages\ListTipoDispositivos;
use App\Filament\Resources\TipoDispositivos\Schemas\TipoDispositivoForm;
use App\Filament\Resources\TipoDispositivos\Tables\TipoDispositivosTable;
use App\Models\TipoDispositivo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TipoDispositivoResource extends Resource
{
    protected static ?string $model = TipoDispositivo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $navigationLabel = 'Tipos de Dispositivo';
    protected static ?string $modelLabel = 'Tipo de Dispositivo';
    protected static ?string $pluralModelLabel = 'Tipos de Dispositivo';
    protected static string | UnitEnum | null $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'tipos-dispositivo';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return TipoDispositivoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TipoDispositivosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTipoDispositivos::route('/'),
            'create' => CreateTipoDispositivo::route('/create'),
            'edit' => EditTipoDispositivo::route('/{record}/edit'),
        ];
    }
}
