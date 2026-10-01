<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Filament\Resources\PortalNotifications\Pages\ManagePortalNotifications;
use App\Models\Announcement;
use App\Models\Company;
use App\Models\PortalNotification;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class NotificationColorTest extends TestCase
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

    public function test_admin_can_save_announcement_color_independently_of_priority(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $announcement = Announcement::factory()->create(['priority' => 'info']);

        Livewire::test(ManageAnnouncements::class)
            ->callAction(TestAction::make('edit')->table($announcement), data: ['color' => 'pink', 'priority' => 'important'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'color' => 'pink', 'priority' => 'important']);
    }

    public function test_admin_can_save_notification_color_when_editing_content(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $notification = PortalNotification::factory()->create(['title' => 'Original notification']);

        Livewire::test(ManagePortalNotifications::class)
            ->callAction(TestAction::make('edit')->table($notification), data: ['color' => 'green', 'title' => 'Updated notification'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('portal_notifications', ['id' => $notification->id, 'color' => 'green', 'title' => 'Updated notification']);
    }

    public function test_admin_forms_reject_colors_outside_the_fixed_palette(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $announcement = Announcement::factory()->create(['color' => 'green']);
        $notification = PortalNotification::factory()->create(['color' => 'blue']);

        Livewire::test(ManageAnnouncements::class)
            ->callAction(TestAction::make('edit')->table($announcement), data: ['color' => '#ff0000'])
            ->assertHasActionErrors(['color']);
        Livewire::test(ManagePortalNotifications::class)
            ->callAction(TestAction::make('edit')->table($notification), data: ['color' => 'arbitrary-class'])
            ->assertHasActionErrors(['color']);

        $this->assertSame('green', $announcement->refresh()->color);
        $this->assertSame('blue', $notification->refresh()->color);
    }

    public function test_saved_colors_render_on_dashboard_header_and_roadmap(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        Announcement::factory()->create(['title' => 'Custom announcement', 'priority' => 'important', 'color' => 'pink', 'show_on_dashboard' => true, 'show_on_roadmap' => true]);
        PortalNotification::factory()->create(['title' => 'Documentation', 'color' => 'green']);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $response->assertSee('class="text-color-green text-weight-semibold text-size-medium">Documentation</p>', false)
            ->assertSee('class="notifications_item-line text-color-green"', false)
            ->assertSee('class="text-color-pink text-weight-semibold text-size-medium">Custom announcement</p>', false)
            ->assertSee('class="notifications_item-line text-color-pink"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'class="text-color-pink text-weight-semibold text-size-medium">Custom announcement</p>'));
        $this->get('/roadmap')->assertOk()
            ->assertSee('class="text-color-pink text-weight-semibold text-size-medium">Custom announcement</p>', false)
            ->assertSee('class="notifications_item-line text-color-pink"', false);
    }

    public function test_invalid_stored_color_uses_a_safe_palette_fallback(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        Announcement::factory()->create(['title' => 'Fallback announcement', 'color' => 'injected-class']);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('class="text-color-blue text-weight-semibold text-size-medium">Fallback announcement</p>', false)
            ->assertDontSee('injected-class');
    }
}
