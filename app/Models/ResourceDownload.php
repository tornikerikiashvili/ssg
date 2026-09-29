<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceDownload extends Model
{
    protected $fillable = ['user_id', 'resource_item_id'];

    protected static function booted(): void
    {
        static::creating(function (ResourceDownload $download): void {
            $resource = ResourceItem::find($download->resource_item_id);
            $download->resource_title = $resource?->title;
            $download->file_type = strtoupper(pathinfo($resource?->file_path ?? '', PATHINFO_EXTENSION));
        });
    }

    public static function recentFor(User $user): Collection
    {
        return static::where('user_id', $user->id)
            ->with(['resource' => fn ($query) => $query->visibleTo($user)])
            ->latest()->orderByDesc('id')->limit(8)->get();
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceItem::class, 'resource_item_id');
    }
}
