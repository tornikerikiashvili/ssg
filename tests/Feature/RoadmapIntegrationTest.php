<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Filament\Resources\RoadmapItems\Pages\ManageRoadmapItems;
use App\Models\Announcement;
use App\Models\Company;
use App\Models\Game;
use App\Models\Region;
use App\Models\ResourceItem;
use App\Models\RoadmapItem;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class RoadmapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    public function test_roadmap_reuses_game_data_and_respects_preview_and_download_permissions(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo/banner.svg', 'Demo asset');
        $user = User::factory()->for(Company::factory())->create();
        $game = Game::factory()->create(['title' => 'Shared game', 'release_status' => 'upcoming']);
        $milestone = RoadmapItem::factory()->create(['game_id' => $game->id, 'title' => 'Initial launch', 'progress' => 35]);
        $asset = ResourceItem::factory()->for($game)->create(['file_path' => 'demo/banner.svg']);
        $this->actingAs($user)->get('/roadmap')->assertOk()->assertSee('Shared game')->assertSee('35%')
            ->assertDontSee(route('games.show', $game->slug), false);
        $this->get('/games/'.$game->slug)->assertNotFound();
        $game->update(['preview_enabled' => true, 'title' => 'Renamed shared game']);
        $this->get('/roadmap')->assertOk()->assertSee('Renamed shared game')->assertSee(route('games.show', $game->slug), false);
        $this->get('/games/'.$game->slug)->assertOk()->assertDontSee($asset->title);
        $this->get('/resource/'.$asset->id.'/download')->assertNotFound();
        $this->post('/assets/basket/archive', ['ids' => [$asset->id]])->assertNotFound();
        $user->company->purchasedGames()->attach($game);
        $game->update(['release_status' => 'released']);
        $this->get('/resource/'.$asset->id.'/download')->assertOk();
        $milestone->update(['progress' => null]);
        $this->get('/roadmap')->assertDontSee('role="progressbar"', false);
    }

    public function test_roadmap_honors_company_region_and_draft_milestone_visibility(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $region = Region::factory()->create(['name' => 'Allowed region']);
        $otherRegion = Region::factory()->create(['name' => 'Private region']);
        $user->company->regions()->attach($region);
        $game = Game::factory()->create(['release_status' => 'upcoming', 'is_published' => false]);
        $game->regionAvailabilities()->create(['region_id' => $region->id, 'status' => 'upcoming']);
        $game->regionAvailabilities()->create(['region_id' => $otherRegion->id, 'status' => 'available']);
        RoadmapItem::factory()->create(['game_id' => $game->id, 'region_id' => $region->id, 'title' => 'Allowed regional launch']);
        RoadmapItem::factory()->create(['game_id' => $game->id, 'region_id' => $otherRegion->id, 'title' => 'Private regional launch']);
        RoadmapItem::factory()->create(['game_id' => $game->id, 'is_published' => false, 'title' => 'Draft milestone']);
        $private = Game::factory()->for(Company::factory())->create(['title' => 'Private game']);
        RoadmapItem::factory()->create(['game_id' => $private->id, 'title' => 'Private game launch']);
        $this->actingAs($user)->get('/roadmap')->assertOk()->assertSee('Allowed regional launch')->assertSee('Upcoming:')
            ->assertDontSee('Private regional launch')->assertDontSee('Private region')->assertDontSee('Draft milestone')->assertSee('Private game launch');
        $this->get('/games/'.$game->slug)->assertNotFound();
    }

    public function test_announcement_placements_are_independent_and_keep_audience_rules(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        Announcement::factory()->create(['title' => 'Dashboard news only', 'show_on_dashboard' => true, 'show_on_roadmap' => false]);
        Announcement::factory()->create(['title' => 'Roadmap news only', 'show_on_dashboard' => false, 'show_on_roadmap' => true]);
        Announcement::factory()->create(['title' => 'Shared news', 'show_on_dashboard' => true, 'show_on_roadmap' => true]);
        Announcement::factory()->for(Company::factory())->create(['title' => 'Private news', 'show_on_roadmap' => true]);
        $this->actingAs($user)->get('/roadmap')->assertOk()->assertSee('Roadmap news only')->assertSee('Shared news')->assertDontSee('Private news')
            ->assertViewHas('announcements', fn ($news) => ! $news->contains('title', 'Dashboard news only'));
        $this->get('/dashboard')->assertViewHas('announcements', fn ($news) => $news->pluck('title')->sort()->values()->all() === ['Dashboard news only', 'Shared news']);
    }

    public function test_admin_can_manage_linked_milestones_and_announcement_placements(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $game = Game::factory()->create();
        $data = ['title' => 'Launch milestone', 'slug' => 'launch-milestone', 'game_id' => $game->id, 'milestone_type' => 'initial_release', 'status' => 'testing', 'target_quarter' => '2027 Q1', 'progress' => 75, 'is_published' => true];
        Livewire::test(ManageRoadmapItems::class)->callAction('create', data: $data)->assertHasNoActionErrors();
        $this->assertDatabaseHas('roadmap_items', ['game_id' => $game->id, 'progress' => 75, 'target_quarter' => '2027 Q1']);
        Livewire::test(ManageRoadmapItems::class)->callAction('create', data: [...$data, 'slug' => 'invalid-progress', 'progress' => 101])->assertHasActionErrors(['progress']);
        Livewire::test(ManageAnnouncements::class)->callAction('create', data: ['title' => 'Roadmap notice', 'priority' => 'info', 'show_on_dashboard' => false, 'show_on_roadmap' => true, 'is_published' => true])->assertHasNoActionErrors();
        $this->assertDatabaseHas('announcements', ['title' => 'Roadmap notice', 'show_on_dashboard' => false, 'show_on_roadmap' => true]);
    }
}
