<?php

namespace App\Http\Controllers\Auth;

use App\AccountOnboarding;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AccountOnboarding $onboarding): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        if ($user->requires_password_setup) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            DB::transaction(function () use ($user, $request, $onboarding): void {
                $locked = User::lockForUpdate()->findOrFail($user->id);
                if (! $locked->invitation_expires_at?->isFuture() || ! hash_equals($locked->password, $user->password)) {
                    throw ValidationException::withMessages(['email' => 'This invitation has already been used or has expired. Use Forgot the password to continue.']);
                }
                $locked->forceFill(['password' => Str::random(64), 'invitation_expires_at' => null, 'remember_token' => Str::random(60)])->save();
                $onboarding->start($request, $locked);
            });

            return to_route('password.code');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
