<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('client.account', ['user' => $request->user()]);
    }

    public function contact(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
        $data = $request->validateWithBag('contact', [
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'current_password' => [Rule::requiredIf($request->input('email') !== $user->email), 'nullable', 'current_password:web'],
        ]);

        if ($data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->save();

        return to_route('account.show')->with('status', 'Contact information saved.');
    }

    public function loginDetails(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('loginDetails', [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:12', 'max:256', 'confirmed'],
            'current_password' => ['required_with:password', 'nullable', 'current_password:web'],
        ]);
        $user = $request->user();
        $user->name = $data['name'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->remember_token = Str::random(60);
        }
        $user->save();

        if (! empty($data['password'])) {
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
            }
            $request->session()->regenerate();
        }

        return to_route('account.show')->with('status', 'Login details saved.');
    }
}
