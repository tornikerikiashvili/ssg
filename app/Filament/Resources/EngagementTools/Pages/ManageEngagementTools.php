<?php

namespace App\Filament\Resources\EngagementTools\Pages;

use App\Filament\Resources\EngagementTools\EngagementToolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEngagementTools extends ManageRecords
{
    protected static string $resource = EngagementToolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
