<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

class EngagementTool extends PortalContent
{
    protected $attributes = ['is_published' => true, 'is_demo' => false];

    public function coverImageUrl(): ?string
    {
        return is_string($this->cover_image) && str_starts_with($this->cover_image, 'engagement-tools/') && ! str_contains($this->cover_image, '..')
            ? Storage::disk('public')->url($this->cover_image) : null;
    }
}
