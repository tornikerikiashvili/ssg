<?php

namespace Tests\Feature;

use App\Filament\Resources\Regions\Pages\ManageRegions;
use App\Models\Game;
use App\Models\GameRegion;
use App\Models\Region;
use App\Models\User;
use Dom\HTMLDocument;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class RegionCountryTest extends TestCase
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
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_country_selection_saves_the_map_key_and_name_and_allows_unchanged_edits(): void
    {
        Livewire::test(ManageRegions::class)
            ->callAction('create', data: ['country_code' => 'GE'])
            ->assertHasNoActionErrors();

        $region = Region::where('country_code', 'GE')->firstOrFail();
        $this->assertSame('Georgia', $region->name);

        Livewire::test(ManageRegions::class)
            ->callAction(TestAction::make('edit')->table($region), data: ['country_code' => 'GE'])
            ->assertHasNoActionErrors();

        Livewire::test(ManageRegions::class)
            ->callAction('create', data: ['country_code' => '_somaliland'])
            ->assertHasNoActionErrors();
        $this->assertDatabaseHas('regions', ['country_code' => '_somaliland', 'name' => 'Somaliland']);
    }

    public function test_region_form_rejects_missing_unknown_and_duplicate_country_codes(): void
    {
        Region::factory()->create(['country_code' => 'GE']);

        foreach ([null, 'ZZ', 'ge', 'GE'] as $code) {
            Livewire::test(ManageRegions::class)
                ->callAction('create', data: ['country_code' => $code])
                ->assertHasActionErrors(['country_code']);
        }

        $this->assertDatabaseCount('regions', 1);
    }

    public function test_mapping_a_legacy_region_preserves_its_availability_assignments(): void
    {
        $region = Region::factory()->create(['name' => 'Georgia market']);
        $availability = GameRegion::factory()->for($region)->create();

        Livewire::test(ManageRegions::class)
            ->callAction(TestAction::make('edit')->table($region), data: ['country_code' => 'GE'])
            ->assertHasNoActionErrors();

        $this->assertSame('Georgia', $region->fresh()->name);
        $this->assertSame($region->id, $availability->fresh()->region_id);
        $this->assertDatabaseHas('regions', ['id' => $region->id, 'country_code' => 'GE']);
    }

    public function test_migration_backfills_matching_countries_without_guessing_custom_or_ambiguous_regions(): void
    {
        $migration = require database_path('migrations/2026_09_30_142950_add_country_code_to_regions_table.php');
        $migration->down();
        $georgia = Region::factory()->create(['name' => 'georgia']);
        $custom = Region::factory()->create(['name' => 'Europe']);
        $germany = Region::factory()->create(['name' => 'Germany']);
        $duplicate = Region::factory()->create(['name' => 'germany']);
        $availability = GameRegion::factory()->for($georgia)->create();

        $migration->up();

        $this->assertSame('GE', $georgia->fresh()->country_code);
        $this->assertNull($custom->fresh()->country_code);
        $this->assertNull($germany->fresh()->country_code);
        $this->assertNull($duplicate->fresh()->country_code);
        $this->assertSame($georgia->id, $availability->fresh()->region_id);
        $this->assertSame('Europe', $custom->fresh()->name);
    }

    public function test_game_page_renders_map_countries_with_their_saved_availability_colors(): void
    {
        $game = Game::factory()->create();
        $expected = [
            'GE' => ['available', '#4fc6e0', 'Georgia — Available'],
            'DE' => ['limited', '#f15b4e', 'Germany — Limited'],
            'US' => ['unavailable', '#8b8d92', 'United States — Not available'],
            'GB' => ['upcoming', '#eab85b', 'United Kingdom — Upcoming'],
        ];

        foreach ($expected as $code => [$status]) {
            $region = Region::factory()->create(['country_code' => $code]);
            GameRegion::factory()->for($game)->for($region)->create(['status' => $status]);
        }

        $response = $this->get('/games/'.$game->slug)->assertOk();
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $paths = $document->querySelectorAll('.regions-map [data-country]');
        $codes = [];

        foreach ($paths as $path) {
            $codes[] = $path->getAttribute('data-country');
        }

        $this->assertEqualsCanonicalizing(array_keys(config('map-countries')), $codes);

        foreach ($expected as $code => [$status, $color, $label]) {
            $path = $document->querySelector('.regions-map [data-country="'.$code.'"]');
            $this->assertSame($status, $path->getAttribute('data-status'));
            $this->assertSame($color, $path->getAttribute('fill'));
            $this->assertSame($label, $path->querySelector('title')->textContent);
            $this->assertNotEmpty($path->getAttribute('d'));
        }

        $this->assertSame('unknown', $document->querySelector('[data-country="FR"]')->getAttribute('data-status'));
    }

    public function test_game_without_region_restrictions_marks_the_whole_map_available(): void
    {
        $game = Game::factory()->create();
        $response = $this->get('/games/'.$game->slug)->assertOk();
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);

        $this->assertCount(count(config('map-countries')), $document->querySelectorAll('.regions-map [data-status="available"]'));
        $this->assertCount(0, $document->querySelectorAll('.regions-map [data-status="unknown"]'));
    }

    public function test_unmapped_legacy_region_does_not_mark_the_whole_world_available(): void
    {
        $region = Region::factory()->create(['name' => 'Europe']);
        $availability = GameRegion::factory()->for($region)->create();
        $response = $this->get('/games/'.$availability->game->slug)->assertOk()->assertSee('Europe');
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);

        $this->assertCount(count(config('map-countries')), $document->querySelectorAll('.regions-map [data-status="unknown"]'));
    }
}
