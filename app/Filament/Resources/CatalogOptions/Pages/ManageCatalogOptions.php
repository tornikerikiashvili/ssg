<?php

namespace App\Filament\Resources\CatalogOptions\Pages;

use App\Filament\Resources\CatalogOptions\CatalogOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCatalogOptions extends ManageRecords
{
    protected static string $resource = CatalogOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
