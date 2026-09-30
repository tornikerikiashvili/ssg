<?php

namespace App\Filament\Resources\VolatilityLevels;

use App\Filament\Resources\CatalogOptions\CatalogOptionResource;
use App\Filament\Resources\VolatilityLevels\Pages\ManageVolatilityLevels;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VolatilityLevelResource extends CatalogOptionResource
{
    protected static ?string $navigationLabel = 'Volatility Levels';

    protected static ?string $pluralModelLabel = 'Volatility Levels';

    protected static ?string $modelLabel = 'volatility level';

    protected static string|UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $optionKind = 'volatility';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'volatility');
    }

    public static function getPages(): array
    {
        return ['index' => ManageVolatilityLevels::route('/')];
    }
}
