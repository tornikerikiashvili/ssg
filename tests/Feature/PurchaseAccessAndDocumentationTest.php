<?php

namespace Tests\Feature;

use App\Filament\Resources\Documentations\Pages\ManageDocumentations;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Models\CatalogOption;
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
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class PurchaseAccessAndDocumentationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_game_assets_require_company_purchase_in_lists_and_direct_downloads(bool $isAdmin): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('game-assets/asset.txt', 'Purchased asset');
        $user = User::factory()->for(Company::factory())->create(['is_admin' => $isAdmin]);
        $other = User::factory()->for(Company::factory())->create();
        $game = Game::factory()->create();
        $asset = ResourceItem::factory()->for($game)->create(['title' => 'Purchase protected asset', 'file_path' => 'game-assets/asset.txt']);
        $other->company->purchasedGames()->attach($game);
        $this->actingAs($user)->get(route('games.show', $game->slug))->assertOk()->assertDontSee($asset->title)
            ->assertDontSee('id="resources"', false)->assertDontSee('Download Assets');
        $this->get('/games')->assertOk()->assertSee($game->title)->assertDontSee(route('games.show', $game->slug).'#resources', false);
        $this->get('/resources/download')->assertOk()->assertDontSee($asset->title)->assertDontSee('data-open-folder="resources-'.$game->id.'"', false);
        $this->get(route('resources.download', $asset))->assertNotFound();
        $this->post(route('games.assets.archive', $game->slug), ['ids' => [$asset->id]])->assertNotFound();
        $this->post(route('assets.basket.archive'), ['ids' => [$asset->id]])->assertNotFound();
        $user->company->purchasedGames()->attach($game);
        $this->get(route('games.show', $game->slug))->assertOk()->assertSee($asset->title)
            ->assertSee('id="resources"', false)->assertSee('Download Assets');
        $this->get('/games')->assertOk()->assertSee(route('games.show', $game->slug).'#resources', false);
        $this->get('/resources/download')->assertOk()->assertSee($asset->title);
        $this->get(route('resources.download', $asset))->assertOk()->assertStreamedContent('Purchased asset');
        $user->company->purchasedGames()->detach($game);
        $this->get(route('resources.download', $asset))->assertNotFound();
    }

    public function test_legacy_game_audience_does_not_restrict_games_but_purchases_still_control_assets(): void
    {
        $game = Game::factory()->for(Company::factory())->create();
        $user = User::factory()->for(Company::factory())->create();
        $asset = ResourceItem::factory()->for($game)->create();
        $this->actingAs($user)->get(route('games.show', $game->slug))->assertOk()
            ->assertDontSee('id="resources"', false);
        $this->assertTrue(Game::roadmapVisibleTo($user)->whereKey($game->id)->exists());
        $this->assertFalse(ResourceItem::visibleTo($user)->whereKey($asset->id)->exists());
        $user->company->purchasedGames()->attach($game);
        $this->assertTrue(ResourceItem::visibleTo($user)->whereKey($asset->id)->exists());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(EditGame::class, ['record' => $game->id])->assertFormFieldDoesNotExist('company_id');
    }

    public function test_dashboard_separates_purchases_from_remaining_region_games(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $bought = Game::factory()->create();
        $region = Region::factory()->create();
        $user->company->regions()->attach($region);
        $available = Game::factory()->count(9)->create();
        foreach ($available as $game) {
            GameRegion::factory()->for($game)->for($region)->create();
        }
        GameRegion::factory()->for($bought)->for($region)->create();
        Game::factory()->count(2)->create();
        GameRegion::factory()->for($region)->create(['status' => 'unavailable']);
        $outside = GameRegion::factory()->create()->game;
        $user->company->purchasedGames()->attach($bought);
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('games', fn ($games) => $games->modelKeys() === [$bought->id])
            ->assertViewHas('purchasedGameCount', 1)->assertViewHas('availableGameCount', 9)
            ->assertViewHas('availableGames', fn ($games) => $games->count() === 6 && ! $games->contains($bought) && ! $games->contains($outside))
            ->assertSee('Available in your Region')->assertSee('Trending Now')->assertSee('Total 9 Available.');
        $user->company->regions()->detach();
        $this->get('/dashboard')->assertOk()
            ->assertViewHas('availableGameCount', 0)
            ->assertViewHas('availableGames', fn ($games) => $games->isEmpty());
    }

    public function test_document_links_and_engagement_placement_respect_visibility(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $link = ResourceItem::factory()->create(['kind' => 'documentation', 'documentation_type' => 'link', 'external_url' => 'https://example.com/guide', 'show_on_engagement_tools' => true]);
        $hidden = ResourceItem::factory()->create(['kind' => 'documentation', 'show_on_engagement_tools' => false]);
        $private = ResourceItem::factory()->for(Company::factory())->create(['kind' => 'documentation', 'show_on_engagement_tools' => true]);
        $draft = ResourceItem::factory()->create(['kind' => 'documentation', 'show_on_engagement_tools' => true, 'is_published' => false]);
        $this->actingAs($user)->get('/engagement-tools')->assertOk()->assertSee($link->title)->assertSee('Open link')
            ->assertDontSee($hidden->title)->assertDontSee($private->title)->assertDontSee($draft->title);
        $this->get('/resources/documentation')->assertOk()->assertSee($link->title)->assertSee('Open link');
        $this->get(route('resources.show', $link))->assertRedirect('https://example.com/guide');
        $this->get(route('resources.show', $private))->assertNotFound();
        $link->update(['external_url' => 'javascript:alert(1)']);
        $this->get(route('resources.show', $link))->assertNotFound();
    }

    public function test_documentation_form_saves_link_and_placement_and_rejects_unsafe_urls(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $category = CatalogOption::factory()->create(['kind' => 'document']);
        Livewire::test(ManageDocumentations::class)->callAction('create', data: [
            'title' => 'Linked guide', 'kind' => 'documentation', 'catalog_option_id' => $category->id,
            'documentation_type' => 'link', 'external_url' => 'https://example.com/docs',
            'show_on_engagement_tools' => true, 'is_published' => true,
        ])->assertHasNoActionErrors();
        $document = ResourceItem::where('title', 'Linked guide')->firstOrFail();
        $this->assertTrue($document->show_on_engagement_tools);
        $this->assertTrue($document->isDocumentationLink());
        Livewire::test(ManageDocumentations::class)->callAction(TestAction::make('edit')->table($document), data: [
            'external_url' => 'javascript:alert(1)',
        ])->assertHasActionErrors(['external_url']);
        Livewire::test(ManageDocumentations::class)->callAction(TestAction::make('edit')->table($document), data: [
            'documentation_type' => 'file', 'show_on_engagement_tools' => false,
        ])->assertHasNoActionErrors();
        $this->assertFalse($document->fresh()->isDocumentationLink());
        $this->assertFalse($document->fresh()->show_on_engagement_tools);
    }
}
