<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Client Area') · SmartSoft</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html[data-client-theme="light"] { color-scheme: light; color: #202124; background: #f5f6f8; --muted: #62666d; --line: #dadddf; }
        [data-client-theme="light"] :is(.sidebar, .auth-story, .panel, .stat-grid a, .game-card, .resource-row, .field input, .filter-bar input, .filter-bar select) { background: #fff; color: #202124; }
        [data-client-theme="light"] :is(.button.secondary, .tag, .nav-link, .password-toggle) { color: #454950; }
        [data-client-theme="light"] :is(.nav-link:hover, .nav-link.active, .game-art) { background: #e9ecef; color: #202124; }
    </style>
</head>
<body>
<x-client-theme />
    @yield('body')
</body>
</html>
