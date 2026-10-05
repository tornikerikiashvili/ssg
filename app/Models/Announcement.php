<?php

namespace App\Models;

class Announcement extends PortalContent
{
    protected $attributes = [
        'is_published' => true,
        'is_demo' => false,
        'show_on_dashboard' => true,
        'show_on_roadmap' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (Announcement $announcement): void {
            $announcement->is_published = $announcement->show_on_dashboard || $announcement->show_on_roadmap;
        });
    }

    protected function casts(): array
    {
        return [...parent::casts(), 'show_on_dashboard' => 'boolean', 'show_on_roadmap' => 'boolean'];
    }
}
