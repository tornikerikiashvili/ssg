<?php

namespace App\Filament\Resources\DocumentationCategories;

use App\Filament\Resources\CatalogOptions\CatalogOptionResource;
use App\Filament\Resources\DocumentationCategories\Pages\ManageDocumentationCategories;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DocumentationCategoryResource extends CatalogOptionResource
{
    protected static ?string $navigationLabel = 'Documentation Categories';

    protected static ?string $pluralModelLabel = 'Documentation Categories';

    protected static ?string $modelLabel = 'documentation category';

    protected static string|UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?string $optionKind = 'document';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'document');
    }

    public static function getPages(): array
    {
        return ['index' => ManageDocumentationCategories::route('/')];
    }
}
