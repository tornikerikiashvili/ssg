<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use Filament\Widgets\Widget;

class ClientAnnouncements extends Widget
{
    protected static ?int $sort = -4;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.client-announcements';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'items' => Announcement::query()->with('company')->latest()->orderByDesc('id')->limit(7)->get(),
        ];
    }
}
