<?php

namespace Database\Seeders;

use App\Models\CatalogOption;
use App\Models\ResourceItem;
use Illuminate\Database\Seeder;

class CatalogOptionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'category' => ['Crash', 'Slot', 'Instant', 'Mine'],
            'game_type' => ['Buy Bonus', 'Fruits', 'Crash Games'],
            'payout_type' => ['Lines', 'Ways', 'Tumble'],
            'volatility' => ['Low', 'Medium', 'High', 'Very High'],
            'asset' => ['Logos', 'Thumbnails', 'Game Covers', 'Videos', 'Banners', 'Promo Packs', 'Certificates'],
            'document' => ['Guide', 'API', 'Manual', 'Rules'],
        ] as $kind => $names) {
            foreach ($names as $order => $name) {
                CatalogOption::firstOrCreate(['kind' => $kind, 'name' => $name], ['sort_order' => $order]);
            }
        }
        foreach ([
            'demo/banner.svg' => ['asset', 'Banners'],
            'demo/marketing-pack.txt' => ['asset', 'Promo Packs'],
            'demo/sample-certificate.txt' => ['asset', 'Certificates'],
            'demo/integration-guide.txt' => ['document', 'Guide'],
        ] as $path => [$kind, $name]) {
            ResourceItem::where('is_demo', true)->whereNull('catalog_option_id')->where('file_path', $path)
                ->where('kind', $kind === 'document' ? 'documentation' : ($name === 'Certificates' ? 'certificate' : 'download'))
                ->update(['catalog_option_id' => CatalogOption::where('kind', $kind)->where('name', $name)->value('id')]);
        }
    }
}
