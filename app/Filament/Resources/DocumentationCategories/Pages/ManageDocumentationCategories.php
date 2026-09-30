<?php

namespace App\Filament\Resources\DocumentationCategories\Pages;

use App\Filament\Resources\DocumentationCategories\DocumentationCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDocumentationCategories extends ManageRecords
{
    protected static string $resource = DocumentationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
