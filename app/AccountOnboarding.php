<?php

namespace App;

use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Notifications\PasswordCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AccountOnboarding
{
    public function invite(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $password = Str::password(20);
            $user->forceFill([
                'password' => $password, 'requires_password_setup' => true,
                'invitation_expires_at' => now()->addDay(), 'remember_token' => Str::random(60),
            ])->save();
            $user->notify(new AccountInvitation($password));
        });
    }

    public function start(Request $request, ?User $user): void
    {
        if ($old = $request->session()->get('password_challenge')) {
            Cache::forget('password-challenge:'.$old);
        }
        $id = Str::random(64);
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(10);
        Cache::put('password-challenge:'.$id, [
            'user_id' => $user?->id, 'email' => $user?->email,
            'password_hash' => $user?->password, 'code_hash' => Hash::make($code),
            'verified' => false, 'expires_at' => $expires->timestamp,
        ], $expires);
        $request->session()->regenerate();
        $request->session()->put('password_challenge', $id);
        if ($user) {
            $user->notify(new PasswordCode($code));
        }
    }

    public function canSend(string $email): bool
    {
        $key = 'password-code-send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return false;
        }
        RateLimiter::hit($key, 600);

        return true;
    }
}
