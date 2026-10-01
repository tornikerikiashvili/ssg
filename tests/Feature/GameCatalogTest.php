<?php

namespace Tests\Feature;

use App\Filament\Resources\GameCategories\Pages\ManageGameCategories;
use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\RelationManagers\ResourcesRelationManager;
use App\Filament\Resources\GameTypes\Pages\ManageGameTypes;
use App\Filament\Resources\PayoutTypes\Pages\ManagePayoutTypes;
use App\Filament\Resources\VolatilityLevels\Pages\ManageVolatilityLevels;
use App\Models\CatalogOption;
use App\Models\Company;
use App\Models\EngagementTool;
use App\Models\Game;
use App\Models\Region;
use App\Models\ResourceItem;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class GameCatalogTest extends TestCase
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

    private function partner(): User
    {
        return User::factory()->for(Company::factory())->create();
    }

    public function test_admin_can_upload_replace_and_remove_a_game_cover(): void
    {
        Storage::fake('public');
        $game = Game::factory()->create(['cover_image' => 'brand/demo/game-1.jpg']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(EditGame::class, ['record' => $game->id])
            ->fillForm(['cover' => UploadedFile::fake()->image('cover.webp')])
            ->call('save')->assertHasNoFormErrors();

        $cover = $game->fresh()->getFirstMedia('cover');
        $this->assertNotNull($cover);
        $this->assertSame('public', $cover->disk);
        Storage::disk('public')->assertExists($cover->getPathRelativeToRoot());
        $this->assertSame($cover->getUrl(), $game->fresh()->cover_image_url);

        Livewire::test(EditGame::class, ['record' => $game->id])
            ->fillForm(['cover' => UploadedFile::fake()->image('replacement.png')])
            ->call('save')->assertHasNoFormErrors();

        $replacement = $game->fresh()->getFirstMedia('cover');
        $this->assertNotSame($cover->id, $replacement->id);
        $this->assertCount(1, $game->fresh()->getMedia('cover'));
        Storage::disk('public')->assertMissing($cover->getPathRelativeToRoot());

        Livewire::test(EditGame::class, ['record' => $game->id])
            ->fillForm(['cover' => []])->call('save')->assertHasNoFormErrors();

        $this->assertCount(0, $game->fresh()->getMedia('cover'));
        Storage::disk('public')->assertMissing($replacement->getPathRelativeToRoot());
        $this->assertSame(asset('brand/demo/game-1.jpg'), $game->fresh()->cover_image_url);
    }

    public function test_admin_can_upload_a_cover_when_creating_a_game(): void
    {
        Storage::fake('public');
        $category = CatalogOption::factory()->create(['kind' => 'category']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateGame::class)->fillForm([
            'title' => 'Game with cover', 'slug' => 'game-with-cover', 'category_id' => $category->id,
            'cover' => UploadedFile::fake()->image('new-cover.png'),
        ])->call('create')->assertHasNoFormErrors();

        $game = Game::where('slug', 'game-with-cover')->firstOrFail();
        $cover = $game->getFirstMedia('cover');
        $this->assertNotNull($cover);
        Storage::disk('public')->assertExists($cover->getPathRelativeToRoot());
    }

    public function test_game_cover_upload_rejects_non_images_and_oversized_images(): void
    {
        Storage::fake('public');
        $game = Game::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(EditGame::class, ['record' => $game->id])
            ->fillForm(['cover' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')])
            ->call('save')->assertHasFormErrors(['cover']);
        Livewire::test(EditGame::class, ['record' => $game->id])
            ->fillForm(['cover' => UploadedFile::fake()->image('large.jpg')->size(5121)])
            ->call('save')->assertHasFormErrors(['cover']);

        $this->assertCount(0, $game->fresh()->getMedia('cover'));
    }

    public function test_uploaded_covers_render_without_a_dropbox_token(): void
    {
        Storage::fake('public');
        config(['services.dropbox.access_token' => null]);
        $game = Game::factory()->create(['is_featured' => true]);
        $cover = $game->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');
        ResourceItem::factory()->for($game)->create(['kind' => 'download']);
        $this->actingAs($this->partner());

        $this->get('/games')->assertOk()->assertSee($cover->getUrl());
        $this->get('/games/'.$game->slug)->assertOk()->assertSee($cover->getUrl());
        $this->get('/dashboard')->assertOk()->assertSee($cover->getUrl());
        $this->get('/resources/download')->assertOk()->assertSee($cover->getUrl());
    }

    public function test_admin_can_create_and_rename_categories_without_losing_game_assignments(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageGameCategories::class)->callAction('create', data: ['name' => 'Puzzle', 'sort_order' => 2])->assertHasNoActionErrors();
        $category = CatalogOption::where('name', 'Puzzle')->firstOrFail();
        $this->assertSame('category', $category->kind);
        $game = Game::factory()->create(['category_id' => $category->id]);
        $asset = ResourceItem::factory()->for($game)->create(['kind' => 'download']);
        Livewire::test(ManageGameCategories::class)->callAction(TestAction::make('edit')->table($category), data: ['name' => 'Puzzles'])->assertHasNoActionErrors();
        $this->assertSame('Puzzles', $game->fresh()->category);
        $this->get('/admin/game-categories')->assertOk()->assertSee('Taxonomy')->assertDontSee('Categories &amp; filter options', false);
        $this->get('/admin/catalog-options')->assertNotFound();
        $this->actingAs($this->partner())->get('/games?category=Puzzles')->assertOk()->assertSee($game->title);
        $this->get('/resources/download?category=Puzzles')->assertOk()->assertSee($game->title)
            ->assertViewHas('resources', fn ($resources) => $resources->modelKeys() === [$asset->id]);
        $this->get('/admin/game-categories')->assertForbidden();
    }

    /** @return array<string, array{class-string, string, string}> */
    public static function gameFilterTaxonomies(): array
    {
        return [
            'game types' => [ManageGameTypes::class, 'game_type', 'game_type_id'],
            'payout types' => [ManagePayoutTypes::class, 'payout_type', 'payout_type_id'],
            'volatility levels' => [ManageVolatilityLevels::class, 'volatility', 'volatility_id'],
        ];
    }

    #[DataProvider('gameFilterTaxonomies')]
    public function test_cms_filter_options_update_games_and_download_center(string $page, string $kind, string $field): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test($page)->callAction('create', data: ['name' => 'Custom option', 'sort_order' => 1])->assertHasNoActionErrors();
        $option = CatalogOption::where('kind', $kind)->where('name', 'Custom option')->firstOrFail();
        $unrelated = CatalogOption::factory()->create(['kind' => 'document']);
        $game = Game::factory()->create();
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm([$field => $option->id])->call('save')->assertHasNoFormErrors();
        $asset = ResourceItem::factory()->for($game)->create(['kind' => 'download']);
        Game::factory()->create(['title' => 'Nonmatching game']);
        Livewire::test($page)->assertCanSeeTableRecords([$option])->assertCanNotSeeTableRecords([$unrelated])
            ->callAction(TestAction::make('edit')->table($option), data: ['name' => 'Renamed option'])->assertHasNoActionErrors();
        $this->assertSame($option->id, $game->fresh()->getAttribute($field));
        Livewire::test($page)->callAction('create', data: ['name' => 'Wrong taxonomy', 'sort_order' => 0, 'kind' => 'document'])->assertHasActionErrors(['kind']);

        $this->actingAs($this->partner())->get('/games?'.http_build_query([$field => $option->id]))->assertOk()
            ->assertSee('Renamed option')->assertViewHas('games', fn ($games) => $games->modelKeys() === [$game->id]);
        $this->get('/resources/download?'.http_build_query([$field => $option->id]))->assertOk()
            ->assertSee('Renamed option')->assertViewHas('resources', fn ($resources) => $resources->modelKeys() === [$asset->id]);
    }

    public function test_full_game_editor_persists_sections_and_rejects_wrong_option_types(): void
    {
        $game = Game::factory()->create();
        $region = Region::factory()->create(['name' => 'Georgia']);
        $type = CatalogOption::factory()->create(['kind' => 'game_type']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/games/'.$game->id.'/edit')->assertOk();
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm([
            'features' => 'Game features from CMS', 'feature_tags' => ['Multiplayer'], 'rules' => 'Game rules from CMS',
            'specifications' => [['label' => 'Languages 22', 'children' => []]], 'regionAvailabilities' => [['region_id' => $region->id, 'status' => 'available']], 'game_type_id' => $type->id,
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Game features from CMS', $game->fresh()->features);
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['category_id' => $type->id])->call('save')->assertHasFormErrors(['category_id']);
        $partner = $this->partner();
        $partner->company->regions()->attach($region);
        $this->actingAs($partner)->get('/games/'.$game->slug)->assertOk()->assertSee('Game features from CMS')->assertSee('Game rules from CMS')->assertSee('Georgia');
        $this->get('/admin/games/'.$game->id.'/edit')->assertForbidden();
    }

    public function test_game_summary_fields_save_and_render_independently_of_specifications(): void
    {
        $game = Game::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm([
            'min_bet' => '0.25', 'max_bet' => '500.50', 'languages' => 'English, Georgian', 'certifications' => 'GLI, BMM, MGA',
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('0.25', $game->fresh()->min_bet);
        $this->assertSame('500.50', $game->fresh()->max_bet);
        $this->assertSame('GLI, BMM, MGA', $game->fresh()->certifications);
        $this->actingAs($this->partner())->get('/games/'.$game->slug)->assertOk()
            ->assertSee('0.25')->assertSee('500.50')->assertSee('English, Georgian')->assertSee('GLI, BMM, MGA')->assertSee('Min Bet')->assertDontSee('Mix Bet');
    }

    /** @return array<string, array{bool, string}> */
    public static function gameTitleTagStates(): array
    {
        return [
            'released' => [false, 'released'],
            'featured' => [true, 'released'],
            'upcoming' => [false, 'upcoming'],
            'featured upcoming' => [true, 'upcoming'],
        ];
    }

    #[DataProvider('gameTitleTagStates')]
    public function test_game_title_tags_follow_featured_and_release_status(bool $featured, string $status): void
    {
        $game = Game::factory()->create(['is_demo' => true, 'is_featured' => $featured, 'release_status' => $status, 'preview_enabled' => true]);
        $response = $this->actingAs($this->partner())->get('/games/'.$game->slug)->assertOk()
            ->assertSee('<p class="tag">'.$game->category.'</p>', false)
            ->assertDontSee('<p class="tag is-red">Demo</p>', false);
        if ($featured) {
            $response->assertSee('<p class="tag is-red">Featured</p>', false);
        } else {
            $response->assertDontSee('<p class="tag is-red">Featured</p>', false);
        }
        if ($status === 'upcoming') {
            $response->assertSee('<p class="tag is-blue">Upcoming</p>', false);
        } else {
            $response->assertDontSee('<p class="tag is-blue">Upcoming</p>', false);
        }
    }

    public function test_demo_link_can_be_saved_displayed_and_removed(): void
    {
        $game = Game::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['demo_url' => 'https://example.com/play?game=demo'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('https://example.com/play?game=demo', $game->fresh()->demo_url);
        $this->actingAs($this->partner())->get('/games/'.$game->slug)->assertOk()
            ->assertSee('href="https://example.com/play?game=demo" target="_blank" rel="noopener noreferrer"', false)->assertSee('Play Demo');
        $this->actingAs($admin);
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['demo_url' => 'javascript:alert(1)'])
            ->call('save')->assertHasFormErrors(['demo_url']);
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['demo_url' => ''])
            ->call('save')->assertHasNoFormErrors();
        $this->get('/games/'.$game->slug)->assertOk()->assertDontSee('Play Demo');
    }

    public function test_game_assets_start_with_selection_instructions_and_hidden_bulk_actions(): void
    {
        $game = Game::factory()->create();
        ResourceItem::factory()->for($game)->create(['kind' => 'download', 'file_path' => 'demo/marketing-pack.txt']);
        $this->actingAs($this->partner())->get('/games/'.$game->slug)->assertOk()
            ->assertSee('To select multiple options, please click on the multiplier cards.')
            ->assertSee('id="asset-selection-hint" class="tag" role="status"', false)
            ->assertSee('id="asset-archive" hidden style="display:none"', false)
            ->assertSee('id="asset-selection-actions" class="buttons" hidden style="display:none"', false)
            ->assertSee('type="submit" form="asset-archive"', false);
    }

    public function test_game_summary_fields_reject_invalid_bet_limits(): void
    {
        $game = Game::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['min_bet' => -1, 'max_bet' => -1])
            ->call('save')->assertHasFormErrors(['min_bet', 'max_bet']);
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['min_bet' => 10, 'max_bet' => 5])
            ->call('save')->assertHasFormErrors(['max_bet']);
    }

    public function test_game_specifications_support_multiple_child_points_and_escape_content(): void
    {
        $game = Game::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm([
            'specifications' => [
                ['label' => 'Bet Placement', 'children' => [['text' => 'Manual or auto-bet options.'], ['text' => 'Two independent bets.'], ['text' => '<script>alert(1)</script>']]],
                ['label' => 'Languages 22', 'children' => []],
            ],
        ])->call('save')->assertHasNoFormErrors();
        $saved = $game->fresh();
        $this->assertSame(['Manual or auto-bet options.', 'Two independent bets.', '<script>alert(1)</script>'], $saved->specifications[0]['children']);
        $this->assertSame('22', $saved->specificationValue('Languages'));
        Livewire::test(EditGame::class, ['record' => $game->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame($saved->specifications, $game->fresh()->specifications);
        $this->actingAs($this->partner())->get('/games/'.$game->slug)->assertOk()
            ->assertSeeInOrder(['Bet Placement', 'Manual or auto-bet options.', 'Two independent bets.'])
            ->assertSee('class="specifications_list-item-sublevel"', false)
            ->assertDontSee('Bet Placement:')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_comma_separated_feature_tags_render_as_separate_colored_tags(): void
    {
        $game = Game::factory()->create(['feature_tags' => ['Tag 1, Tag 2, Tag 3']]);
        $this->assertSame(['Tag 1', 'Tag 2', 'Tag 3'], $game->feature_tags);
        $this->actingAs($this->partner())->get('/games/'.$game->slug)->assertOk()
            ->assertSee('<p class="tag">Tag 1</p>', false)
            ->assertSee('<p class="tag is-red">Tag 2</p>', false)
            ->assertSee('<p class="tag is-blue">Tag 3</p>', false)
            ->assertDontSee('Tag 1, Tag 2, Tag 3');
    }

    public function test_previous_specification_text_is_preserved_in_the_heading(): void
    {
        $game = Game::factory()->create(['specifications' => [
            ['label' => 'Payout', 'value' => 'Multiplier × Bet', 'children' => ['Example payout']],
        ]]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditGame::class, ['record' => $game->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame([['label' => 'Payout Multiplier × Bet', 'children' => ['Example payout']]], $game->fresh()->specifications);
    }

    public function test_legacy_game_specifications_survive_loading_and_saving_the_editor(): void
    {
        $game = Game::factory()->create(['specifications' => ['Max bet' => '100', 'Min bet' => '1', 'Languages' => '22']]);
        $this->assertSame('100', $game->specificationValue('Max bet'));
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditGame::class, ['record' => $game->id])->call('save')->assertHasNoFormErrors();
        $saved = $game->fresh();
        $this->assertCount(3, $saved->specifications);
        $this->assertSame('100', $saved->specificationValue('Max bet'));
        $this->assertSame('1', $saved->specificationValue('Min bet'));
        $this->assertSame('22', $saved->specificationValue('Languages'));
        $this->actingAs($this->partner())->get('/games/'.$game->slug)->assertOk()->assertSee('Max bet 100');
    }

    public function test_filters_and_sorting_use_dynamic_options_and_preserve_company_scope(): void
    {
        $type = CatalogOption::factory()->create(['kind' => 'game_type']);
        $payout = CatalogOption::factory()->create(['kind' => 'payout_type']);
        $volatility = CatalogOption::factory()->create(['kind' => 'volatility']);
        $values = ['game_type_id' => $type->id, 'payout_type_id' => $payout->id, 'volatility_id' => $volatility->id];
        $a = Game::factory()->create(['title' => 'Alpha game', ...$values]);
        $b = Game::factory()->create(['title' => 'Beta game', ...$values]);
        $other = Game::factory()->create(['title' => 'Filtered out']);
        $private = Game::factory()->for(Company::factory())->create(['title' => 'Secret title', ...$values]);
        $this->actingAs($this->partner())->get('/games?'.http_build_query([...$values, 'sort' => 'name']))->assertOk()->assertSeeInOrder([$a->title, $b->title])->assertDontSee($other->title)->assertDontSee($private->title);
        $this->get('/games?game_type_id='.$volatility->id)->assertSessionHasErrors('game_type_id');
    }

    public function test_game_sorting_orders_names_creation_ties_and_release_dates(): void
    {
        $createdAt = now()->subDay()->startOfSecond();
        $alpha = Game::factory()->create(['title' => 'Alpha', 'created_at' => $createdAt, 'release_date' => '2026-01-01']);
        $zulu = Game::factory()->create(['title' => 'Zulu', 'created_at' => $createdAt, 'release_date' => '2026-02-01']);
        $beta = Game::factory()->create(['title' => 'Beta', 'created_at' => $createdAt->copy()->subDay(), 'release_date' => null]);
        $this->actingAs($this->partner());
        $this->get('/games?sort=name')->assertOk()->assertViewHas('games', fn ($games) => $games->modelKeys() === [$alpha->id, $beta->id, $zulu->id]);
        $this->get('/games?sort=newest')->assertOk()->assertViewHas('games', fn ($games) => $games->modelKeys() === [$zulu->id, $alpha->id, $beta->id]);
        $this->get('/games?sort=release')->assertOk()->assertViewHas('games', fn ($games) => $games->modelKeys() === [$zulu->id, $alpha->id, $beta->id]);
    }

    public function test_download_center_filters_and_sorts_by_game_metadata(): void
    {
        $category = CatalogOption::factory()->create(['kind' => 'category', 'name' => 'Puzzle']);
        $type = CatalogOption::factory()->create(['kind' => 'game_type']);
        $payout = CatalogOption::factory()->create(['kind' => 'payout_type']);
        $volatility = CatalogOption::factory()->create(['kind' => 'volatility']);
        $values = ['category_id' => $category->id, 'game_type_id' => $type->id, 'payout_type_id' => $payout->id, 'volatility_id' => $volatility->id];
        $alpha = Game::factory()->create(['title' => 'Alpha', 'created_at' => now()->subDays(2), 'release_date' => null, ...$values]);
        $zulu = Game::factory()->create(['title' => 'Zulu', 'created_at' => now()->subDay(), 'release_date' => '2026-09-01', ...$values]);
        $first = ResourceItem::factory()->for($alpha)->create(['title' => 'Z asset']);
        $second = ResourceItem::factory()->for($zulu)->create(['title' => 'A asset']);
        ResourceItem::factory()->for(Game::factory())->create();
        ResourceItem::factory()->for(Company::factory())->for($alpha)->create(['title' => 'Private download']);
        $this->actingAs($this->partner());
        $filters = ['category' => 'Puzzle', 'game_type_id' => $type->id, 'payout_type_id' => $payout->id, 'volatility_id' => $volatility->id];
        foreach ($filters as $key => $value) {
            $this->get('/resources/download?'.http_build_query([$key => $value]))->assertOk()
                ->assertViewHas('resources', fn ($resources) => $resources->modelKeys() === [$first->id, $second->id]);
        }
        foreach (['name' => [$first->id, $second->id], 'newest' => [$second->id, $first->id], 'release' => [$second->id, $first->id]] as $sort => $expected) {
            $this->get('/resources/download?'.http_build_query([...$filters, 'sort' => $sort]))->assertOk()
                ->assertViewHas('resources', fn ($resources) => $resources->modelKeys() === $expected)->assertDontSee('Private download');
        }
        $this->get('/resources/download?game_type_id='.$volatility->id)->assertSessionHasErrors('game_type_id');
        $this->get('/resources/download?sort=invalid')->assertSessionHasErrors('sort');
        $this->get('/resources/download?category=Puzzle&q=nonexistent')->assertOk()->assertSee('No downloads match your filters.');
    }

    public function test_documentation_manager_updates_uploaded_files_on_the_owner_game(): void
    {
        Storage::fake('local');
        $game = Game::factory()->create();
        $category = CatalogOption::factory()->create(['kind' => 'document']);
        $document = ResourceItem::factory()->for($game)->create(['kind' => 'documentation']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ResourcesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])->callAction(TestAction::make('edit')->table($document), data: [
            'title' => 'Uploaded guide', 'slug' => 'uploaded-guide', 'kind' => 'documentation', 'catalog_option_id' => $category->id,
            'is_published' => true, 'file_path' => UploadedFile::fake()->create('guide.pdf', 10, 'application/pdf'),
        ])->assertHasNoActionErrors();
        $resource = $document->fresh();
        $this->assertSame('Uploaded guide', $resource->title);
        $this->assertSame($game->id, $resource->game_id);
        $this->assertTrue($resource->hasDownloadableFile());
        Storage::disk('local')->assertExists($resource->file_path);
        $this->actingAs($this->partner())->get('/resource/'.$resource->id.'/download')->assertOk();
    }

    public function test_game_documentation_can_be_assigned_and_removed_without_deleting_files(): void
    {
        $game = Game::factory()->create();
        $document = ResourceItem::factory()->create(['kind' => 'documentation', 'game_id' => null]);
        $asset = ResourceItem::factory()->for($game)->create(['kind' => 'download']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ResourcesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
            ->assertActionDoesNotExist(TestAction::make('create')->table())
            ->callAction(TestAction::make('associate')->table(), data: ['recordId' => $document->id])
            ->assertHasNoActionErrors()
            ->assertCanSeeTableRecords([$document])
            ->assertCanNotSeeTableRecords([$asset]);
        $this->assertSame($game->id, $document->fresh()->game_id);

        Livewire::test(ResourcesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
            ->callAction(TestAction::make('dissociate')->table($document))
            ->assertHasNoActionErrors();
        $this->assertNull($document->fresh()->game_id);
        $this->assertSame($document->file_path, $document->fresh()->file_path);
        $this->assertSame($game->id, $asset->fresh()->game_id);
    }

    public function test_game_documentation_rejects_assets_and_documents_owned_by_another_game(): void
    {
        $game = Game::factory()->create();
        $asset = ResourceItem::factory()->create(['kind' => 'download', 'game_id' => null]);
        $otherDocument = ResourceItem::factory()->for(Game::factory())->create(['kind' => 'documentation']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach ([$asset, $otherDocument] as $resource) {
            Livewire::test(ResourcesRelationManager::class, ['ownerRecord' => $game, 'pageClass' => EditGame::class])
                ->callAction(TestAction::make('associate')->table(), data: ['recordId' => $resource->id])
                ->assertHasActionErrors(['recordId']);
            $this->assertSame($resource->game_id, $resource->fresh()->game_id);
        }
    }

    public function test_game_assets_documents_and_tools_follow_assignments_and_access(): void
    {
        $game = Game::factory()->create();
        $category = CatalogOption::factory()->create(['kind' => 'asset']);
        $asset = ResourceItem::factory()->create(['game_id' => $game->id, 'catalog_option_id' => $category->id, 'title' => 'Selected asset']);
        ResourceItem::factory()->create(['game_id' => $game->id, 'title' => 'Other asset']);
        ResourceItem::factory()->for(Company::factory())->create(['game_id' => $game->id, 'title' => 'Private asset']);
        ResourceItem::factory()->create(['game_id' => $game->id, 'title' => 'Draft asset', 'is_published' => false]);
        $doc = ResourceItem::factory()->create(['game_id' => $game->id, 'kind' => 'documentation', 'title' => 'Assigned guide']);
        $tool = EngagementTool::factory()->create(['title' => 'Assigned tool']);
        $hidden = EngagementTool::factory()->for(Company::factory())->create(['title' => 'Hidden tool']);
        $game->engagementTools()->sync([$tool->id, $hidden->id]);
        $this->actingAs($this->partner())->get('/games/'.$game->slug.'?asset_category='.$category->id)->assertOk()->assertSee($asset->title)->assertSee('Other asset')->assertDontSee('Private asset')->assertDontSee('Draft asset')->assertViewHas('resources', fn ($resources) => $resources->count() === 2)->assertSee($doc->title)->assertSee($tool->title)->assertDontSee($hidden->title);
    }

    public function test_basket_downloads_only_selected_assets_and_preserves_access_boundaries(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('game-assets/basket.pdf', 'Selected content');
        $game = Game::factory()->create();
        $selected = ResourceItem::factory()->create(['game_id' => $game->id, 'file_path' => 'game-assets/basket.pdf']);
        $other = ResourceItem::factory()->create(['game_id' => $game->id, 'file_path' => 'game-assets/other.pdf']);
        $private = ResourceItem::factory()->for(Company::factory())->create(['file_path' => 'game-assets/basket.pdf']);
        $draft = ResourceItem::factory()->create(['is_published' => false, 'file_path' => 'game-assets/basket.pdf']);
        $privateGame = Game::factory()->for(Company::factory())->create();
        $privateGameAsset = ResourceItem::factory()->create(['game_id' => $privateGame->id, 'file_path' => 'game-assets/basket.pdf']);
        $this->post('/assets/basket/archive', ['ids' => [$selected->id]])->assertRedirect('/login');
        $this->actingAs($this->partner());
        $response = $this->post('/assets/basket/archive', ['ids' => [$selected->id]])->assertOk()->assertDownload('game-assets.zip');
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertSame(1, $zip->numFiles);
        $this->assertSame('Selected content', $zip->getFromIndex(0));
        $zip->close();
        unlink($path);
        $this->assertDatabaseHas('resource_downloads', ['resource_item_id' => $selected->id]);
        $this->assertDatabaseMissing('resource_downloads', ['resource_item_id' => $other->id]);
        foreach ([$private, $draft, $privateGameAsset, $other] as $unavailable) {
            $this->postJson('/assets/basket/archive', ['ids' => [$unavailable->id]])->assertNotFound();
        }
        $this->postJson('/assets/basket/archive', ['ids' => []])->assertUnprocessable();
        $this->postJson('/assets/basket/archive', ['ids' => [$selected->id, $selected->id]])->assertUnprocessable();
        $this->postJson('/assets/basket/archive', ['ids' => range(1, 51)])->assertUnprocessable();
        $this->postJson('/assets/basket/archive', ['game_ids' => [$game->id]])->assertUnprocessable();
    }

    public function test_zip_contains_selected_files_and_rejects_other_game_and_missing_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('game-assets/example.pdf', 'PDF content');
        $game = Game::factory()->create();
        $file = ResourceItem::factory()->create(['game_id' => $game->id, 'file_path' => 'game-assets/example.pdf']);
        $other = ResourceItem::factory()->for(Game::factory())->create(['file_path' => 'game-assets/example.pdf']);
        $user = $this->partner();
        $private = ResourceItem::factory()->for(Company::factory())->create(['game_id' => $game->id, 'file_path' => 'game-assets/example.pdf']);
        $this->actingAs($user)->post('/games/'.$game->slug.'/assets/archive', ['ids' => [$private->id]])->assertNotFound();
        $this->actingAs($user)->post('/games/'.$game->slug.'/assets/archive', ['ids' => [$other->id]])->assertNotFound();
        $this->post('/games/'.$game->slug.'/assets/archive', ['ids' => []])->assertSessionHasErrors('ids');
        $response = $this->post('/games/'.$game->slug.'/assets/archive', ['ids' => [$file->id]])->assertOk()->assertDownload('game-assets.zip');
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertSame('PDF content', $zip->getFromIndex(0));
        $zip->close();
        unlink($path);
        $this->assertDatabaseHas('resource_downloads', ['user_id' => $user->id, 'resource_item_id' => $file->id]);
        Storage::disk('local')->delete($file->file_path);
        $this->post('/games/'.$game->slug.'/assets/archive', ['ids' => [$file->id]])->assertNotFound();
    }
}
