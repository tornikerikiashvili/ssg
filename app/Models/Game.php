<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Game extends PortalContent implements HasMedia
{
    use InteractsWithMedia;

    protected $with = ['categoryTerm', 'media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])->singleFile();
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('cover') ?: ($this->cover_image ? asset($this->cover_image) : null);
    }

    public const CATEGORIES = ['Crash' => 'Crash', 'Instant' => 'Instant', 'Slot' => 'Slot'];

    public const COVERS = [
        'brand/demo/game-1.jpg' => 'Demo artwork 1',
        'brand/demo/game-2.png' => 'Demo artwork 2',
        'brand/demo/game-3.png' => 'Demo artwork 3',
    ];

    protected function casts(): array
    {
        return [...parent::casts(), 'dropbox_synced_at' => 'datetime', 'rtp' => 'decimal:2', 'min_bet' => 'decimal:2', 'max_bet' => 'decimal:2', 'release_date' => 'date', 'is_featured' => 'boolean', 'preview_enabled' => 'boolean', 'feature_tags' => 'array', 'specifications' => 'array'];
    }

    /** @return array<int, string> */
    public function getFeatureTagsAttribute(?string $value): array
    {
        return collect(json_decode($value ?? '[]', true) ?? [])
            ->flatMap(fn (string $tag): array => explode(',', $tag))
            ->map(fn (string $tag): string => trim($tag))
            ->filter(fn (string $tag): bool => $tag !== '')
            ->values()->all();
    }

    /** @return array<int, array{label: string, children: array<int, string>}> */
    public function getSpecificationsAttribute(?string $value): array
    {
        $specifications = json_decode($value ?? '[]', true) ?? [];

        if (array_is_list($specifications)) {
            return array_map(fn (array $item): array => [
                'label' => trim($item['label'].' '.($item['value'] ?? '')),
                'children' => $item['children'] ?? [],
            ], $specifications);
        }

        $items = [];
        foreach ($specifications as $label => $text) {
            $items[] = ['label' => trim($label.' '.$text), 'children' => []];
        }

        return $items;
    }

    public function specificationValue(string $label): ?string
    {
        foreach ($this->specifications as $specification) {
            if (preg_match('/^'.preg_quote($label, '/').'(?:\s*[:–-]\s*|\s+)(.+)$/u', $specification['label'], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    protected static function booted(): void
    {
        static::saving(function (Game $game): void {
            if ($game->isDirty('dropbox_folder_path')) {
                $game->dropbox_folder_path = filled($game->dropbox_folder_path) ? rtrim(trim($game->dropbox_folder_path), '/') : null;
                $game->dropbox_folder_id = null;
                $game->dropbox_synced_at = null;
                $game->dropbox_sync_error = null;
            }
            if (! $game->category_id) {
                $game->category_id = CatalogOption::firstOrCreate(['kind' => 'category', 'name' => $game->getAttributes()['category'] ?? 'Crash'])->id;
            }
        });
        static::saved(function (Game $game): void {
            if ($game->wasChanged('dropbox_folder_path')) {
                $game->resources()->whereNotNull('dropbox_file_id')->update(['dropbox_available' => false]);
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
