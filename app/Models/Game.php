<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends PortalContent
{
    protected $with = ['categoryTerm'];

    public const CATEGORIES = ['Crash' => 'Crash', 'Instant' => 'Instant', 'Slot' => 'Slot'];

    public const COVERS = [
        'brand/demo/game-1.jpg' => 'Demo artwork 1',
        'brand/demo/game-2.png' => 'Demo artwork 2',
        'brand/demo/game-3.png' => 'Demo artwork 3',
    ];

    protected function casts(): array
    {
        return [...parent::casts(), 'rtp' => 'decimal:2', 'release_date' => 'date', 'is_featured' => 'boolean', 'preview_enabled' => 'boolean', 'feature_tags' => 'array', 'specifications' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Game $game): void {
            if (! $game->category_id) {
                $game->category_id = CatalogOption::firstOrCreate(['kind' => 'category', 'name' => $game->getAttributes()['category'] ?? 'Crash'])->id;
            }
        });
    }

    public function categoryTerm(): BelongsTo
    {
        return $this->belongsTo(CatalogOption::class, 'category_id');
    }

    public function gameType(): BelongsTo
    {
        return $this->belongsTo(CatalogOption::class, 'game_type_id');
    }

    public function payoutType(): BelongsTo
    {
        return $this->belongsTo(CatalogOption::class, 'payout_type_id');
    }

    public function volatility(): BelongsTo
    {
        return $this->belongsTo(CatalogOption::class, 'volatility_id');
    }

    public function engagementTools(): BelongsToMany
    {
        return $this->belongsToMany(EngagementTool::class);
    }

    public function getCategoryAttribute(?string $value): ?string
    {
        return $this->categoryTerm?->name ?? $value;
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ResourceItem::class);
    }

    public function regionAvailabilities(): HasMany
    {
        return $this->hasMany(GameRegion::class);
    }

    public function roadmapItems(): HasMany
    {
        return $this->hasMany(RoadmapItem::class);
    }

    public function scopeRoadmapVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->canAccessClientArea()) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->is_admin) {
            return $query;
        }

        return $query->where(fn (Builder $games) => $games->whereNull('company_id')->orWhere('company_id', $user->company_id))
            ->where(fn (Builder $games) => $games->whereDoesntHave('regionAvailabilities')
                ->orWhereHas('regionAvailabilities', fn (Builder $regions) => $regions->whereIn('region_id', $user->accessibleRegionIds())->whereIn('status', ['available', 'limited', 'upcoming'])));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        parent::scopeVisibleTo($query, $user);

        if ($user->is_admin) {
            return $query;
        }

        $query->where(fn (Builder $games) => $games->where('release_status', 'released')->orWhere('preview_enabled', true));
        $regionIds = $user->accessibleRegionIds();

        return $query->where(fn (Builder $games): Builder => $games
            ->whereDoesntHave('regionAvailabilities')
            ->orWhereHas('regionAvailabilities', fn (Builder $regions): Builder => $regions
                ->whereIn('region_id', $regionIds)
                ->whereIn('status', ['available', 'limited'])));
    }
}
