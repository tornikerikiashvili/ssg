<?php

namespace App\Models;

use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'country_code'];

    protected static function booted(): void
    {
        static::saving(function (Region $region): void {
            if ($region->country_code === null) {
                return;
            }

            $countries = config('map-countries');

            if (! array_key_exists($region->country_code, $countries)) {
                throw ValidationException::withMessages(['country_code' => 'Select a country from the map.']);
            }

            $region->name = $countries[$region->country_code];
        });
    }
}
