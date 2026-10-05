<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceItem extends PortalContent
{
    public const KINDS = ['download' => 'Download', 'documentation' => 'Documentation', 'license' => 'License', 'certificate' => 'Certification'];

    public const DEMO_FILES = [
        'demo/marketing-pack.txt' => 'Demo marketing brief (TXT)',
        'demo/integration-guide.txt' => 'Demo integration guide (TXT)',
        'demo/sample-certificate.txt' => 'Sample certificate — not valid (TXT)',
        'demo/banner.svg' => 'Demo banner (SVG)',
    ];

    protected function casts(): array
    {
        return [...parent::casts(), 'show_on_engagement_tools' => 'boolean'];
    }

    public function isDocumentationLink(): bool
    {
        return $this->kind === 'documentation' && $this->documentation_type === 'link';
    }

    public function documentationUrl(): string
    {
        return route($this->isDocumentationLink() ? 'resources.show' : 'resources.download', $this->id);
    }

    public function catalogOption(): BelongsTo
    {
        return $this->belongsTo(CatalogOption::class);
    }

    public function hasDownloadableFile(): bool
    {
        if ($this->isDocumentationLink()) {
            return false;
        }

        if ($this->dropbox_file_id) {
            return (bool) $this->dropbox_available;
        }

        return array_key_exists($this->file_path ?? '', self::DEMO_FILES)
            || (bool) preg_match('~^(?:game-assets|portal-resources)/[a-zA-Z0-9_-]+\.[a-zA-Z0-9]+$~D', $this->file_path ?? '');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return parent::scopeVisibleTo($query, $user)
            ->where(fn (Builder $files) => $files->whereNull('dropbox_file_id')->orWhere('dropbox_available', true))
            ->where(fn (Builder $assets) => $assets
                ->whereNull('game_id')
                ->orWhereHas('game', fn (Builder $games) => $games->visibleTo($user)->where('release_status', 'released')->purchasedBy($user)));
    }
}
