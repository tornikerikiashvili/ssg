<?php

namespace Tests\Feature;

use App\Filament\Resources\Games\Pages\EditGame;
use App\Models\CatalogOption;
use App\Models\Company;
use App\Models\Game;
use App\Models\ResourceItem;
use App\Models\User;
use App\SyncDropboxGame;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class DropboxIntegrationTest extends TestCase
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
        config(['services.dropbox.access_token' => 'test-token']);
        Http::preventStrayRequests();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    /** @param array<string, mixed> $responses */
    private function fakeHttp(array $responses = []): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($responses);
    }

    /** @return array<string, mixed> */
    private function file(string $id = 'id:file', string $name = 'logo.svg'): array
    {
        return ['.tag' => 'file', 'id' => $id, 'name' => $name, 'path_display' => '/Games/Game1/assets/Logos/'.$name, 'rev' => 'revision1', 'size' => 7, 'is_downloadable' => true];
    }

    /** @param array<int, array<string, mixed>> $files */
    private function fakeFolder(array $files): void
    {
        $this->fakeHttp([
            '*/get_metadata' => Http::response(['.tag' => 'folder', 'id' => 'id:folder', 'path_display' => '/Games/Game1/assets']),
            '*/list_folder' => Http::response(['entries' => $files, 'has_more' => false, 'cursor' => 'cursor']),
        ]);
    }

    public function test_game_editor_links_folder_and_imports_assets_with_folder_categories(): void
    {
        $this->fakeFolder([$this->file()]);
        $game = Game::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(EditGame::class, ['record' => $game->id])->fillForm(['dropbox_folder_path' => '/Games/Game1/assets'])
            ->call('save')->assertHasNoFormErrors();

        $asset = $game->resources()->firstOrFail();
        $this->assertSame('logo.svg', $asset->title);
        $this->assertSame('Logos', $asset->catalogOption->name);
        $this->assertSame('id:folder', $game->fresh()->dropbox_folder_id);
        $this->assertNotNull($game->fresh()->dropbox_synced_at);
        $this->assertNull($game->fresh()->dropbox_sync_error);
        $this->actingAs(User::factory()->for(Company::factory())->create())->get('/games/'.$game->slug)->assertOk()->assertSee('logo.svg')->assertSee('Logos');
    }

    #[TestWith(['WEBP', 'image'])]
    #[TestWith(['mp4', 'video'])]
    #[TestWith(['zip', 'archive'])]
    #[TestWith(['svg', 'design'])]
    #[TestWith(['pdf', 'pdf'])]
    #[TestWith(['docx', 'document'])]
    #[TestWith(['mp3', 'audio'])]
    #[TestWith(['xlsx', 'spreadsheet'])]
    #[TestWith(['pptx', 'presentation'])]
    #[TestWith(['json', 'code'])]
    #[TestWith(['unknown', 'file'])]
    #[TestWith(['', 'file'])]
    public function test_asset_cards_show_icons_for_the_file_format(string $extension, string $type): void
    {
        $resource = ResourceItem::factory()->make([
            'id' => 1, 'file_path' => 'asset'.($extension ? '.'.$extension : ''),
            'dropbox_file_id' => 'id:icon-test', 'dropbox_available' => true,
        ]);

        $view = $this->blade('<x-original-assets :resources="$resources" />', ['resources' => collect([$resource])]);

        $view->assertSee('data-file-icon="'.$type.'"', false)
            ->assertSee('aria-label="'.strtoupper($extension ?: 'file').' file"', false);
    }

    #[TestWith(['/games/dropbox-categories'])]
    #[TestWith(['/resources/download'])]
    public function test_asset_filters_only_include_categories_of_visible_dropbox_files(string $url): void
    {
        $game = Game::factory()->create(['slug' => 'dropbox-categories']);
        $logos = CatalogOption::factory()->create(['kind' => 'asset', 'name' => 'Dropbox Logos']);
        $legacy = CatalogOption::factory()->create(['kind' => 'asset', 'name' => 'Legacy Category']);
        $archived = CatalogOption::factory()->create(['kind' => 'asset', 'name' => 'Archived Category']);
        $private = CatalogOption::factory()->create(['kind' => 'asset', 'name' => 'Unpublished Category']);
        CatalogOption::factory()->create(['kind' => 'asset', 'name' => 'Empty Category']);
        ResourceItem::factory()->for($game)->count(2)->sequence(
            ['dropbox_file_id' => 'id:logo-one'],
            ['dropbox_file_id' => 'id:logo-two'],
        )->create(['kind' => 'download', 'catalog_option_id' => $logos->id]);
        ResourceItem::factory()->for($game)->create(['kind' => 'download', 'catalog_option_id' => $legacy->id]);
        ResourceItem::factory()->for($game)->create(['kind' => 'download', 'catalog_option_id' => $archived->id, 'dropbox_file_id' => 'id:archived', 'dropbox_available' => false]);
        ResourceItem::factory()->for($game)->create(['kind' => 'download', 'catalog_option_id' => $private->id, 'dropbox_file_id' => 'id:private', 'is_published' => false]);

        $response = $this->actingAs(User::factory()->for(Company::factory())->create())->get($url);

        $response->assertOk()->assertViewHas('assetCategories', [$logos->id => 'Dropbox Logos']);
    }

    public function test_game_asset_filters_exclude_other_games_categories(): void
    {
        $game = Game::factory()->create();
        $otherCategory = CatalogOption::factory()->create(['kind' => 'asset', 'name' => 'Other Game Category']);
        ResourceItem::factory()->for(Game::factory())->create(['kind' => 'download', 'catalog_option_id' => $otherCategory->id, 'dropbox_file_id' => 'id:other']);

        $response = $this->actingAs(User::factory()->for(Company::factory())->create())->get('/games/'.$game->slug);

        $response->assertOk()->assertViewHas('assetCategories', []);
    }

    public function test_sync_reads_all_pages_updates_files_and_archives_missing_assets_without_touching_documents(): void
    {
        $game = Game::factory()->create(['dropbox_folder_path' => '/Games/Game1/assets']);
        $document = ResourceItem::factory()->for($game)->create(['kind' => 'documentation']);
        $this->fakeHttp([
            '*/get_metadata' => Http::response(['.tag' => 'folder', 'id' => 'id:folder', 'path_display' => '/Games/Game1/assets']),
            '*/list_folder' => Http::response(['entries' => [$this->file()], 'has_more' => true, 'cursor' => 'next']),
            '*/list_folder/continue' => Http::response(['entries' => [$this->file('id:second', 'other.png')], 'has_more' => false, 'cursor' => 'done']),
        ]);
        $this->assertSame(2, app(SyncDropboxGame::class)->sync($game));
        $first = $game->resources()->where('dropbox_file_id', 'id:file')->firstOrFail();

        $this->fakeFolder([$this->file('id:file', 'renamed.svg')]);
        $this->assertSame(1, app(SyncDropboxGame::class)->sync($game));
        $this->assertSame('renamed.svg', $first->fresh()->title);
        $this->assertDatabaseCount('resource_items', 3);
        $this->assertDatabaseHas('resource_items', ['dropbox_file_id' => 'id:second', 'dropbox_available' => false]);
        $this->assertTrue($document->fresh()->is_published);
    }

    public function test_failed_sync_preserves_index_but_mapping_changes_hide_old_files(): void
    {
        $game = Game::factory()->create(['dropbox_folder_path' => '/Games/Game1/assets']);
        $this->fakeFolder([$this->file()]);
        app(SyncDropboxGame::class)->sync($game);
        $asset = $game->resources()->firstOrFail();
        $this->fakeHttp(['*/get_metadata' => Http::response([], 401)]);

        try {
            app(SyncDropboxGame::class)->sync($game);
            $this->fail('Expected sync failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('expired', $exception->getMessage());
        }
        $this->assertTrue((bool) $asset->fresh()->dropbox_available);
        $this->assertStringNotContainsString('test-token', $game->fresh()->dropbox_sync_error);
        $game->refresh()->update(['dropbox_folder_path' => '/Other']);
        $this->assertFalse((bool) $asset->fresh()->dropbox_available);
        $this->assertNull($game->fresh()->dropbox_folder_id);
    }

    public function test_downloads_and_zip_use_dropbox_and_enforce_access_before_network_calls(): void
    {
        $game = Game::factory()->create(['dropbox_folder_path' => '/Games/Game1/assets']);
        $asset = ResourceItem::factory()->for($game)->create(['dropbox_file_id' => 'id:file', 'file_path' => 'logo.svg', 'file_size' => 7]);
        $user = User::factory()->for(Company::factory())->create();
        $this->fakeHttp(['*/files/download' => fn () => Http::response('content')]);
        $this->actingAs($user);
        $this->get('/resource/'.$asset->id.'/download')->assertOk()->assertStreamedContent('content');
        $response = $this->post('/assets/basket/archive', ['ids' => [$asset->id]])->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertSame('content', $zip->getFromIndex(0));
        $zip->close();
        unlink($path);
        $this->assertDatabaseCount('resource_downloads', 2);

        $this->fakeHttp();
        $game->update(['company_id' => Company::factory()->create()->id]);
        $this->get('/resource/'.$asset->id.'/download')->assertNotFound();
        $this->post('/assets/basket/archive', ['ids' => [$asset->id]])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_download_failure_does_not_record_success_and_unavailable_assets_are_hidden(): void
    {
        $asset = ResourceItem::factory()->for(Game::factory())->create(['dropbox_file_id' => 'id:file', 'file_path' => 'logo.svg']);
        $this->fakeHttp(['*/files/download' => Http::response([], 401)]);
        $this->actingAs(User::factory()->for(Company::factory())->create());
        $this->get('/resource/'.$asset->id.'/download')->assertStatus(502);
        $this->assertDatabaseCount('resource_downloads', 0);
        $asset->update(['dropbox_available' => false]);
        $this->get('/resource/'.$asset->id.'/download')->assertNotFound();
    }
}
