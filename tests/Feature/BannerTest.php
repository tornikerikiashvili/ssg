<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Models\Banner;
use App\Models\Company;
use App\Models\Game;
use App\Models\User;
use Dom\HTMLDocument;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    public function test_banners_obey_page_order_publication_and_company_and_game_access(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        Banner::factory()->create(['title' => 'Second banner', 'sort_order' => 20, 'pages' => ['dashboard', 'roadmap']]);
        Banner::factory()->create(['title' => 'First banner', 'sort_order' => 10, 'pages' => ['dashboard', 'roadmap']]);
        Banner::factory()->create(['title' => 'Games only', 'pages' => ['games']]);
        Banner::factory()->create(['title' => 'Draft banner', 'is_published' => false]);
        Banner::factory()->for(Company::factory())->create(['title' => 'Private banner']);
        $privateGame = Game::factory()->for(Company::factory())->create();
        Banner::factory()->create(['title' => 'Private game banner', 'game_id' => $privateGame->id]);
        $response = $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('First banner')->assertDontSee('Second banner')->assertDontSee('Games only')->assertDontSee('Draft banner')->assertDontSee('Private banner')->assertDontSee('Private game banner');
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $this->assertCount(1, $document->querySelectorAll('.dashboard-row > .banner[data-cms-banner]'));
        $this->assertCount(1, $document->querySelectorAll('[data-cms-banner]'));
        $this->get('/roadmap')->assertOk()->assertSeeInOrder(['First banner', 'Second banner']);
        $this->get('/games')->assertOk()->assertSee('Games only')->assertDontSee('First banner');
        $this->get('/admin/banners')->assertForbidden();
    }

    public function test_banner_uses_shared_game_stats_and_safe_button_destinations(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $game = Game::factory()->create(['rtp' => 96.5]);
        $banner = Banner::factory()->create(['game_id' => $game->id, 'show_game_stats' => true, 'primary_target' => 'game', 'primary_label' => 'Discover', 'secondary_target' => 'url', 'secondary_label' => 'Unsafe CTA', 'secondary_url' => 'javascript:alert(1)']);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('96.50%')->assertSee(route('games.show', $game->slug), false)->assertDontSee('Unsafe CTA')->assertDontSee('javascript:alert(1)', false);
        $game->update(['rtp' => 97.5]);
        $this->get('/dashboard')->assertSee('97.50%');
        $game->update(['is_published' => false]);
        $this->get('/dashboard')->assertDontSee($banner->title);
    }

    public function test_uploaded_media_uses_the_original_page_frame_and_video_is_not_hidden_by_default_artwork(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $banner = Banner::factory()->create([
            'pages' => ['dashboard', 'roadmap'], 'video_path' => 'banners/custom.mp4',
            'original_artwork' => 'cheesy', 'use_original_video' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $this->assertCount(1, $document->querySelectorAll('[data-cms-banner] .banner_image-wrapper:not(.is-game-cover) > video.fullsize-img[autoplay][muted][loop][playsinline]'));
        $response->assertSee('/storage/banners/custom.mp4', false)
            ->assertDontSee('e020298b5f0f8d25d27842e7c8a17ba9d424f6a5.png')
            ->assertDontSee('w-background-video');
        $roadmap = HTMLDocument::createFromString($this->get('/roadmap')->assertOk()->getContent(), LIBXML_NOERROR);
        $this->assertCount(1, $roadmap->querySelectorAll('[data-cms-banner] .banner_image-wrapper.is-game-cover > video.fullsize-img'));

        $banner->update(['video_path' => null, 'image_path' => 'banners/custom.webp', 'use_original_video' => false]);
        $response = $this->get('/dashboard')->assertOk()->assertSee('/storage/banners/custom.webp', false);
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $this->assertCount(1, $document->querySelectorAll('[data-cms-banner] .banner_image-wrapper:not(.is-game-cover) > img.fullsize-img'));
        $this->assertCount(0, $document->querySelectorAll('[data-cms-banner] video'));
    }

    public function test_admin_can_create_banner_and_invalid_destinations_are_rejected(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $data = ['title' => 'CMS banner', 'variant' => 'blue', 'pages' => ['games', 'roadmap'], 'sort_order' => 5, 'primary_target' => 'url', 'primary_label' => 'Explore', 'primary_url' => 'https://example.com', 'secondary_target' => 'none', 'is_published' => true];
        $this->get('/admin/banners/create')->assertOk();
        Livewire::test(CreateBanner::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('banners', ['title' => 'CMS banner', 'variant' => 'blue']);
        Livewire::test(CreateBanner::class)->fillForm([...$data, 'primary_url' => 'javascript:alert(1)'])->call('create')->assertHasFormErrors(['primary_url']);
        Livewire::test(CreateBanner::class)->fillForm([...$data, 'pages' => []])->call('create')->assertHasFormErrors(['pages']);
        $this->assertDatabaseCount('banners', 1);
    }

    public function test_play_game_button_uses_the_saved_test_url_and_can_be_disabled(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $banner = Banner::factory()->create(['secondary_label' => 'Old label', 'secondary_target' => 'none']);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(['secondary_url' => 'https://example.com/test-play?game=demo'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('https://example.com/test-play?game=demo', $banner->refresh()->secondary_url);
        $response = $this->get('/dashboard')->assertOk()->assertSee('Play Game')->assertDontSee('Old label');
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $button = $document->querySelector('[data-cms-banner] a.is-secondary');
        $this->assertSame('https://example.com/test-play?game=demo', $button?->getAttribute('href'));

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(['secondary_url' => 'javascript:alert(1)'])
            ->call('save')->assertHasFormErrors(['secondary_url']);
        $this->assertSame('https://example.com/test-play?game=demo', $banner->refresh()->secondary_url);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(['secondary_url' => null])->call('save')->assertHasNoFormErrors();
        $this->assertNull($banner->refresh()->secondary_url);
        $this->get('/dashboard')->assertOk()->assertDontSee('Play Game');
    }

    public function test_admin_can_upload_an_svg_logo_and_non_image_files_are_rejected(): void
    {
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $banner = Banner::factory()->create();
        $logo = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 40"><rect width="100" height="40" fill="red"/></svg>');

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(['logo_path' => $logo])->call('save')->assertHasNoFormErrors();

        $path = $banner->refresh()->logo_path;
        $this->assertStringEndsWith('.svg', $path);
        Storage::disk('public')->assertExists($path);
        $this->get('/dashboard')->assertOk()->assertSee($banner->mediaUrl('logo_path'), false);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(['logo_path' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')])
            ->call('save')->assertHasFormErrors(['logo_path']);
        $this->assertSame($path, $banner->refresh()->logo_path);
    }

    public function test_admin_can_edit_banner_on_its_own_page_and_clients_cannot_access_it(): void
    {
        $banner = Banner::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/banners')->assertSee('/admin/banners/create', false)->assertSee('/admin/banners/'.$banner->id.'/edit', false);
        $this->get('/admin/banners/'.$banner->id.'/edit')->assertOk();
        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->fillForm(['title' => 'Updated banner', 'pages' => ['roadmap']])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('Updated banner', $banner->refresh()->title);
        $this->assertSame(['roadmap'], $banner->pages);

        $this->actingAs(User::factory()->create());
        $this->get('/admin/banners/create')->assertForbidden();
        $this->get('/admin/banners/'.$banner->id.'/edit')->assertForbidden();
    }
}
