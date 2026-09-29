<?php

namespace App\Models;

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
        return [...parent::casts(), 'rtp' => 'decimal:2', 'release_date' => 'date', 'is_featured' => 'boolean', 'feature_tags' => 'array', 'specifications' => 'array', 'regions' => 'array'];
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
}
