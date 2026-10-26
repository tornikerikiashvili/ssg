<?php

namespace Tests\Feature;

use App\AccountOnboarding;
use App\Filament\Resources\Users\UserResource;
use App\Models\Company;
use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Notifications\PasswordChanged;
use App\Notifications\PasswordCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class PasswordSetupTest extends TestCase
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
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    private function client(): User
    {
        return User::factory()->for(Company::factory())->create();
    }

    private function code(User $user): string
    {
        return Notification::sent($user, PasswordCode::class)->last()->code;
    }

    public function test_invitation_requires_code_and_new_password_before_portal_access(): void
    {
        Notification::fake();
        $user = $this->client();
        app(AccountOnboarding::class)->invite($user);
        $invitation = Notification::sent($user, AccountInvitation::class)->first();
        $this->assertTrue(Hash::check($invitation->temporaryPassword, $user->fresh()->password));

        $this->post('/login', ['email' => $user->email, 'password' => $invitation->temporaryPassword])->assertRedirect('/password/verify');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertFalse(Hash::check($invitation->temporaryPassword, $user->fresh()->password));
        $this->post('/password/set', ['password' => 'new-partner-password', 'password_confirmation' => 'new-partner-password'])->assertSessionHasErrors('password');
        $this->post('/password/verify', ['code' => $this->code($user)])->assertRedirect('/password/verify');
        $this->get('/password/verify')->assertSee('Set your new password');
        $this->post('/password/set', ['password' => 'new-partner-password', 'password_confirmation' => 'new-partner-password'])->assertRedirect('/login');
        $this->assertFalse($user->fresh()->requires_password_setup);
        Notification::assertSentToTimes($user, PasswordChanged::class, 1);
        $this->post('/login', ['email' => $user->email, 'password' => 'new-partner-password'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
    }

    public function test_recovery_revokes_sessions_and_cannot_be_replayed(): void
    {
        Notification::fake();
        $user = $this->client();
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert(['id' => 'old-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect('/password/verify');
        $this->post('/password/verify', ['code' => $this->code($user)])->assertRedirect('/password/verify');
        $data = ['password' => 'replacement-password', 'password_confirmation' => 'replacement-password'];
        $this->post('/password/set', $data)->assertRedirect('/login');
        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-device']);
        $this->post('/password/set', $data)->assertSessionHasErrors('password');
        Notification::assertSentToTimes($user, PasswordChanged::class, 1);
    }

    public function test_unknown_and_inactive_accounts_receive_same_response_without_email(): void
    {
        Notification::fake();
        $user = $this->client();
        $user->forceFill(['is_active' => false])->save();
        foreach ([$user->email, 'unknown@example.test'] as $email) {
            $this->post('/forgot-password', ['email' => $email])->assertRedirect('/password/verify')
                ->assertSessionHas('status', 'If this email has an active account, a verification code has been sent.');
        }
        Notification::assertNothingSent();
    }

    public function test_codes_expire_and_are_limited_to_five_attempts_even_after_resending(): void
    {
        Notification::fake();
        $user = $this->client();
        $this->post('/forgot-password', ['email' => $user->email]);
        $code = $this->code($user);
        $wrong = $code === '0000' ? '1111' : '0000';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/password/verify', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post('/forgot-password', ['email' => $user->email]);
        $this->post('/password/verify', ['code' => $this->code($user)])->assertSessionHasErrors('code');
        $this->travel(11)->minutes();
        $this->post('/password/verify', ['code' => $this->code($user)])->assertSessionHasErrors('code');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_changed_email_or_disabled_user_cannot_complete_verified_request(): void
    {
        Notification::fake();
        $user = $this->client();
        $this->post('/forgot-password', ['email' => $user->email]);
        $this->post('/password/verify', ['code' => $this->code($user)]);
        $user->update(['email' => 'changed@example.test']);
        $this->post('/password/set', ['password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        Notification::assertNotSentTo($user, PasswordChanged::class);
    }

    public function test_admin_creates_invitation_without_choosing_or_retrieving_password(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $user = UserResource::saveUser(new User, ['name' => 'Partner', 'email' => 'new@example.test', 'company_id' => Company::factory()->create()->id, 'password' => 'admin-chosen-password']);
        Notification::assertSentToTimes($user, AccountInvitation::class, 1);
        $this->assertTrue($user->requires_password_setup);
        $this->assertFalse(Hash::check('admin-chosen-password', $user->password));
        $oldPassword = $user->password;
        UserResource::saveUser($user, ['name' => 'Updated']);
        $this->assertSame($oldPassword, $user->fresh()->password);
        Notification::assertSentToTimes($user, AccountInvitation::class, 1);
    }

    public function test_expired_invitation_is_rejected_and_resend_invalidates_previous_password(): void
    {
        Notification::fake();
        $user = $this->client();
        app(AccountOnboarding::class)->invite($user);
        $old = Notification::sent($user, AccountInvitation::class)->last()->temporaryPassword;
        $this->travel(25)->hours();
        $this->post('/login', ['email' => $user->email, 'password' => $old])->assertSessionHasErrors('email');
        app(AccountOnboarding::class)->invite($user);
        $this->assertFalse(Hash::check($old, $user->fresh()->password));
        $new = Notification::sent($user, AccountInvitation::class)->last()->temporaryPassword;
        $this->post('/login', ['email' => $user->email, 'password' => $new])->assertRedirect('/password/verify');
    }

    public function test_verified_code_expires_and_pending_accounts_cannot_bypass_setup(): void
    {
        Notification::fake();
        $user = $this->client();
        $this->post('/forgot-password', ['email' => $user->email]);
        $this->post('/password/verify', ['code' => $this->code($user)]);
        $this->travel(11)->minutes();
        $this->post('/password/set', ['password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertSessionHasErrors('password');
        Notification::assertNotSentTo($user, PasswordChanged::class);
        $user->forceFill(['requires_password_setup' => true])->save();
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/forgot-password');
        $this->assertGuest();
    }

    public function test_code_requests_are_limited_and_new_code_replaces_previous_challenge(): void
    {
        Notification::fake();
        $user = $this->client();
        $this->post('/forgot-password', ['email' => $user->email]);
        $oldChallenge = session('password_challenge');
        $this->post('/forgot-password', ['email' => $user->email]);
        $this->assertNull(Cache::get('password-challenge:'.$oldChallenge));
        $this->post('/forgot-password', ['email' => $user->email]);
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect('/password/verify');
        Notification::assertSentToTimes($user, PasswordCode::class, 3);
    }
}
