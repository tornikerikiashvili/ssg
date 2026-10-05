<?php

namespace Tests\Feature;

use App\Filament\Resources\EngagementTools\Pages\ManageEngagementTools;
use App\Models\EngagementTool;
use App\Models\Game;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class EngagementToolTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    public function test_cover_upload_is_saved_and_displayed_on_tools_and_game_pages(): void
    {
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageEngagementTools::class)->callAction('create', data: [
            'title' => 'Coin Flip', 'slug' => 'coin-flip',
            'cover_image' => UploadedFile::fake()->image('cover.png'),
        ])->assertHasNoActionErrors();

        $tool = EngagementTool::where('slug', 'coin-flip')->firstOrFail();
        Storage::disk('public')->assertExists($tool->cover_image);
        $this->assertTrue($tool->is_published);
        $this->assertFalse($tool->is_demo);
        $game = Game::factory()->create();
        $game->engagementTools()->attach($tool);
        $this->get(route('tools.index'))->assertOk()->assertSee($tool->coverImageUrl(), false);
        $this->get(route('games.show', $game->slug))->assertOk()->assertSee($tool->coverImageUrl(), false);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ManageEngagementTools::class)->callAction('create', data: [
            'title' => 'Invalid tool', 'slug' => 'invalid-tool',
            'cover_image' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ])->assertHasActionErrors(['cover_image']);

        $this->assertDatabaseMissing('engagement_tools', ['slug' => 'invalid-tool']);
    }
}
