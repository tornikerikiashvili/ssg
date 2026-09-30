@extends('layouts.original-client')
@section('title', 'Roadmap')
@section('design-page', '6a9a87f77339e26eacde66a4')
@section('content')
<main class="main">
    <div class="heading-wrapper">
        <h1>Roadmap</h1>
        <div class="dashboard_info-line"><p>Track upcoming game releases, platform improvements and future product updates</p></div>
    </div>
    @if($pageBanners->isNotEmpty())
    <div class="dashboard-row">@foreach($pageBanners as $banner)<x-promo-banner :banner="$banner" />@endforeach</div>
    @elseif($upcomingItems->isNotEmpty())
    <div class="dashboard-row">
        @foreach($upcomingItems as $upcoming)
        <div class="banner">
            <p class="banner_category">Coming Next</p>
            <div class="banner_inner">
                <p class="banner_title">{{ $upcoming->game?->title ?? $upcoming->title }}</p>
                <p>{{ $upcoming->target_date?->format('d M Y') ?? $upcoming->target_quarter ?? 'Date to be announced' }}</p>
                <p>{{ $upcoming->title }}</p>
                @if($upcoming->game && $openableGameIds->contains($upcoming->game_id))<a class="button w-inline-block" href="{{ route('games.show', $upcoming->game->slug) }}">View game</a>@endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
    <div class="heading-wrapper"><h2>Roadmap Timeline</h2></div>
    <div class="games_list-small">
        @forelse($items as $item)<x-roadmap-card :item="$item" :openable-game-ids="$openableGameIds" />@empty<p>No roadmap entries available.</p>@endforelse
    </div>
    <x-pagination :records="$items" />
    <div data-collapsing class="dashboard-row is-resources">
        <div data-collapsing class="dashboard_block is-downloads is-dark">
            <div no-scrollbar class="dashboard_block-header"><span class="tab-link is-active"><p>Regional Availability</p><div class="tab-link_line"></div></span></div>
            <div data-dark-scrollbar class="downloads_list">
                @forelse($regionalGames as $regionalGame)
                <div class="availability_item">
                    @if($openableGameIds->contains($regionalGame->id))<a href="{{ route('games.show', $regionalGame->slug) }}">{{ $regionalGame->title }}</a>@else<p>{{ $regionalGame->title }}</p>@endif
                    <div class="wrapper-vertical-xs text-size-small">
                        @forelse($regionalGame->regionAvailabilities->groupBy('status') as $status => $availability)
                        <div class="tags">
                            <p class="{{ $status === 'available' ? 'text-color-blue' : 'text-color-red-orange' }}">{{ \App\Models\GameRegion::STATUSES[$status] ?? $status }}:</p>
                            @foreach($availability as $entry)<p class="tag {{ $status === 'available' ? 'is-blue' : 'is-red' }}">{{ $entry->region->name }}</p>@endforeach
                        </div>
                        @empty<p class="text-color-subtitles">No regional restrictions configured.</p>@endforelse
                    </div>
                </div>
                @empty<p>No regional rollout information available.</p>@endforelse
            </div>
        </div>
        <div class="dashboard_banner-block">
            <div class="dashboard_block">
                <div no-scrollbar class="dashboard_block-header-wrapper"><div class="dashboard_block-header"><span class="tab-link is-active"><p>Announcements</p><div class="tab-link_line"></div></span></div></div>
                <div class="notifications_list">
                    @forelse($announcements as $announcement)
                    <div class="notifications_item">
                        <div class="notifications_item-top"><p class="text-color-blue text-weight-semibold text-size-medium">{{ $announcement->title }}</p><p>{{ $announcement->created_at->diffForHumans() }}</p></div>
                        <p class="text-color-text">{{ $announcement->description }}</p>
                        <div class="notifications_item-line text-color-blue"></div>
                    </div>
                    @empty<p>No roadmap announcements available.</p>@endforelse
                </div>
            </div>
        </div>
    </div>
    @if($regionalItems->isNotEmpty())
    <h2>Roadmap per Region</h2>
    @foreach($regionalItems as $regionItems)
    <div class="dashboard_block is-dark">
        <div class="dashboard_block-header"><span class="tab-link is-active"><p>{{ $regionItems->first()->region->name }}</p><div class="tab-link_line"></div></span></div>
        <div class="games_list-small">@foreach($regionItems as $item)<x-roadmap-card :item="$item" :openable-game-ids="$openableGameIds" />@endforeach</div>
    </div>
    @endforeach
    @endif
</main>
@endsection
