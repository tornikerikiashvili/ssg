<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessClientArea()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your access is no longer available. Please contact your SmartSoft representative.',
            ]);
        }

        if ($request->user()->requires_password_setup) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('password.request')->withErrors(['email' => 'Please finish setting your password before accessing the portal.']);
        }

        return $next($request);
    }
}
