<?php

namespace Tests\Feature;

use App\Filament\Resources\GameCategories\Pages\ManageGameCategories;
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
            'specifications' => ['Languages' => '22'], 'regionAvailabilities' => [['region_id' => $region->id, 'status' => 'available']], 'game_type_id' => $type->id,
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Game features from CMS', $game->fresh()->features);
        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['category_id' => $type->id])->call('save')->assertHasFormErrors(['category_id']);
        $partner = $this->partner();
        $partner->company->regions()->attach($region);
        $this->actingAs($partner)->get('/games/'.$game->slug)->assertOk()->assertSee('Game features from CMS')->assertSee('Game rules from CMS')->assertSee('Georgia');
        $this->get('/admin/games/'.$game->id.'/edit')->assertForbidden();
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
        $resource = ResourceItem::where('slug', 'uploaded-guide')->firstOrFail();
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
