<?php

namespace App;

use App\Models\ResourceItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DropboxThumbnail
{
    public function __construct(private DropboxClient $dropbox) {}

    public function get(ResourceItem $resource): ?string
    {
        if (! $resource->supportsDropboxThumbnail()) {
            return null;
        }
        $hash = hash('sha256', 'transparent-v1|'.$resource->dropbox_file_id.'|'.$resource->dropbox_revision);
        $path = 'dropbox-thumbnails/'.$hash.'.png';
        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            return $disk->get($path);
        }
        $failureKey = 'dropbox-thumbnail-failed-'.$hash;
        if (Cache::has($failureKey)) {
            return null;
        }
        $lock = Cache::lock('dropbox-thumbnail-'.$hash, 30);
        if (! $lock->get()) {
            return null;
        }
        try {
            if ($disk->exists($path)) {
                return $disk->get($path);
            }
            $bytes = $this->dropbox->thumbnail($resource->dropbox_file_id);
            $disk->put($path, $bytes);

            return $bytes;
        } catch (Throwable) {
            Cache::put($failureKey, true, now()->addMinutes(5));

            return null;
        } finally {
            $lock->release();
        }
    }
}
