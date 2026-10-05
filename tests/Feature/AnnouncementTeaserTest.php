<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use App\Models\Company;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AnnouncementTeaserTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    public function test_placements_control_publication_without_publish_or_demo_fields(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageAnnouncements::class)->mountAction('create')
            ->assertFormFieldDoesNotExist('is_published')->assertFormFieldDoesNotExist('is_demo')
            ->fillForm(['title' => 'Placement announcement', 'show_on_dashboard' => true, 'show_on_roadmap' => false])
            ->callMountedAction()->assertHasNoActionErrors();
        $announcement = Announcement::where('title', 'Placement announcement')->firstOrFail();
        $this->assertTrue($announcement->is_published);
        foreach ([[false, true, true], [false, false, false], [true, false, true]] as [$dashboard, $roadmap, $published]) {
            Livewire::test(ManageAnnouncements::class)->callAction(TestAction::make('edit')->table($announcement), data: [
                'show_on_dashboard' => $dashboard, 'show_on_roadmap' => $roadmap,
            ])->assertHasNoActionErrors();
            $this->assertSame($published, $announcement->fresh()->is_published);
            $this->assertSame($published, Announcement::visibleTo(auth()->user())->whereKey($announcement->id)->exists());
        }
    }

    public function test_admin_can_save_and_clear_teasers_but_cannot_exceed_the_length_limit(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $announcement = Announcement::factory()->create();

        Livewire::test(ManageAnnouncements::class)
            ->callAction(TestAction::make('edit')->table($announcement), data: ['teaser' => 'A short summary'])
            ->assertHasNoActionErrors();
        $this->assertSame('A short summary', $announcement->refresh()->teaser);

        Livewire::test(ManageAnnouncements::class)
            ->callAction(TestAction::make('edit')->table($announcement), data: ['teaser' => str_repeat('a', 256)])
            ->assertHasActionErrors(['teaser' => 'max']);
        $this->assertSame('A short summary', $announcement->refresh()->teaser);

        Livewire::test(ManageAnnouncements::class)
            ->callAction(TestAction::make('edit')->table($announcement), data: ['teaser' => null])
            ->assertHasNoActionErrors();
        $this->assertNull($announcement->refresh()->teaser);
    }

    public function test_announcement_teasers_are_escaped_and_shown_on_all_announcement_surfaces(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $announcement = Announcement::factory()->create([
            'title' => 'Partner announcement', 'teaser' => '<script>teaser()</script>',
            'description' => 'Full announcement details', 'show_on_dashboard' => true, 'show_on_roadmap' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSeeInOrder(['Partner announcement', '&lt;script&gt;teaser()&lt;/script&gt;', 'Full announcement details'], false)
            ->assertDontSee('<script>teaser()</script>', false);
        $this->assertSame(2, substr_count($response->getContent(), '<p class="text-color-text">&lt;script&gt;teaser()&lt;/script&gt;</p>'));
        foreach (['/roadmap', '/updates'] as $path) {
            $this->get($path)->assertOk()->assertSee('&lt;script&gt;teaser()&lt;/script&gt;', false)
                ->assertDontSee('<script>teaser()</script>', false);
        }

        $announcement->update(['teaser' => null]);
        $this->get('/dashboard')->assertOk()->assertSee('Partner announcement')->assertSee('Full announcement details')
            ->assertDontSee('&lt;script&gt;teaser()&lt;/script&gt;', false);
    }
}
