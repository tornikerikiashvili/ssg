<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Filament\Resources\Regions\Pages\ManageRegions;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Company;
use App\Models\Game;
use App\Models\GameRegion;
use App\Models\Region;
use App\Models\ResourceItem;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class RegionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_regions_can_be_created_updated_and_assigned_through_admin_forms(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageRegions::class)->callAction('create', data: ['country_code' => 'GE'])->assertHasNoActionErrors();
        $region = Region::where('name', 'Georgia')->firstOrFail();
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        Livewire::test(ManageCompanies::class)
            ->callAction(TestAction::make('edit')->table($company), data: ['regions' => [$region->id]])->assertHasNoActionErrors();
        Livewire::test(ManageUsers::class)
            ->mountAction(TestAction::make('edit')->table($user))
            ->assertFormFieldDoesNotExist('regions');
        $this->assertSame([$region->id], $company->regions()->pluck('regions.id')->all());
        $this->assertSame([$region->id], $user->accessibleRegionIds());
        $availability = GameRegion::factory()->for($region)->create();

        Livewire::test(ManageRegions::class)->callAction(TestAction::make('edit')->table($region), data: ['country_code' => 'DE'])
            ->assertHasNoActionErrors();
        $this->actingAs($user)->get('/games/'.$availability->game->slug)->assertOk()->assertSee('Germany');
        $this->get('/admin/regions')->assertForbidden();
    }

    public function test_users_inherit_company_regions_ignoring_legacy_overrides_and_revocation_applies_immediately(): void
    {
        $regions = Region::factory()->count(3)->create();
        $user = User::factory()->for(Company::factory())->create();
        $user->company->regions()->sync([$regions[0]->id, $regions[1]->id]);
        $available = GameRegion::factory()->for($regions[0])->create();
        $limited = GameRegion::factory()->for($regions[1])->create(['status' => 'limited']);
        $unavailable = GameRegion::factory()->for($regions[0])->create(['status' => 'unavailable']);
        $outside = GameRegion::factory()->for($regions[2])->create();
        $global = Game::factory()->create();
        $private = Game::factory()->for(Company::factory())->create();
        $draft = Game::factory()->create(['is_published' => false]);
        $this->actingAs($user);

        $this->assertEqualsCanonicalizing([$global->id, $private->id, $available->game_id, $limited->game_id], Game::visibleTo($user)->pluck('id')->all());
        DB::table('region_user')->insert(['user_id' => $user->id, 'region_id' => $regions[2]->id]);
        $this->assertEqualsCanonicalizing([$global->id, $private->id, $available->game_id, $limited->game_id], Game::visibleTo($user)->pluck('id')->all());
        $this->get('/games/'.$available->game->slug)->assertOk();
        $this->get('/games/'.$outside->game->slug)->assertNotFound();
        $this->get('/games/'.$unavailable->game->slug)->assertNotFound();
        $this->get('/games/'.$private->slug)->assertOk();
        $this->get('/games/'.$draft->slug)->assertNotFound();

        $user->company->regions()->detach($regions[1]);
        $this->get('/games/'.$limited->game->slug)->assertNotFound();
        $this->get('/games/'.$available->game->slug)->assertOk();
        $user->company->regions()->detach();
        $this->assertEqualsCanonicalizing([$global->id, $private->id], Game::visibleTo($user)->pluck('id')->all());
    }

    public function test_changing_company_switches_inherited_regions_for_the_user(): void
    {
        $regions = Region::factory()->count(2)->create();
        $first = Company::factory()->create();
        $second = Company::factory()->create();
        $first->regions()->attach($regions[0]);
        $second->regions()->attach($regions[1]);
        $user = User::factory()->for($first)->create();
        $colleague = User::factory()->for($first)->create();
        $this->assertSame([$regions[0]->id], $user->accessibleRegionIds());
        $this->assertSame($user->accessibleRegionIds(), $colleague->accessibleRegionIds());
        $user->load('company');
        $user->forceFill(['company_id' => $second->id])->save();
        $this->assertSame([$regions[1]->id], $user->accessibleRegionIds());
        $this->assertSame([$regions[0]->id], $colleague->accessibleRegionIds());
        $user->forceFill(['company_id' => null])->save();
        $this->assertSame([], $user->accessibleRegionIds());
    }

    public function test_regional_restrictions_cover_catalog_dashboard_documents_and_download_endpoints(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('game-assets/regional.pdf', 'Regional file');
        $availability = GameRegion::factory()->create();
        $game = $availability->game;
        $asset = ResourceItem::factory()->for($game)->create(['kind' => 'download', 'file_path' => 'game-assets/regional.pdf']);
        $document = ResourceItem::factory()->for($game)->create(['kind' => 'documentation', 'file_path' => 'game-assets/regional.pdf']);
        $user = User::factory()->for(Company::factory())->create();
        $user->company->purchasedGames()->attach($game);
        $this->actingAs($user);

        $this->get('/games')->assertViewHas('games', fn ($games): bool => $games->isEmpty());
        $this->get('/dashboard')->assertViewHas('gameCount', 0)->assertViewHas('assetCount', 0);
        $this->get('/resources/download?q='.urlencode($asset->title))->assertViewHas('resources', fn ($resources): bool => $resources->isEmpty());
        foreach ([$asset, $document] as $resource) {
            $this->get('/resource/'.$resource->id)->assertNotFound();
            $this->get('/resource/'.$resource->id.'/download')->assertNotFound();
        }
        $this->post('/games/'.$game->slug.'/assets/archive', ['ids' => [$asset->id]])->assertNotFound();
        $this->post('/assets/basket/archive', ['ids' => [$asset->id]])->assertNotFound();
        $this->assertDatabaseCount('resource_downloads', 0);

        $user->company->regions()->attach($availability->region_id);
        $this->get('/games/'.$game->slug)->assertOk()->assertSee($document->title);
        $this->get('/resource/'.$asset->id.'/download')->assertOk();
        $this->get('/resource/'.$document->id.'/download')->assertOk();
    }

    public function test_admin_region_bypass_does_not_bypass_publication_or_active_account_checks(): void
    {
        $availability = GameRegion::factory()->create(['status' => 'unavailable']);
        $draft = Game::factory()->create(['is_published' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertSame([$availability->game_id], Game::visibleTo($admin)->pluck('id')->all());
        $admin->is_active = false;
        $this->assertSame([], Game::visibleTo($admin)->pluck('id')->all());
    }

    public function test_migration_preserves_existing_regional_availability_and_reuses_region_names(): void
    {
        $roadmapMigration = require database_path('migrations/2026_09_29_163843_link_roadmap_games_and_announcement_placements.php');
        $roadmapMigration->down();
        $migration = require database_path('migrations/2026_09_29_143203_create_reusable_regions.php');
        $migration->down();
        $first = Game::factory()->create();
        $second = Game::factory()->create();
        DB::table('games')->where('id', $first->id)->update(['regions' => json_encode([
            ['country' => 'Georgia', 'status' => 'available'], ['country' => 'France', 'status' => 'unavailable'],
        ])]);
        DB::table('games')->where('id', $second->id)->update(['regions' => json_encode([
            ['country' => 'georgia', 'status' => 'limited'],
        ])]);

        $migration->up();

        $this->assertDatabaseCount('regions', 2);
        $georgia = Region::where('name', 'Georgia')->firstOrFail();
        $this->assertDatabaseHas('game_regions', ['game_id' => $first->id, 'region_id' => $georgia->id, 'status' => 'available']);
        $this->assertDatabaseHas('game_regions', ['game_id' => $second->id, 'region_id' => $georgia->id, 'status' => 'limited']);
        $migration->down();
        $this->assertSame('limited', json_decode(DB::table('games')->where('id', $second->id)->value('regions'), true)[0]['status']);
        $migration->up();
        $roadmapMigration->up();
    }
}
