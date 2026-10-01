<?php

namespace Database\Seeders;

use App\Models\PortalNotification;
use Illuminate\Database\Seeder;

class PortalNotificationSeeder extends Seeder
{
    public function run(): void
    {
        PortalNotification::firstOrCreate(['title' => 'Welcome to your notifications', 'is_demo' => true], [
            'teaser' => 'The latest updates for your team.',
            'description' => 'Sample notification for previewing partner updates.',
            'color' => 'blue', 'is_demo' => true, 'is_published' => false,
        ]);
    }
}
