<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::firstOrCreate(['title' => 'Available Soon', 'is_demo' => true], [
            'label' => 'Coming Next', 'pages' => ['roadmap'], 'variant' => 'blue',
            'original_artwork' => 'cheesy', 'use_original_video' => true,
            'show_game_stats' => false, 'is_published' => false,
        ]);
    }
}
