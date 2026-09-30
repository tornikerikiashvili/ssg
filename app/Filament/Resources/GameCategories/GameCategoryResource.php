<?php

namespace App\Filament\Resources\GameCategories;

use App\Filament\Resources\CatalogOptions\CatalogOptionResource;
use App\Filament\Resources\GameCategories\Pages\ManageGameCategories;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GameCategoryResource extends CatalogOptionResource
{
    protected static ?string $navigationLabel = 'Game Categories';

    protected static ?string $pluralModelLabel = 'Game Categories';

    protected static ?string $modelLabel = 'game category';

    protected static string|UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $optionKind = 'category';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'category');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGameCategories::route('/')];
    }
}
