<?php

namespace Tests\Feature;

use App\Filament\Resources\FileFormats\Pages\ManageFileFormats;
use App\Models\Company;
use App\Models\FileFormat;
use App\Models\Game;
use App\Models\ResourceItem;
use App\Models\User;
use App\SyncDropboxGame;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AssetPreviewTest extends TestCase
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
        config(['services.dropbox.access_token' => 'test-token', 'cache.default' => 'array']);
        Cache::flush();
        Http::preventStrayRequests();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function png(): string
    {
        $image = UploadedFile::fake()->image('thumbnail.png', 64, 64);

        return file_get_contents($image->getRealPath());
    }

    private function imageAsset(array $attributes = []): ResourceItem
    {
        return ResourceItem::factory()->for(Game::factory())->create($attributes + [
            'kind' => 'download', 'file_path' => 'photo.PNG', 'dropbox_file_id' => 'id:photo',
            'dropbox_revision' => 'rev1', 'file_size' => 1024, 'dropbox_available' => true,
        ]);
    }

    public function test_thumbnail_is_cached_privately_and_a_new_revision_fetches_a_new_preview(): void
    {
        Storage::fake('local');
        $png = $this->png();
        Http::fake(['*/get_thumbnail_v2' => fn () => Http::response($png, 200)]);
        $asset = $this->imageAsset();
        $user = User::factory()->for(Company::factory())->create();
        $user->company->purchasedGames()->attach($asset->game_id);
        $this->actingAs($user);
        $url = route('resources.thumbnail', $asset);

        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png')->assertContent($png);
        config(['services.dropbox.access_token' => null]);
        $this->get($url)->assertOk()->assertContent($png);
        Http::assertSentCount(1);
        $this->assertCount(1, Storage::disk('local')->allFiles('dropbox-thumbnails'));

        config(['services.dropbox.access_token' => 'test-token']);
        $asset->update(['dropbox_revision' => 'rev2']);
        $this->get($url)->assertOk()->assertContent($png);
        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            $arguments = json_decode($request->header('Dropbox-API-Arg')[0], true);

            return $arguments['resource']['path'] === 'id:photo'
                && $arguments['format'] === 'png'
                && $arguments['preserve_transparency'] === true;
        });
        $this->assertDatabaseCount('resource_downloads', 0);
    }

    public function test_cached_thumbnails_still_require_current_resource_access(): void
    {
        Storage::fake('local');
        Http::fake(['*/get_thumbnail_v2' => Http::response($this->png())]);
        $owner = User::factory()->for(Company::factory())->create();
        $asset = $this->imageAsset(['company_id' => $owner->company_id]);
        $url = route('resources.thumbnail', $asset);
        $owner->company->purchasedGames()->attach($asset->game_id);
        $this->actingAs($owner)->get($url)->assertOk();

        $this->actingAs(User::factory()->for(Company::factory())->create())->get($url)->assertNotFound();
        $owner->company->purchasedGames()->detach($asset->game_id);
        $this->actingAs($owner)->get($url)->assertNotFound();
        $owner->company->purchasedGames()->attach($asset->game_id);
        $asset->update(['dropbox_available' => false]);
        $this->actingAs($owner)->get($url)->assertNotFound();
        Http::assertSentCount(1);
    }

    public function test_failed_thumbnail_uses_custom_icon_and_retries_after_failure_cache_expires(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->freezeTime();
        $format = FileFormat::factory()->create(['extension' => 'png']);
        $icon = $format->addMedia(UploadedFile::fake()->image('icon.png'))->toMediaCollection('icon');
        $asset = $this->imageAsset(['file_format_id' => $format->id]);
        Http::fake(['*/get_thumbnail_v2' => Http::sequence()->push([], 401)->push($this->png())]);
        $user = User::factory()->for(Company::factory())->create();
        $user->company->purchasedGames()->attach($asset->game_id);
        $this->actingAs($user);

        $this->get('/games/'.$asset->game->slug)->assertOk()->assertSee($icon->getUrl())->assertSee(route('resources.thumbnail', $asset));
        $this->get(route('resources.thumbnail', $asset))->assertNotFound();
        $this->get(route('resources.thumbnail', $asset))->assertNotFound();
        Http::assertSentCount(1);
        $this->travel(6)->minutes();
        $this->get(route('resources.thumbnail', $asset))->assertOk();
        Http::assertSentCount(2);
    }

    public function test_non_images_and_invalid_thumbnail_responses_are_not_cached(): void
    {
        Storage::fake('local');
        Http::fake(['*/get_thumbnail_v2' => Http::response('<html>not an image</html>')]);
        $asset = $this->imageAsset();
        $video = $this->imageAsset(['file_path' => 'clip.mp4', 'dropbox_file_id' => 'id:video']);
        $user = User::factory()->for(Company::factory())->create();
        $user->company->purchasedGames()->attach($asset->game_id);
        $this->actingAs($user);

        $user->company->purchasedGames()->attach($video->game_id);
        $this->get(route('resources.thumbnail', $video))->assertNotFound();
        Http::assertNothingSent();
        $this->get(route('resources.thumbnail', $asset))->assertNotFound();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_sync_discovers_formats_once_and_preserves_admin_icons(): void
    {
        Storage::fake('public');
        $format = FileFormat::factory()->create(['extension' => 'mp4']);
        $icon = $format->addMedia(UploadedFile::fake()->image('video.png'))->toMediaCollection('icon');
        $game = Game::factory()->create(['dropbox_folder_path' => '/Assets']);
        $files = [];
        foreach (['video.MP4', 'other.mp4', 'drawing.efs', 'README'] as $index => $name) {
            $files[] = ['.tag' => 'file', 'id' => 'id:'.$index, 'name' => $name, 'path_display' => '/Assets/'.$name, 'rev' => 'r1', 'size' => 10];
        }
        Http::fake([
            '*/get_metadata' => Http::response(['.tag' => 'folder', 'id' => 'id:folder', 'path_display' => '/Assets']),
            '*/list_folder' => Http::response(['entries' => $files, 'has_more' => false]),
        ]);

        app(SyncDropboxGame::class)->sync($game);
        app(SyncDropboxGame::class)->sync($game);

        $this->assertDatabaseCount('file_formats', 2);
        $this->assertDatabaseHas('file_formats', ['extension' => 'efs']);
        $this->assertSame($icon->id, $format->fresh()->getFirstMedia('icon')->id);
        $this->assertSame(2, $game->resources()->where('file_format_id', $format->id)->count());
        $user = User::factory()->for(Company::factory())->create();
        $user->company->purchasedGames()->attach($game);
        $this->actingAs($user)->get('/games/'.$game->slug)->assertSee($icon->getUrl());
    }

    public function test_admin_can_upload_an_svg_format_icon(): void
    {
        Storage::fake('public');
        $format = FileFormat::factory()->create(['extension' => 'mp4']);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4h16v16H4z" fill="#08B89D"/></svg>';
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageFileFormats::class)
            ->callAction(TestAction::make('edit')->table($format), data: [
                'icon' => UploadedFile::fake()->createWithContent('video.svg', $svg),
            ])->assertHasNoActionErrors();

        $icon = $format->fresh()->getFirstMedia('icon');
        $this->assertNotNull($icon);
        $this->assertSame('image/svg+xml', $icon->mime_type);
        $this->assertSame($svg, Storage::disk('public')->get($icon->getPathRelativeToRoot()));
        $resource = ResourceItem::factory()->make(['file_format_id' => $format->id, 'file_path' => 'video.mp4', 'id' => 1]);
        $this->blade('<x-original-assets :resources="$resources" />', ['resources' => collect([$resource])])
            ->assertSee($icon->getUrl());
    }

    public function test_admin_can_assign_an_icon_and_partner_cannot_access_format_management(): void
    {
        Storage::fake('public');
        $format = FileFormat::factory()->create(['extension' => 'efs']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageFileFormats::class)
            ->callAction(TestAction::make('edit')->table($format), data: ['icon' => UploadedFile::fake()->image('efs.png')])
            ->assertHasNoActionErrors();

        $icon = $format->fresh()->getFirstMedia('icon');
        $this->assertNotNull($icon);
        Storage::disk('public')->assertExists($icon->getPathRelativeToRoot());
        Livewire::test(ManageFileFormats::class)
            ->callAction(TestAction::make('edit')->table($format), data: ['icon' => UploadedFile::fake()->create('invalid.pdf', 10, 'application/pdf')])
            ->assertHasActionErrors(['icon']);
        $this->assertSame($icon->id, $format->fresh()->getFirstMedia('icon')->id);
        $this->actingAs(User::factory()->for(Company::factory())->create())->get('/admin/file-formats')->assertForbidden();
    }
}
