<?php

namespace App\Filament\Resources\Games\Pages;

use App\Filament\Resources\Games\GameResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->url(GameResource::getUrl('create')),
        ];
    }
}
