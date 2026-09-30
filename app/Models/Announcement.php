<?php

namespace App\Models;

class Announcement extends PortalContent
{
    protected function casts(): array
    {
        return [...parent::casts(), 'show_on_dashboard' => 'boolean', 'show_on_roadmap' => 'boolean'];
    }
}
