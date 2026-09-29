<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogOption extends Model
{
    use HasFactory;

    protected $fillable = ['kind', 'name', 'sort_order'];

    public const KINDS = ['category' => 'Game categories', 'game_type' => 'Game types', 'payout_type' => 'Payout types', 'volatility' => 'Volatility levels', 'asset' => 'Asset categories', 'document' => 'Document types'];

    public static function options(string $kind): array
    {
        return static::where('kind', $kind)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all();
    }
}
