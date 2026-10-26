<?php

namespace App\Http\Controllers\Auth;

use App\AccountOnboarding;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordSetupController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.password-setup', ['step' => 'email']);
    }

    public function send(Request $request, AccountOnboarding $onboarding): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = Str::lower(trim($data['email']));
        $user = User::where('email', $email)->first();
        $eligible = $onboarding->canSend($email) && $user?->canAccessClientArea();
        $onboarding->start($request, $eligible ? $user : null);

        return to_route('password.code')->with('status', 'If this email has an active account, a verification code has been sent.');
    }

    public function codeForm(Request $request): View|RedirectResponse
    {
        $challenge = Cache::get('password-challenge:'.$request->session()->get('password_challenge'));
        if (! $challenge) {
            return to_route('password.request')->withErrors(['email' => 'Your code expired. Please request a new one.']);
        }

        return view('auth.password-setup', ['step' => $challenge['verified'] ? 'password' : 'code']);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:4']]);
        $key = 'password-challenge:'.$request->session()->get('password_challenge');
        Cache::lock($key.':lock', 10)->block(5, function () use ($request, $key): void {
            $challenge = Cache::get($key);
            $limitKey = 'password-code-attempts:'.($challenge['user_id'] ?? hash('sha256', $key));
            if (! $challenge || $challenge['verified'] || RateLimiter::tooManyAttempts($limitKey, 5)) {
                throw ValidationException::withMessages(['code' => 'Code expired or too many attempts. Please request a new code later.']);
            }
            RateLimiter::hit($limitKey, 600);
            if (! $challenge['user_id'] || ! Hash::check($request->string('code')->toString(), $challenge['code_hash'])) {
                throw ValidationException::withMessages(['code' => 'The verification code is incorrect.']);
            }
            $challenge['verified'] = true;
            unset($challenge['code_hash']);
            Cache::put($key, $challenge, max(1, $challenge['expires_at'] - now()->timestamp));
        });

        return to_route('password.code');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:12', 'max:72', 'confirmed']]);
        $key = 'password-challenge:'.$request->session()->get('password_challenge');
        Cache::lock($key.':lock', 10)->block(5, function () use ($request, $key): void {
            $challenge = Cache::get($key);
            if (! $challenge || ! $challenge['verified']) {
                throw ValidationException::withMessages(['password' => 'Please verify a valid email code first.']);
            }
            DB::transaction(function () use ($request, $challenge): void {
                $user = User::lockForUpdate()->find($challenge['user_id']);
                if (! $user?->canAccessClientArea() || $user->email !== $challenge['email'] || ! hash_equals($user->password, $challenge['password_hash'])) {
                    throw ValidationException::withMessages(['password' => 'This request is no longer valid. Please start again.']);
                }
                if (Hash::check($request->string('password')->toString(), $user->password)) {
                    throw ValidationException::withMessages(['password' => 'Please choose a different password.']);
                }
                $user->forceFill([
                    'password' => $request->input('password'), 'requires_password_setup' => false,
                    'invitation_expires_at' => null, 'email_verified_at' => now(), 'remember_token' => Str::random(60),
                ])->save();
                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }
                $user->notify(new PasswordChanged);
            });
            Cache::forget($key);
        });
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('status', 'Your password has been set. Please sign in with your new password.');
    }
}
