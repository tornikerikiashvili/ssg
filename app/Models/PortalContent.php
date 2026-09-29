<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class PortalContent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $attributes = ['is_published' => false, 'is_demo' => false];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $query->where('is_published', true);

        if (! $user->canAccessClientArea()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->is_admin) {
            return $query;
        }

        return $query->where(fn (Builder $audience) => $audience
            ->whereNull('company_id')->orWhere('company_id', $user->company_id));
    }
}
