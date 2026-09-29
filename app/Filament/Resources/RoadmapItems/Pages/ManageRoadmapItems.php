<?php

namespace App\Filament\Resources\RoadmapItems\Pages;

use App\Filament\Resources\RoadmapItems\RoadmapItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRoadmapItems extends ManageRecords
{
    protected static string $resource = RoadmapItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
