<?php

namespace App\Models;

class RoadmapItem extends PortalContent
{
    public const STATUSES = ['planned' => 'Planned', 'in_progress' => 'In progress', 'released' => 'Released'];

    protected function casts(): array
    {
        return [...parent::casts(), 'target_date' => 'date'];
    }
}
