<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

class ClientAccessTest extends TestCase
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
    }

    private function client(array $attributes = []): User
    {
        $company = Company::create(['name' => 'Test Partner']);

        return User::factory()->create(array_merge(['company_id' => $company->id], $attributes));
    }

    public function test_guests_are_redirected_to_the_client_login(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Welcome back');
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_client_can_login_and_see_only_their_own_account(): void
    {
        $user = $this->client(['email' => 'partner@example.test']);
        $other = $this->client(['email' => 'other@example.test']);

        $this->post('/login', [
            'email' => ' PARTNER@EXAMPLE.TEST ',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk()->assertSee($user->email)->assertDontSee($other->email);
    }

    public function test_wrong_password_does_not_authenticate(): void
    {
        $user = $this->client();
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = $this->client();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
                ->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertTrue(RateLimiter::tooManyAttempts('client-login:'.hash('sha256', $user->email.'|127.0.0.1'), 5));
    }

    public function test_disabled_users_cannot_login(): void
    {
        $user = $this->client(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_disabled_companies_cannot_login(): void
    {
        $user = $this->client();
        $user->company->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unassigned_clients_cannot_login(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_revocation_ends_existing_client_access(): void
    {
        $user = $this->client();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $user->forceFill(['is_active' => false])->save();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_company_revocation_ends_existing_client_access(): void
    {
        $user = $this->client();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $user->company->update(['is_active' => false]);
        $user->unsetRelation('company');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_client_cannot_access_filament_admin(): void
    {
        $this->actingAs($this->client())->get('/admin')->assertForbidden();
    }

    public function test_active_admin_can_access_both_interfaces(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->get('/dashboard')->assertOk()->assertDontSee('Administration');
    }

    public function test_disabled_admin_cannot_access_filament(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => false]);
        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    public function test_privilege_fields_cannot_be_mass_assigned(): void
    {
        $user = $this->client();
        $user->fill(['is_admin' => true, 'company_id' => null, 'is_active' => false]);
        $this->assertFalse($user->is_admin);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->company_id);
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs($this->client())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
