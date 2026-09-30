<?php

namespace App\Filament\Resources\GameTypes\Pages;

use App\Filament\Resources\GameTypes\GameTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGameTypes extends ManageRecords
{
    protected static string $resource = GameTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
