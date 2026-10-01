<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Game;
use App\Models\ResourceItem;
use App\Models\User;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DocumentationFileTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    public function test_documentation_downloads_the_file_and_tracks_get_requests_only(): void
    {
        Storage::fake('local');
        $contents = "%PDF-1.4\nExample document\n%%EOF";
        Storage::disk('local')->put('portal-resources/guide.pdf', $contents);
        $user = User::factory()->for(Company::factory())->create();
        $document = ResourceItem::factory()->create(['kind' => 'documentation', 'file_path' => 'portal-resources/guide.pdf']);

        $this->actingAs($user)->head(route('resources.show', $document))->assertOk();
        $this->assertDatabaseCount('resource_downloads', 0);
        $this->get(route('resources.show', $document))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename=guide.pdf')
            ->assertStreamedContent($contents);
        $this->assertDatabaseHas('resource_downloads', ['user_id' => $user->id, 'resource_item_id' => $document->id]);
        $this->get(route('resources.download', $document))->assertOk()->assertDownload('guide.pdf');
    }

    public function test_documentation_cards_link_directly_to_downloads_on_both_pages(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $document = ResourceItem::factory()->create(['kind' => 'documentation']);
        $game = Game::factory()->create();
        $gameDocument = ResourceItem::factory()->for($game)->create(['kind' => 'documentation']);

        $page = HTMLDocument::createFromString($this->actingAs($user)->get('/resources/documentation')->assertOk()->getContent(), LIBXML_NOERROR);
        $this->assertSame(route('resources.download', $document), $page->querySelector('.docs_item:not([target])')?->getAttribute('href'));
        $page = HTMLDocument::createFromString($this->get(route('games.show', $game->slug))->assertOk()->getContent(), LIBXML_NOERROR);
        $this->assertSame(route('resources.download', $gameDocument), $page->querySelector('.docs_item:not([target])')?->getAttribute('href'));
    }

    public function test_documentation_file_access_rejects_unpublished_private_missing_and_unsafe_paths(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('portal-resources/guide.txt', 'Guide');
        $user = User::factory()->for(Company::factory())->create();
        $private = ResourceItem::factory()->for(Company::factory())->create(['kind' => 'documentation', 'file_path' => 'portal-resources/guide.txt']);
        $draft = ResourceItem::factory()->create(['kind' => 'documentation', 'is_published' => false, 'file_path' => 'portal-resources/guide.txt']);
        $missing = ResourceItem::factory()->create(['kind' => 'documentation', 'file_path' => 'portal-resources/missing.txt']);
        $unsafe = ResourceItem::factory()->create(['kind' => 'documentation', 'file_path' => '../.env']);
        $this->get(route('resources.show', $private))->assertRedirect('/login');
        $this->actingAs($user);

        foreach ([$private, $draft, $missing, $unsafe] as $document) {
            $this->get(route('resources.show', $document))->assertNotFound();
        }
        $this->assertDatabaseCount('resource_downloads', 0);
    }

    public function test_active_document_formats_download_instead_of_rendering_inline(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('portal-resources/guide.html', '<html><script>alert(1)</script></html>');
        $user = User::factory()->for(Company::factory())->create();
        $document = ResourceItem::factory()->create(['kind' => 'documentation', 'file_path' => 'portal-resources/guide.html']);

        $this->actingAs($user)->get(route('resources.show', $document))->assertOk()->assertDownload('guide.html')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
