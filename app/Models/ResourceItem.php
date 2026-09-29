<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceItem extends PortalContent
{
    public const KINDS = ['download' => 'Download', 'documentation' => 'Documentation', 'certificate' => 'Certificate'];

    public const DEMO_FILES = [
        'demo/marketing-pack.txt' => 'Demo marketing brief (TXT)',
        'demo/integration-guide.txt' => 'Demo integration guide (TXT)',
        'demo/sample-certificate.txt' => 'Sample certificate — not valid (TXT)',
        'demo/banner.svg' => 'Demo banner (SVG)',
    ];

    public function catalogOption(): BelongsTo
    {
        return $this->belongsTo(CatalogOption::class);
    }

    public function hasDownloadableFile(): bool
    {
        return array_key_exists($this->file_path ?? '', self::DEMO_FILES)
            || (bool) preg_match('~^game-assets/[a-zA-Z0-9_-]+\.[a-zA-Z0-9]+$~D', $this->file_path ?? '');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return parent::scopeVisibleTo($query, $user)->where(fn (Builder $assets) => $assets
            ->whereNull('game_id')
            ->orWhereHas('game', fn (Builder $games) => $games->visibleTo($user)));
    }
}
