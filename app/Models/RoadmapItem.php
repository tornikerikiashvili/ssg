<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadmapItem extends PortalContent
{
    public const STATUSES = ['concept' => 'Concept', 'planned' => 'Planning', 'in_progress' => 'Under Development', 'testing' => 'Testing', 'released' => 'Released'];

    public const TYPES = ['initial_release' => 'Initial release', 'regional_launch' => 'Regional launch', 'update' => 'Major update', 'general' => 'General plan'];

    protected function casts(): array
    {
        return [...parent::casts(), 'target_date' => 'date', 'progress' => 'integer'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        parent::scopeVisibleTo($query, $user);
        $query->where(fn (Builder $items) => $items->whereNull('game_id')
            ->orWhereHas('game', fn (Builder $games) => $games->roadmapVisibleTo($user)));
        if (! $user->is_admin) {
            $query->where(fn (Builder $items) => $items->whereNull('region_id')->orWhereIn('region_id', $user->accessibleRegionIds()));
        }

        return $query;
    }
}
