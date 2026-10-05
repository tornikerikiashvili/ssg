<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Banner extends PortalContent
{
    public const PAGES = ['dashboard' => 'Dashboard', 'games' => 'Games', 'roadmap' => 'Roadmap'];

    public const ARTWORK = ['crash' => 'Original crash artwork', 'cheesy' => 'Original Cheesy Road artwork'];

    public const TARGETS = ['none' => 'No button', 'game' => 'Linked game', 'assets' => 'Linked game assets', 'url' => 'Website URL'];

    protected function casts(): array
    {
        return [...parent::casts(), 'pages' => 'array', 'show_game_stats' => 'boolean', 'use_original_video' => 'boolean'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->canAccessClientArea()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('is_published', true)->where(fn (Builder $banners) => $banners->whereNull('game_id')
            ->orWhereHas('game', fn (Builder $games) => $games->visibleTo($user)));
    }

    public function mediaUrl(string $field): ?string
    {
        $path = $this->getAttribute($field);

        return is_string($path) && str_starts_with($path, 'banners/') && ! str_contains($path, '..')
            ? Storage::disk('public')->url($path) : null;
    }

    public function artworkUrl(): ?string
    {
        return $this->mediaUrl('image_path') ?? match ($this->original_artwork) {
            'crash' => asset('client-design/images/7680b7339e68db2788ae2443fcf8f92994cdf7ac.png'),
            'cheesy' => asset('client-design/images/e020298b5f0f8d25d27842e7c8a17ba9d424f6a5.png'),
            default => null,
        };
    }

    public function buttonUrl(string $button): ?string
    {
        $target = $this->getAttribute($button.'_target');
        $url = $this->getAttribute($button.'_url');

        if ($button === 'secondary') {
            return is_string($url) && preg_match('~^https?://~i', $url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
        }

        return match ($target) {
            'game' => $this->game ? route('games.show', $this->game->slug) : null,
            'assets' => $this->game?->release_status === 'released' ? route('games.show', $this->game->slug).'#resources' : null,
            'url' => is_string($url) && preg_match('~^https?://~i', $url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null,
            default => null,
        };
    }
}
