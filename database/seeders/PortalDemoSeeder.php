<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Company;
use App\Models\EngagementTool;
use App\Models\Game;
use App\Models\ResourceItem;
use App\Models\RoadmapItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo content is restricted to local and testing environments.');
        }

        if (app()->environment('local')) {
            $this->call(LocalDemoSeeder::class);
        }

        $files = [
            'demo/marketing-pack.txt' => "SMARTSOFT DEMO MARKETING BRIEF\n\nSample file for testing downloads.\nCampaign: Autumn game launch\nFormats: square, landscape and portrait\nThis is fictional content. It is not approved marketing material.\n",
            'demo/integration-guide.txt' => "SMARTSOFT DEMO INTEGRATION GUIDE\n\n1. Request sandbox access from your account manager.\n2. Review the integration checklist.\n3. Schedule a test session.\n\nThis is a sample document, not a real API specification.\n",
            'demo/sample-certificate.txt' => "SAMPLE CERTIFICATE — NOT VALID\n\nFictional certificate for testing the client area.\nNo licensing, certification, regulatory approval, or legal validity is represented.\n",
            'demo/banner.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="628"><rect width="1200" height="628" fill="#191a1c"/><text x="80" y="260" font-family="Arial" font-size="80" fill="#f15b4e">SMARTSOFT DEMO</text><text x="80" y="345" font-family="Arial" font-size="30" fill="white">Sample campaign banner — test use only</text></svg>',
        ];
        foreach ($files as $path => $contents) {
            if (! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, $contents);
            }
        }

        DB::transaction(function () {
            $partner = Company::firstOrCreate(['name' => 'Demo Partner']);
            $other = Company::firstOrCreate(['name' => 'Demo Europe Partner']);
            $covers = array_keys(Game::COVERS);
            $games = [
                ['JetX', 'Crash'], ['BalloonX', 'Crash'], ['PropelX', 'Crash'],
                ['Chicken Highway', 'Instant'], ['World Champion X', 'Instant'],
                ['Cheesy Road', 'Instant'], ['Neon Reels', 'Slot'], ['Orbit Run', 'Crash'],
            ];
            foreach ($games as $index => [$name, $category]) {
                $game = Game::firstOrCreate(['slug' => 'demo-'.Str::slug($name)], [
                    'title' => $name,
                    'category' => $category,
                    'description' => "Demo profile for {$name}. Explore the game information and download sample marketing resources. All specifications and release dates on this record are fictional test data.",
                    'rtp' => 96 + ($index % 3) * 0.5,
                    'release_date' => now()->subDays(10 + $index * 14)->toDateString(),
                    'cover_image' => $covers[$index % count($covers)],
                    'is_featured' => $index < 3,
                    'is_published' => true,
                    'is_demo' => true,
                ]);
                foreach (['Marketing brief' => 'demo/marketing-pack.txt', 'Campaign banner' => 'demo/banner.svg'] as $title => $file) {
                    ResourceItem::firstOrCreate(['slug' => $game->slug.'-'.Str::slug($title)], [
                        'title' => $name.' — '.$title,
                        'kind' => 'download', 'game_id' => $game->id,
                        'description' => 'Sample resource for testing the download center. Not approved for commercial use.',
                        'file_path' => $file, 'is_published' => true, 'is_demo' => true,
                    ]);
                }
            }

            $privateGame = Game::firstOrCreate(['slug' => 'demo-europe-exclusive'], [
                'title' => 'Europe partner exclusive', 'category' => 'Slot',
                'description' => 'A company-specific demo record. The default Demo Client must not see this game.',
                'company_id' => $other->id, 'is_published' => true, 'is_demo' => true,
                'cover_image' => $covers[2],
            ]);
            Game::firstOrCreate(['slug' => 'demo-unpublished-game'], [
                'title' => 'Unpublished concept', 'category' => 'Instant',
                'description' => 'Draft record for testing publication controls.', 'is_published' => false, 'is_demo' => true,
            ]);
            ResourceItem::firstOrCreate(['slug' => 'demo-private-game-brief'], [
                'title' => 'Europe exclusive brief', 'kind' => 'download',
                'game_id' => $privateGame->id, 'file_path' => 'demo/marketing-pack.txt',
                'description' => 'This resource inherits the restricted game visibility.',
                'is_published' => true, 'is_demo' => true,
            ]);

            foreach (['Integration quick start', 'Back office manual', 'Launch checklist'] as $title) {
                ResourceItem::firstOrCreate(['slug' => 'demo-'.Str::slug($title)], [
                    'title' => $title, 'kind' => 'documentation',
                    'description' => "DEMO DOCUMENT\n\nThis sample demonstrates how partners will browse technical and operational documentation.\n\nBefore launch, confirm the available markets, request sandbox credentials, and agree on the test plan with your SmartSoft representative.\n\nThis content is fictional and does not describe production API endpoints.",
                    'file_path' => 'demo/integration-guide.txt', 'is_published' => true, 'is_demo' => true,
                ]);
            }
            foreach (['Sample game certificate', 'Sample market license'] as $title) {
                ResourceItem::firstOrCreate(['slug' => 'demo-'.Str::slug($title)], [
                    'title' => $title, 'kind' => 'certificate',
                    'description' => 'SAMPLE ONLY — NOT VALID. This fictional record is provided to test certificate browsing and downloads. It does not represent regulatory approval.',
                    'file_path' => 'demo/sample-certificate.txt', 'is_published' => true, 'is_demo' => true,
                ]);
            }
            ResourceItem::firstOrCreate(['slug' => 'demo-partner-onboarding'], [
                'title' => 'Your company onboarding plan', 'kind' => 'documentation',
                'description' => 'Company-specific demo onboarding instructions for Demo Partner.',
                'company_id' => $partner->id, 'file_path' => 'demo/integration-guide.txt',
                'is_published' => true, 'is_demo' => true,
            ]);
            foreach ([
                ['Welcome to your demo workspace', 'Browse the games, open documents, and download sample files. All content marked Demo is fictional.', 'info', null],
                ['New marketing resources available', 'Sample campaign banners and marketing briefs are ready in the Download Center.', 'info', null],
                ['Planned sandbox maintenance', 'Demo notice: a fictional maintenance window for testing important announcements.', 'important', null],
                ['Your onboarding is ready', 'A company-specific demo announcement for Demo Partner.', 'info', $partner->id],
                ['Europe partner notice', 'Restricted demo announcement for the other company.', 'info', $other->id],
            ] as [$title, $description, $priority, $companyId]) {
                Announcement::firstOrCreate(['slug' => 'demo-'.Str::slug($title)], [
                    'title' => $title, 'description' => $description, 'priority' => $priority,
                    'company_id' => $companyId, 'is_published' => true, 'is_demo' => true,
                ]);
            }
            foreach ([['New instant game concept', 'planned', 45], ['Regional resource pack', 'in_progress', 21], ['Partner knowledge center', 'released', -7], ['Autumn promotion toolkit', 'planned', 60]] as [$title, $status, $days]) {
                RoadmapItem::firstOrCreate(['slug' => 'demo-'.Str::slug($title)], [
                    'title' => $title, 'description' => 'Fictional roadmap entry for testing. This is not a delivery commitment.',
                    'status' => $status, 'target_date' => now()->addDays($days)->toDateString(),
                    'is_published' => true, 'is_demo' => true,
                ]);
            }
            foreach ([['Coin Flip', 'Promotion'], ['Leaderboard', 'Competition'], ['Prize Drop', 'Retention']] as [$title, $category]) {
                EngagementTool::firstOrCreate(['slug' => 'demo-'.Str::slug($title)], [
                    'title' => $title, 'category' => $category,
                    'description' => "Demo overview of {$title}. Use this page to test how engagement products are presented to partners. Campaign activation and game integration are not connected in this demo.",
                    'is_published' => true, 'is_demo' => true,
                ]);
            }
        });

        $this->command?->info('Demo content is ready. Existing records and CMS edits were preserved.');
    }
}
