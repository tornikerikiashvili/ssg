<?php

namespace Tests\Feature;

use App\Filament\Resources\PortalNotifications\Pages\ManagePortalNotifications;
use App\Models\Announcement;
use App\Models\Company;
use App\Models\PortalNotification;
use App\Models\ResourceItem;
use App\Models\User;
use Dom\HTMLDocument;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    public function test_admin_dashboard_shows_latest_seven_of_each_communication(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = Company::factory()->create(['name' => 'Dashboard audience']);
        foreach ([PortalNotification::class, Announcement::class] as $model) {
            for ($index = 1; $index <= 8; $index++) {
                $model::factory()->create([
                    'title' => class_basename($model).' item '.$index,
                    'created_at' => now()->subDays(9 - $index),
                    'is_published' => $index !== 8,
                    'company_id' => $index === 8 ? $company->id : null,
                ]);
            }
        }

        $response = $this->get('/admin')->assertOk()
            ->assertSee('Dashboard audience')->assertSee('All clients')
            ->assertSee('Draft')->assertSee('Published');
        foreach ([PortalNotification::class, Announcement::class] as $model) {
            $prefix = class_basename($model).' item ';
            $response->assertDontSee($prefix.'1')
                ->assertSeeInOrder(array_map(fn (int $index): string => $prefix.$index, range(8, 2)));
        }
    }

    public function test_admin_dashboard_shows_empty_communication_lists(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin')->assertOk()
            ->assertSee('No notifications yet.')->assertSee('No announcements yet.')
            ->assertSee('Add notification')->assertSee('Add announcement');
    }

    public function test_admin_can_create_and_delete_notifications_without_creating_other_content(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/notifications')->assertOk();

        Livewire::test(ManagePortalNotifications::class)->callAction('create', data: [
            'title' => 'Partner update', 'teaser' => 'Short partner summary', 'description' => 'New partner information.',
            'color' => 'pink', 'is_published' => true,
        ])->assertHasNoActionErrors();

        $this->assertDatabaseHas('portal_notifications', ['title' => 'Partner update', 'teaser' => 'Short partner summary', 'color' => 'pink', 'is_published' => true]);
        $this->assertDatabaseCount('announcements', 0);
        $this->assertDatabaseCount('resource_items', 0);
        $notification = PortalNotification::where('title', 'Partner update')->firstOrFail();
        Livewire::test(ManagePortalNotifications::class)
            ->callAction(TestAction::make('delete')->table($notification))->assertHasNoActionErrors();
        $this->assertModelMissing($notification);
    }

    public function test_notifications_obey_audience_and_publication_on_dashboard_and_other_page_headers(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        $shared = PortalNotification::factory()->create(['title' => 'Shared notification', 'teaser' => 'Shared summary']);
        $own = PortalNotification::factory()->create(['title' => 'Company notification', 'company_id' => $user->company_id]);
        PortalNotification::factory()->for(Company::factory())->create(['title' => 'Other company notification']);
        PortalNotification::factory()->create(['title' => 'Draft notification', 'is_published' => false]);
        ResourceItem::factory()->create(['title' => 'Library resource']);
        Announcement::factory()->create(['title' => 'Separate announcement']);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('notifications', fn ($items) => $items->modelKeys() === [$own->id, $shared->id])
            ->assertSee('Shared notification')->assertSee('Company notification')->assertSee('<p class="text-color-text">Shared summary</p>', false)
            ->assertDontSee('Other company notification')->assertDontSee('Draft notification')
            ->assertDontSee('Library resource');
        $this->get('/games')->assertOk()->assertSee('Shared notification')->assertSee('Company notification')->assertSee('<p class="text-color-text">Shared summary</p>', false)
            ->assertDontSee('Other company notification')->assertDontSee('Draft notification');
        $this->get('/admin/notifications')->assertForbidden();
        $this->post('/logout');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_documentation_feed_matches_dashboard_content_and_audience_rules(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        PortalNotification::factory()->create(['title' => 'Partner notification', 'teaser' => 'Notification teaser', 'color' => 'pink']);
        PortalNotification::factory()->for(Company::factory())->create(['title' => 'Private notification']);
        PortalNotification::factory()->create(['title' => 'Draft notification', 'is_published' => false]);
        Announcement::factory()->create(['title' => 'Partner announcement', 'teaser' => 'Announcement teaser', 'color' => 'green', 'company_id' => $user->company_id]);
        Announcement::factory()->for(Company::factory())->create(['title' => 'Private announcement']);
        Announcement::factory()->create(['title' => 'Draft announcement', 'is_published' => false]);
        Announcement::factory()->create(['title' => 'Roadmap only announcement', 'show_on_dashboard' => false]);

        $dashboard = HTMLDocument::createFromString($this->actingAs($user)->get('/dashboard')->assertOk()->getContent(), LIBXML_NOERROR);
        $response = $this->get('/resources/documentation')->assertOk();
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $block = $document->querySelector('.is-docs-notifications');
        $this->assertNotNull($block);
        $this->assertSame(
            array_map(fn ($item) => $item->textContent, iterator_to_array($dashboard->querySelectorAll('.dashboard_banner-block .notifications_item-top > p:first-child'))),
            array_map(fn ($item) => $item->textContent, iterator_to_array($block->querySelectorAll('.notifications_item-top > p:first-child'))),
        );
        $this->assertStringContainsString('Notification teaser', $block->textContent);
        $this->assertStringContainsString('Announcement teaser', $block->textContent);
        $this->assertCount(1, $block->querySelectorAll('.notifications_item-line.text-color-pink'));
        $this->assertCount(1, $block->querySelectorAll('.notifications_item-line.text-color-green'));
        $response->assertDontSee('Private notification')->assertDontSee('Draft notification')
            ->assertDontSee('Private announcement')->assertDontSee('Draft announcement')->assertDontSee('Roadmap only announcement')
            ->assertDontSee('REST API v3.2 documentation released')->assertDontSee('Crash Duel X is live in Europe');
    }

    public function test_documentation_feed_shows_empty_states_without_sample_content(): void
    {
        $user = User::factory()->for(Company::factory())->create();

        $response = $this->actingAs($user)->get('/resources/documentation')->assertOk();
        $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
        $block = $document->querySelector('.is-docs-notifications');
        $this->assertNotNull($block);
        $this->assertStringContainsString('No notifications available.', $block->textContent);
        $this->assertStringContainsString('No announcements available.', $block->textContent);
        $this->assertCount(0, $block->querySelectorAll('.notifications_item'));
    }

    public function test_notification_feed_shows_only_the_four_latest_records_and_escapes_content(): void
    {
        $user = User::factory()->for(Company::factory())->create();
        PortalNotification::factory()->create(['title' => 'Old notification', 'created_at' => now()->subDays(5)]);
        PortalNotification::factory()->count(3)->create(['created_at' => now()->subDay()]);
        $latest = PortalNotification::factory()->create(['title' => '<script>bad()</script>', 'teaser' => '<script>teaser()</script>', 'description' => '<img src=x onerror=alert(1)>']);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('notifications', fn ($items) => $items->count() === 4 && $items->first()->id === $latest->id)
            ->assertDontSee('Old notification')->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)
            ->assertSee('&lt;script&gt;teaser()&lt;/script&gt;', false)->assertDontSee('<script>teaser()</script>', false)
            ->assertDontSee('<script>bad()</script>', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
    }
}
