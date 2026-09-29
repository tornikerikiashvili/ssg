<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Company;
use App\Models\Game;
use App\Models\ResourceDownload;
use App\Models\ResourceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Database refresh is restricted to smartsoft_testing.');
        }
    }

    public function test_dashboard_statistics_and_content_follow_company_access_and_dates(): void
    {
        $this->travelTo(now()->startOfDay());
        $user = User::factory()->for(Company::factory())->create();
        $own = Game::factory()->create(['company_id' => $user->company_id, 'title' => 'Own live game', 'is_featured' => true]);
        Game::factory()->create(['title' => 'Older shared game', 'created_at' => now()->subDays(9)]);
        $private = Game::factory()->for(Company::factory())->create(['title' => 'Secret game', 'is_featured' => true]);
        Game::factory()->create(['is_published' => false, 'title' => 'Unpublished game']);
        ResourceItem::factory()->create(['game_id' => $own->id]);
        ResourceItem::factory()->create(['created_at' => now()->subDays(9)]);
        ResourceItem::factory()->create(['game_id' => $private->id]);
        ResourceItem::factory()->create(['kind' => 'documentation']);
        Announcement::factory()->create(['title' => 'Visible update', 'company_id' => $user->company_id]);
        Announcement::factory()->for(Company::factory())->create(['title' => 'Secret update']);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('gameCount', 2)->assertViewHas('newGameCount', 1)
            ->assertViewHas('newAssetCount', 1)->assertViewHas('assetCount', 2)
            ->assertViewHas('featuredCount', 1)->assertViewHas('downloadCount', 0)
            ->assertSee('Own live game')->assertSee('Visible update')
            ->assertDontSee('Secret game')->assertDontSee('Unpublished game')->assertDontSee('Secret update');
        $own->update(['title' => 'Updated live game', 'is_featured' => false]);
        $this->get('/dashboard')->assertViewHas('featuredCount', 0)->assertSee('Updated live game')->assertDontSee('Own live game');
    }

    public function test_download_tracking_counts_only_authorized_existing_files_for_the_current_user(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo/marketing-pack.txt', 'Example file');
        $user = User::factory()->for(Company::factory())->create();
        $other = User::factory()->for(Company::factory())->create();
        $resource = ResourceItem::factory()->create(['file_path' => 'demo/marketing-pack.txt']);
        $private = ResourceItem::factory()->create(['company_id' => $other->company_id, 'file_path' => 'demo/marketing-pack.txt']);
        $missing = ResourceItem::factory()->create(['file_path' => 'demo/banner.svg']);
        ResourceDownload::create(['user_id' => $other->id, 'resource_item_id' => $resource->id]);
        $this->actingAs($user)->get('/resource/'.$private->id.'/download')->assertNotFound();
        $this->get('/resource/'.$missing->id.'/download')->assertNotFound();
        $this->head('/resource/'.$resource->id.'/download')->assertOk();
        $this->assertSame(0, ResourceDownload::where('user_id', $user->id)->count());
        $this->get('/resource/'.$resource->id.'/download')->assertOk()->assertStreamedContent('Example file');
        $this->get('/dashboard')->assertViewHas('downloadCount', 1)->assertViewHas('weeklyDownloadCount', 1)
            ->assertViewHas('recentDownloads', fn ($events) => $events->count() === 1 && $events->first()->resource_item_id === $resource->id);
        $resource->update(['is_published' => false]);
        $this->get('/dashboard')->assertViewHas('downloadCount', 1)->assertViewHas('recentDownloads', fn ($events) => $events->count() === 1 && $events->first()->resource === null)->assertSee('Unavailable');
    }

    public function test_download_history_survives_sessions_time_and_asset_deletion(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $other = User::factory()->for(Company::factory())->create();
        $resource = ResourceItem::factory()->create(['title' => 'Archived partner asset', 'file_path' => 'demo/banner.svg']);
        $event = ResourceDownload::create(['user_id' => $user->id, 'resource_item_id' => $resource->id]);
        $event->update(['created_at' => now()->subYears(2)]);
        $resource->update(['title' => 'Renamed asset']);
        $resource->delete();
        $this->assertDatabaseHas('resource_downloads', ['id' => $event->id, 'resource_item_id' => null, 'resource_title' => 'Archived partner asset', 'file_type' => 'SVG']);
        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        foreach (['/dashboard', '/games', '/resources/download'] as $path) {
            $this->get($path)->assertOk()->assertSee('Archived partner asset')->assertSee('Unavailable');
            $this->actingAs($other)->get($path)->assertOk()->assertDontSee('Archived partner asset');
            $this->actingAs($user);
        }
    }

    public function test_empty_dashboard_and_user_supplied_titles_render_safely(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('No featured games yet')->assertViewHas('gameCount', 0);
        Game::factory()->create(['title' => '<script>bad()</script>', 'is_featured' => true, 'rtp' => null]);
        $this->get('/dashboard')->assertOk()->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)->assertDontSee('<script>bad()</script>', false);
    }
}
