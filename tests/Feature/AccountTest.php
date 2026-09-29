<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Database refresh is restricted to smartsoft_testing.');
        }
    }

    private function client(): User
    {
        return User::factory()->for(Company::factory())->create();
    }

    public function test_account_routes_require_active_authentication(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->patch('/account/contact')->assertRedirect('/login');
        $this->patch('/account/login-details')->assertRedirect('/login');
        $user = $this->client();
        $user->is_active = false;
        $user->save();
        $this->actingAs($user)->get('/account')->assertRedirect('/login');
    }

    public function test_account_shows_only_current_user_and_escapes_their_name(): void
    {
        $user = $this->client();
        $other = $this->client();
        $user->update(['name' => '<script>unsafe()</script>']);
        $this->actingAs($user)->get('/account')->assertOk()->assertSee($user->email)
            ->assertSee('&lt;script&gt;unsafe()&lt;/script&gt;', false)->assertDontSee('<script>unsafe()</script>', false)
            ->assertDontSee($other->email);
    }

    public function test_contact_updates_are_scoped_and_cannot_change_permissions(): void
    {
        $user = $this->client();
        $other = $this->client();
        $this->actingAs($user)->patch('/account/contact', [
            'email' => ' NEW@EXAMPLE.TEST ', 'phone' => '+995 555 123456', 'current_password' => 'password',
            'id' => $other->id, 'company_id' => $other->company_id, 'is_admin' => true, 'is_active' => false,
        ])->assertRedirect('/account')->assertSessionHasNoErrors();
        $fresh = $user->fresh();
        $this->assertSame('new@example.test', $fresh->email);
        $this->assertSame('+995 555 123456', $fresh->phone);
        $this->assertNull($fresh->email_verified_at);
        $this->assertSame($user->company_id, $fresh->company_id);
        $this->assertFalse($fresh->is_admin);
        $this->assertTrue($fresh->is_active);
        $this->assertSame($other->email, $other->fresh()->email);
    }

    public function test_email_change_requires_current_password_and_unique_email(): void
    {
        $user = $this->client();
        $other = $this->client();
        $this->actingAs($user)->patch('/account/contact', ['email' => 'new@example.test'])
            ->assertSessionHasErrorsIn('contact', ['current_password']);
        $this->patch('/account/contact', ['email' => $other->email, 'current_password' => 'wrong'])
            ->assertSessionHasErrorsIn('contact', ['email', 'current_password']);
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_phone_and_name_can_change_without_replacing_password(): void
    {
        $user = $this->client();
        $original = $user->password;
        $this->actingAs($user)->patch('/account/contact', ['email' => $user->email, 'phone' => '123'])
            ->assertSessionHasNoErrors();
        $this->patch('/account/login-details', ['name' => 'Updated Partner', 'password' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame('123', $user->fresh()->phone);
        $this->assertSame('Updated Partner', $user->fresh()->name);
        $this->assertSame($original, $user->fresh()->password);
    }

    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        $user = $this->client();
        $this->actingAs($user)->patch('/account/login-details', [
            'name' => $user->name, 'password' => 'new-secure-password', 'password_confirmation' => 'different',
        ])->assertSessionHasErrorsIn('loginDetails', ['password', 'current_password']);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->patch('/account/login-details', [
            'name' => $user->name, 'password' => 'short', 'password_confirmation' => 'short', 'current_password' => 'wrong',
        ])->assertSessionHasErrorsIn('loginDetails', ['password', 'current_password']);
    }

    public function test_password_is_hashed_and_old_sessions_and_remember_token_are_revoked(): void
    {
        $user = $this->client();
        $token = $user->remember_token;
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert(['id' => 'old-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($user)->patch('/account/login-details', [
            'name' => $user->name, 'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password', 'current_password' => 'password',
        ])->assertRedirect('/account')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->assertNotSame($token, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-device']);
    }
}
