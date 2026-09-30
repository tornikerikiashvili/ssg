<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Filament\Resources\Certificates\Pages\ManageCertificates;
use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Filament\Resources\DocumentationCategories\Pages\ManageDocumentationCategories;
use App\Filament\Resources\Documentations\Pages\ManageDocumentations;
use App\Filament\Resources\EngagementTools\Pages\ManageEngagementTools;
use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ManageGames;
use App\Filament\Resources\Licenses\Pages\ManageLicenses;
use App\Filament\Resources\ResourceItems\Pages\ManageResourceItems;
use App\Filament\Resources\RoadmapItems\Pages\ManageRoadmapItems;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Announcement;
use App\Models\CatalogOption;
use App\Models\Company;
use App\Models\Game;
use App\Models\ResourceItem;
use App\Models\User;
use Database\Seeders\PortalDemoSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PortalContentTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Database refresh is restricted to smartsoft_testing.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function client(): User
    {
        return User::factory()->for(Company::factory())->create();
    }

    public function test_header_search_filters_games_by_keyword(): void
    {
        $client = $this->client();
        $matching = Game::factory()->create(['title' => 'Rocket Adventure']);
        Game::factory()->create(['title' => 'Ocean Adventure']);
        Game::factory()->create(['title' => 'Rocket Draft', 'is_published' => false]);
        Game::factory()->for(Company::factory())->create(['title' => 'Private Rocket']);

        $this->actingAs($client)->get('/dashboard')->assertOk()
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('name="search_category"', false)
            ->assertDontSee('Oops! Something went wrong while submitting the form.');
        $this->get('/search?search_category=games&q=rocket')
            ->assertRedirect(route('games.index', ['q' => 'rocket']));
        $this->get('/games?q=rocket')->assertOk()
            ->assertViewHas('games', fn ($games) => $games->pluck('id')->all() === [$matching->id])
            ->assertSee('value="rocket"', false);
    }

    /** @return array<string, array{string}> */
    public static function searchResourceCategories(): array
    {
        return ['downloads' => ['download'], 'documents' => ['documentation'], 'certificates' => ['certificate']];
    }

    #[DataProvider('searchResourceCategories')]
    public function test_header_search_filters_the_selected_resource_category(string $kind): void
    {
        $matching = ResourceItem::factory()->create(['kind' => $kind, 'title' => 'Rocket pack']);
        ResourceItem::factory()->create(['kind' => $kind, 'title' => 'Ocean pack']);
        ResourceItem::factory()->create(['kind' => $kind, 'title' => 'Rocket draft', 'is_published' => false]);
        ResourceItem::factory()->for(Company::factory())->create(['kind' => $kind, 'title' => 'Private rocket']);
        ResourceItem::factory()->create(['kind' => $kind === 'download' ? 'documentation' : 'download', 'title' => 'Rocket other category']);

        $this->actingAs($this->client())->get('/search?'.http_build_query(['search_category' => $kind, 'q' => 'rocket']))
            ->assertRedirect(route('resources.index', ['kind' => $kind, 'q' => 'rocket']));
        $this->get(route('resources.index', ['kind' => $kind, 'q' => 'rocket']))->assertOk()
            ->assertViewHas('resources', fn ($resources) => $resources->pluck('id')->all() === [$matching->id])
            ->assertSee('checked value="'.$kind.'"', false)
            ->assertSee('value="rocket"', false);
    }

    public function test_header_search_validates_inputs_and_allows_an_empty_keyword(): void
    {
        $this->actingAs($this->client())->get('/search?search_category=games&q=')
            ->assertRedirect(route('games.index', ['q' => '']));
        $this->getJson('/search?search_category=invalid')->assertUnprocessable()->assertJsonValidationErrors('search_category');
        $this->getJson('/search?search_category=games&q[]=invalid')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/search?search_category=games&q='.str_repeat('a', 101))->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_catalog_filters_and_hides_drafts_and_other_companies(): void
    {
        $client = $this->client();
        $own = Game::factory()->create(['title' => 'Own rocket', 'category' => 'Crash', 'company_id' => $client->company_id]);
        $shared = Game::factory()->create(['title' => 'Shared rocket', 'category' => 'Crash']);
        $private = Game::factory()->for(Company::factory())->create(['title' => 'Private rocket']);
        $draft = Game::factory()->create(['title' => 'Draft rocket', 'is_published' => false]);
        $slot = Game::factory()->create(['title' => 'Other category', 'category' => 'Slot']);

        $this->actingAs($client)->get('/games?q=rocket&category=Crash')->assertOk()
            ->assertSee($own->title)->assertSee($shared->title)
            ->assertDontSee($private->title)->assertDontSee($draft->title)->assertDontSee($slot->title);
        $this->get('/games/'.$private->slug)->assertNotFound();
        $this->get('/games/'.$draft->slug)->assertNotFound();
        $this->get('/games/'.$own->slug)->assertOk();
    }

    public function test_certificate_cards_download_files_without_a_detail_page(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo/sample-certificate.txt', 'Sample certificate');
        $certificate = ResourceItem::factory()->create(['kind' => 'certificate', 'file_path' => 'demo/sample-certificate.txt']);
        $this->actingAs($this->client())->get('/resources/certificate')->assertOk()
            ->assertSee(route('resources.download', $certificate->id), false)
            ->assertDontSee(route('resources.show', $certificate->id).'"', false);
        $this->get('/resource/'.$certificate->id.'/download')->assertOk()->assertDownload('sample-certificate.txt');
    }

    public function test_resource_downloads_enforce_own_and_parent_game_visibility(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo/marketing-pack.txt', 'Demo marketing contents');
        $client = $this->client();
        $privateGame = Game::factory()->for(Company::factory())->create();
        $inherited = ResourceItem::factory()->for($privateGame)->create(['file_path' => 'demo/marketing-pack.txt']);
        $private = ResourceItem::factory()->for(Company::factory())->create(['file_path' => 'demo/marketing-pack.txt']);
        $allowed = ResourceItem::factory()->create(['file_path' => 'demo/marketing-pack.txt', 'company_id' => $client->company_id]);
        $this->actingAs($client)->get('/resources/download')->assertOk()
            ->assertSee($allowed->title)->assertDontSee($inherited->title)->assertDontSee($private->title);
        foreach ([$private, $inherited] as $blocked) {
            $this->get('/resource/'.$blocked->id)->assertNotFound();
            $this->get('/resource/'.$blocked->id.'/download')->assertNotFound();
        }
        $this->get('/resource/'.$allowed->id.'/download')->assertOk()->assertDownload('marketing-pack.txt')
            ->assertStreamedContent('Demo marketing contents');
    }

    public function test_unpublishing_a_game_revokes_its_resource_download(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo/marketing-pack.txt', 'demo');
        $game = Game::factory()->create();
        $resource = ResourceItem::factory()->for($game)->create(['file_path' => 'demo/marketing-pack.txt']);
        $this->actingAs($this->client())->get('/resource/'.$resource->id.'/download')->assertOk();
        $game->update(['is_published' => false]);
        $this->get('/resource/'.$resource->id.'/download')->assertNotFound();
    }

    public function test_unknown_or_missing_files_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        $unknown = ResourceItem::factory()->create(['file_path' => '../.env']);
        $missing = ResourceItem::factory()->create(['file_path' => 'demo/marketing-pack.txt']);
        $this->actingAs($this->client())->get('/resource/'.$unknown->id.'/download')->assertNotFound();
        $this->get('/resource/'.$missing->id.'/download')->assertNotFound();
    }

    public function test_invalid_catalog_filters_are_rejected(): void
    {
        $this->actingAs($this->client())->get('/games?category=invalid')->assertSessionHasErrors('category');
        $this->get('/games?q[]=invalid')->assertSessionHasErrors('q');
        $this->get('/resources/unknown')->assertNotFound();
    }

    public function test_announcements_are_company_scoped_and_content_is_escaped(): void
    {
        $client = $this->client();
        $own = Announcement::factory()->create(['company_id' => $client->company_id, 'title' => 'Partner announcement']);
        $other = Announcement::factory()->for(Company::factory())->create(['title' => 'Other company secret']);
        $unsafe = Announcement::factory()->create(['description' => '<script>alert(1)</script>']);
        $this->actingAs($client)->get('/updates')->assertOk()->assertSee($own->title)->assertDontSee($other->title)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee($unsafe->description, false);
    }

    public static function adminModules(): array
    {
        return array_map(fn (string $path): array => [$path], [
            'companies', 'users', 'games', 'game-categories', 'game-types', 'payout-types', 'volatility-levels', 'resource-items', 'certificates', 'licenses', 'documentations', 'documentation-categories', 'announcements', 'roadmap-items', 'engagement-tools',
        ]);
    }

    #[DataProvider('adminModules')]
    public function test_admin_modules_load_and_refuse_partner_users(string $path): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin/'.$path)->assertOk();
        $this->actingAs($this->client())->get('/admin/'.$path)->assertForbidden();
    }

    public static function contentForms(): array
    {
        return [
            'game' => [ManageGames::class, 'games', ['category' => 'Crash']],
            'resource' => [ManageResourceItems::class, 'resource_items', ['kind' => 'documentation']],
            'announcement' => [ManageAnnouncements::class, 'announcements', ['priority' => 'info']],
            'roadmap' => [ManageRoadmapItems::class, 'roadmap_items', ['status' => 'planned', 'target_date' => '2027-01-01']],
            'engagement' => [ManageEngagementTools::class, 'engagement_tools', ['category' => 'Promotion']],
        ];
    }

    #[DataProvider('contentForms')]
    public function test_admin_can_create_content_using_cms_forms(string $page, string $table, array $extra): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $data = [
            'title' => 'Created in CMS', 'slug' => 'created-in-cms', 'description' => 'New content',
            'is_published' => true, 'is_demo' => true, ...$extra,
        ];
        if ($table === 'games') {
            $data['category_id'] = CatalogOption::factory()->create()->id;
            unset($data['category']);
            Livewire::test(CreateGame::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        } else {
            Livewire::test($page)->callAction('create', data: $data)->assertHasNoActionErrors();
        }
        $this->assertDatabaseHas($table, ['slug' => 'created-in-cms', 'is_published' => true]);
    }

    /** @return array<string, array{class-string, string}> */
    public static function standaloneResourceForms(): array
    {
        return [
            'licenses' => [ManageLicenses::class, 'license'],
            'certifications' => [ManageCertificates::class, 'certificate'],
        ];
    }

    #[DataProvider('standaloneResourceForms')]
    public function test_standalone_cms_uploads_files_for_the_client_area(string $page, string $kind): void
    {
        Storage::fake('local');
        $client = $this->client();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test($page)->callAction('create', data: [
            'title' => 'Gaming license', 'slug' => 'gaming-license',
            'company_id' => $client->company_id, 'is_published' => true,
            'file_path' => UploadedFile::fake()->create('license.pdf', 10, 'application/pdf'),
        ])->assertHasNoActionErrors();

        $certificate = ResourceItem::where('slug', 'gaming-license')->firstOrFail();
        $this->assertSame($kind, $certificate->kind);
        $this->assertNull($certificate->game_id);
        $this->assertNull($certificate->catalog_option_id);
        $this->assertStringStartsWith('portal-resources/', $certificate->file_path);
        $this->assertTrue($certificate->hasDownloadableFile());
        Storage::disk('local')->assertExists($certificate->file_path);
        $this->actingAs($client)->get('/resources/certificate')->assertOk()->assertSee('Gaming license');
        $this->get(route('resources.download', $certificate))->assertOk()->assertDownload();
        $this->actingAs($this->client())->get(route('resources.download', $certificate))->assertNotFound();
    }

    public function test_certificate_cms_only_manages_certificates(): void
    {
        $certificate = ResourceItem::factory()->create(['kind' => 'certificate']);
        $download = ResourceItem::factory()->create(['kind' => 'download']);
        $gameCertificate = ResourceItem::factory()->for(Game::factory())->create(['kind' => 'certificate']);
        $license = ResourceItem::factory()->create(['kind' => 'license']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageCertificates::class)
            ->assertCanSeeTableRecords([$certificate])->assertCanNotSeeTableRecords([$download, $gameCertificate, $license])
            ->callAction('create', data: ['title' => 'Wrong kind', 'slug' => 'wrong-kind', 'kind' => 'download'])
            ->assertHasActionErrors(['kind']);
        $this->assertDatabaseMissing('resource_items', ['slug' => 'wrong-kind']);
        $this->actingAs($this->client())->get('/resources/certificate')->assertOk()
            ->assertSee($certificate->title)->assertDontSee($gameCertificate->title);
    }

    public function test_license_cms_and_main_page_keep_licenses_separate(): void
    {
        $license = ResourceItem::factory()->create(['kind' => 'license', 'title' => 'Partner License']);
        $certificate = ResourceItem::factory()->create(['kind' => 'certificate', 'title' => 'Partner Certification']);
        $draft = ResourceItem::factory()->create(['kind' => 'license', 'is_published' => false]);
        $private = ResourceItem::factory()->for(Company::factory())->create(['kind' => 'license']);
        $gameLicense = ResourceItem::factory()->for(Game::factory())->create(['kind' => 'license']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageLicenses::class)
            ->assertCanSeeTableRecords([$license])->assertCanNotSeeTableRecords([$certificate, $gameLicense])
            ->callAction('create', data: ['title' => 'Wrong kind', 'slug' => 'wrong-kind', 'kind' => 'certificate'])
            ->assertHasActionErrors(['kind']);

        $this->actingAs($this->client())->get('/resources/certificate')->assertOk()
            ->assertViewHas('licenses', fn ($licenses) => $licenses->modelKeys() === [$license->id])
            ->assertViewHas('resources', fn ($resources) => $resources->modelKeys() === [$certificate->id])
            ->assertSeeInOrder(['Licenses</p>', $license->title, 'Certifications</p>', $certificate->title], false)
            ->assertDontSee($draft->title)->assertDontSee($private->title)->assertDontSee($gameLicense->title);
        $this->get('/resources/certificate?q=Certification')->assertOk()
            ->assertViewHas('licenses', fn ($licenses) => $licenses->isEmpty())
            ->assertViewHas('resources', fn ($resources) => $resources->modelKeys() === [$certificate->id]);
    }

    public function test_documentation_categories_and_uploads_work_in_the_cms_and_main_page(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageDocumentationCategories::class)->callAction('create', data: [
            'name' => 'Integration guides', 'sort_order' => 1,
        ])->assertHasNoActionErrors();
        $category = CatalogOption::where('name', 'Integration guides')->firstOrFail();
        $this->assertSame('document', $category->kind);
        $assetCategory = CatalogOption::factory()->create(['kind' => 'asset']);
        Livewire::test(ManageDocumentationCategories::class)
            ->assertCanSeeTableRecords([$category])->assertCanNotSeeTableRecords([$assetCategory]);
        Livewire::test(ManageDocumentations::class)->callAction('create', data: [
            'title' => 'Platform setup', 'slug' => 'platform-setup', 'catalog_option_id' => $category->id,
            'is_published' => true, 'file_path' => UploadedFile::fake()->create('setup.pdf', 10, 'application/pdf'),
        ])->assertHasNoActionErrors();
        $document = ResourceItem::where('slug', 'platform-setup')->firstOrFail();
        $this->assertSame('documentation', $document->kind);
        $this->assertNull($document->game_id);
        Storage::disk('local')->assertExists($document->file_path);
        $other = ResourceItem::factory()->create(['kind' => 'documentation']);
        $gameDocument = ResourceItem::factory()->for(Game::factory())->create(['kind' => 'documentation', 'catalog_option_id' => $category->id]);
        $private = ResourceItem::factory()->for(Company::factory())->create(['kind' => 'documentation', 'catalog_option_id' => $category->id]);
        $draft = ResourceItem::factory()->create(['kind' => 'documentation', 'catalog_option_id' => $category->id, 'is_published' => false]);
        Livewire::test(ManageDocumentations::class)->assertCanSeeTableRecords([$document])->assertCanNotSeeTableRecords([$gameDocument]);
        $this->actingAs($this->client())->get('/resources/documentation?documentation_category_id='.$category->id.'&q=setup')
            ->assertOk()->assertSee($category->name)->assertSee($document->title)
            ->assertViewHas('resources', fn ($resources) => $resources->modelKeys() === [$document->id])
            ->assertDontSee($other->title)->assertDontSee($gameDocument->title)->assertDontSee($private->title)->assertDontSee($draft->title);
        $this->get(route('resources.download', $document))->assertOk()->assertDownload();
        $this->getJson('/resources/documentation?documentation_category_id='.$assetCategory->id)
            ->assertUnprocessable()->assertJsonValidationErrors('documentation_category_id');
    }

    public function test_documentation_cms_requires_a_documentation_category(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $assetCategory = CatalogOption::factory()->create(['kind' => 'asset']);
        foreach ([null, $assetCategory->id] as $categoryId) {
            Livewire::test(ManageDocumentations::class)->callAction('create', data: [
                'title' => 'Invalid category', 'slug' => 'invalid-category', 'catalog_option_id' => $categoryId,
            ])->assertHasActionErrors(['catalog_option_id']);
        }
        $this->assertDatabaseMissing('resource_items', ['slug' => 'invalid-category']);
    }

    public function test_cms_edit_changes_what_the_client_sees(): void
    {
        $game = Game::factory()->create();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditGame::class, ['record' => $game->id])
            ->fillForm(['title' => 'Updated partner game', 'is_published' => false])->call('save')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas('games', ['id' => $game->id, 'title' => 'Updated partner game', 'is_published' => false]);
        $this->actingAs($this->client())->get('/games/'.$game->slug)->assertNotFound();
    }

    public function test_admin_can_create_company_and_partner_user_with_hashed_password(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageCompanies::class)->callAction('create', data: ['name' => 'CMS Partner', 'is_active' => true])->assertHasNoActionErrors();
        $company = Company::where('name', 'CMS Partner')->firstOrFail();
        Livewire::test(ManageUsers::class)->callAction('create', data: [
            'name' => 'New Partner', 'email' => 'new@example.test', 'company_id' => $company->id,
            'password' => 'DemoPassword123!', 'is_active' => true,
        ])->assertHasNoActionErrors();
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('DemoPassword123!', $user->password));
        $this->assertFalse($user->is_admin);
        $this->assertSame($company->id, $user->company_id);
    }

    public function test_editing_a_user_with_blank_password_preserves_their_password(): void
    {
        $client = $this->client();
        $hash = $client->password;
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageUsers::class)->callAction(TestAction::make('edit')->table($client), data: [
            'name' => 'Updated name', 'password' => '',
        ])->assertHasNoActionErrors();
        $this->assertSame($hash, $client->fresh()->password);
        $this->assertSame('Updated name', $client->fresh()->name);
    }

    public function test_demo_seeding_preserves_existing_edits_and_all_client_pages_load(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $this->seed(PortalDemoSeeder::class);
        $game = Game::where('slug', 'demo-jetx')->firstOrFail();
        $game->update(['title' => 'Administrator edit']);
        $this->seed(PortalDemoSeeder::class);
        $this->assertDatabaseCount('games', 10);
        $this->assertDatabaseCount('resource_items', 23);
        $this->assertSame('Administrator edit', $game->fresh()->title);
        $this->actingAs($this->client());
        foreach (['/dashboard', '/games', '/resources/download', '/resources/documentation', '/resources/certificate', '/roadmap', '/updates', '/engagement-tools'] as $path) {
            $this->get($path)->assertOk();
        }
        Storage::disk('local')->assertExists('demo/banner.svg');
    }
}
