@extends('layouts.base')
@section('body')
<div class="client-shell">
    <aside class="sidebar">
        <x-brand />
        <nav aria-label="Main navigation">
            @foreach([
                ['Dashboard', route('dashboard'), request()->routeIs('dashboard')],
                ['Games', route('games.index'), request()->routeIs('games.*')],
                ['Download Center', route('resources.index', 'download'), request()->is('resources/download')],
                ['Documentation', route('resources.index', 'documentation'), request()->is('resources/documentation')],
                ['Licenses & Certificates', route('resources.index', 'certificate'), request()->is('resources/certificate')],
                ['Roadmap', route('roadmap.index'), request()->routeIs('roadmap.*')],
                ['Engagement Tools', route('tools.index'), request()->routeIs('tools.*')],
                ['Updates', route('updates.index'), request()->routeIs('updates.*')],
            ] as [$label, $url, $active])
                <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ $url }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="sidebar-note">SmartSoft Gaming<br>Partner client area</div>
    </aside>
    <div class="client-main">
        <header class="topbar">
            <p>{{ auth()->user()->company?->name ?? 'SmartSoft team' }}</p>
            <div class="topbar-actions">
                @if(auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                    <a class="button secondary" href="{{ url('/admin') }}">Administration ↗</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="button secondary">Sign out</button></form>
            </div>
        </header>
        <main class="content">@yield('content')</main>
    </div>
</div>
@endsection
