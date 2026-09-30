<?php

namespace App\Filament\Resources\VolatilityLevels\Pages;

use App\Filament\Resources\VolatilityLevels\VolatilityLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVolatilityLevels extends ManageRecords
{
    protected static string $resource = VolatilityLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
