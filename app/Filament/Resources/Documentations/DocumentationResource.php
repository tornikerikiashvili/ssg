<?php

namespace App\Filament\Resources\Documentations;

use App\Filament\Resources\Documentations\Pages\ManageDocumentations;
use App\Filament\Resources\ResourceItems\ResourceItemResource;
use Illuminate\Database\Eloquent\Builder;

class DocumentationResource extends ResourceItemResource
{
    protected static ?string $navigationLabel = 'Documentations';

    protected static ?string $pluralModelLabel = 'Documentations';

    protected static ?string $modelLabel = 'documentation';

    protected static ?string $resourceKind = 'documentation';

    protected static bool $standalone = true;

    protected static bool $requiresSlug = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('kind', 'documentation')->whereNull('game_id');
    }

    public static function getPages(): array
    {
        return ['index' => ManageDocumentations::route('/')];
    }
}
