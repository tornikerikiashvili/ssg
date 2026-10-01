<?php

namespace App\Filament\Resources\PortalNotifications\Pages;

use App\Filament\Resources\PortalNotifications\PortalNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePortalNotifications extends ManageRecords
{
    protected static string $resource = PortalNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
