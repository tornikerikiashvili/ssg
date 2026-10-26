@extends('layouts.client-auth')
@section('title', 'Account verification')
@section('heading', $step === 'email' ? 'Forgot your password?' : ($step === 'code' ? 'Check your email' : 'Set your new password'))
@section('description', $step === 'email' ? 'Enter your account email to receive a verification code.' : ($step === 'code' ? 'Enter the four-digit code sent to your email. It expires in 10 minutes. You have up to 5 attempts.' : 'Choose a new password with at least 12 characters.'))
@section('form')
<form method="POST" action="{{ route($step === 'email' ? 'password.email' : ($step === 'code' ? 'password.verify' : 'password.store')) }}">
    @csrf
    <div class="form_fields">
        @if(session('status'))<p class="text-color-subtitles" role="status">{{ session('status') }}</p>@endif
        @if($errors->any())<div class="text-color-red-orange" role="alert" id="password-error">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if($step === 'email')
            <div class="form_field-wrapper">
                <label for="recovery-email">Email address</label>
                <input class="form_input w-input" id="recovery-email" type="email" name="email" value="{{ old('email') }}" placeholder="your@example.com" autocomplete="email" maxlength="255" @if($errors->has('email')) aria-invalid="true" aria-describedby="password-error" @endif required autofocus>
            </div>
        @elseif($step === 'code')
            <div class="form_field-wrapper">
                <label for="verification-code">Verification code</label>
                <input class="form_input w-input" id="verification-code" type="text" name="code" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" autocomplete="one-time-code" placeholder="Enter 4-digit code" @if($errors->has('code')) aria-invalid="true" aria-describedby="password-error" @endif required autofocus>
            </div>
        @else
            <div class="form_field-wrapper">
                <label for="new-password">New password</label>
                <input class="form_input w-input" id="new-password" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif required autofocus>
            </div>
            <div class="form_field-wrapper">
                <label for="confirm-password">Repeat password</label>
                <input class="form_input w-input" id="confirm-password" type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required>
            </div>
        @endif
        <div class="stretch">
            <button class="button is-opposite w-inline-block" style="width:100%" type="submit">
                <span>{{ $step === 'email' ? 'Send code' : ($step === 'code' ? 'Verify code' : 'Save password') }}</span>
                <span class="button_icon w-embed" aria-hidden="true"><svg width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m10 8 4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
        </div>
        @if($step !== 'email')<a class="text-color-red-orange" href="{{ route('password.request') }}">Code expired or not received? Request a new code</a>@endif
        <a class="text-color-subtitles" href="{{ route('login') }}">← Back to sign in</a>
    </div>
</form>
@endsection
