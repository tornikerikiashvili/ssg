<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogOptionSeeder::class);
        // Local accounts are explicitly created with LocalDemoSeeder.
    }
}
