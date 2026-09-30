<?php

namespace App\Models;

use Database\Factories\GameRegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRegion extends Model
{
    /** @use HasFactory<GameRegionFactory> */
    use HasFactory;

    public const STATUSES = ['available' => 'Available', 'limited' => 'Limited', 'unavailable' => 'Not available', 'upcoming' => 'Upcoming'];

    protected $fillable = ['region_id', 'status'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
