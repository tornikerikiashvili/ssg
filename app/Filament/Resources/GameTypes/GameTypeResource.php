<?php

namespace App\Filament\Resources\GameTypes;

use App\Filament\Resources\CatalogOptions\CatalogOptionResource;
use App\Filament\Resources\GameTypes\Pages\ManageGameTypes;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GameTypeResource extends CatalogOptionResource
{
    protected static ?string $navigationLabel = 'Game Types';

    protected static ?string $pluralModelLabel = 'Game Types';

    protected static ?string $modelLabel = 'game type';

    protected static string|UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $optionKind = 'game_type';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'game_type');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGameTypes::route('/')];
    }
}
