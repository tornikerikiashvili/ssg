<?php

namespace App\Filament\Widgets;

use App\Models\PortalNotification;
use Filament\Widgets\Widget;

class ClientNotifications extends Widget
{
    protected static ?int $sort = -5;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.client-notifications';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'items' => PortalNotification::query()->with('company')->latest()->orderByDesc('id')->limit(7)->get(),
        ];
    }
}
