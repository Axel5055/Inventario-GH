<?php

namespace App\Filament\Resources\EntregaDispositivos;

use App\Filament\Resources\EntregaDispositivos\Pages\CreateEntregaDispositivo;
use App\Filament\Resources\EntregaDispositivos\Pages\EditEntregaDispositivo;
use App\Filament\Resources\EntregaDispositivos\Pages\ListEntregaDispositivos;
use App\Filament\Resources\EntregaDispositivos\Pages\ViewEntregaDispositivo;
use App\Filament\Resources\EntregaDispositivos\Schemas\EntregaDispositivoForm;
use App\Filament\Resources\EntregaDispositivos\Schemas\EntregaDispositivoInfolist;
use App\Filament\Resources\EntregaDispositivos\Tables\EntregaDispositivosTable;
use App\Models\EntregaDispositivo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EntregaDispositivoResource extends Resource
{
    protected static ?string $model = EntregaDispositivo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $navigationLabel = 'Dispositivos Externos';
    protected static ?string $modelLabel = 'Dispositivo Externo';
    protected static ?string $pluralModelLabel = 'Dispositivos Externos';
    protected static string | UnitEnum | null $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nombre_usuario';

    public static function form(Schema $schema): Schema
    {
        return EntregaDispositivoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EntregaDispositivoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntregaDispositivosTable::configure($table);
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
            'index' => ListEntregaDispositivos::route('/'),
            'create' => CreateEntregaDispositivo::route('/create'),
            'view' => ViewEntregaDispositivo::route('/{record}'),
            'edit' => EditEntregaDispositivo::route('/{record}/edit'),
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
