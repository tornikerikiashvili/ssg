<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use App\Models\Game;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CompanyPurchasedGamesTest extends TestCase
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

    public function test_company_form_saves_updates_and_clears_purchased_games(): void
    {
        $games = Game::factory()->count(3)->create();
        Livewire::test(ManageCompanies::class)->callAction('create', data: [
            'name' => 'Purchasing company',
            'is_active' => true,
            'purchasedGames' => [$games[0]->id, $games[1]->id],
        ])->assertHasNoActionErrors();
        $company = Company::where('name', 'Purchasing company')->firstOrFail();
        $this->assertEqualsCanonicalizing([$games[0]->id, $games[1]->id], $company->purchasedGames()->pluck('games.id')->all());
        $other = Company::factory()->create();
        $other->purchasedGames()->attach($games[0]);

        Livewire::test(ManageCompanies::class)
            ->mountAction(TestAction::make('edit')->table($company))
            ->assertSchemaStateSet(['purchasedGames' => [$games[0]->id, $games[1]->id]])
            ->fillForm(['purchasedGames' => [$games[1]->id, $games[2]->id]])
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertEqualsCanonicalizing([$games[1]->id, $games[2]->id], $company->purchasedGames()->pluck('games.id')->all());
        Livewire::test(ManageCompanies::class)->callAction(TestAction::make('edit')->table($company), data: ['purchasedGames' => []])
            ->assertHasNoActionErrors();
        $this->assertSame([], $company->purchasedGames()->pluck('games.id')->all());
        $this->assertSame([$games[0]->id], $other->purchasedGames()->pluck('games.id')->all());
    }

    public function test_deleted_games_and_companies_remove_purchase_links(): void
    {
        $company = Company::factory()->create();
        $games = Game::factory()->count(2)->create();
        $company->purchasedGames()->attach($games->modelKeys());
        $games[0]->delete();
        $this->assertDatabaseMissing('company_game', ['game_id' => $games[0]->id]);
        $company->delete();
        $this->assertDatabaseCount('company_game', 0);
        $this->assertModelExists($games[1]);
    }
}
