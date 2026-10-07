<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FileFormat extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $with = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->useDisk('public')
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])->singleFile();
    }

    public static function forFilename(string $filename): ?self
    {
        $extension = mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $extension !== '' ? static::firstOrCreate(['extension' => $extension]) : null;
    }
}
