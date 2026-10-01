@if (filament()->auth()->check())
    <form method="POST" action="{{ filament()->getLogoutUrl() }}" data-admin-sign-out>
        @csrf
        <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-right-start-on-rectangle">
            Sign out
        </x-filament::button>
    </form>
@endif
