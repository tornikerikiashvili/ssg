@props(['banner', 'gameCover' => false])
@php
    $blue = $banner->variant === 'blue';
    $uploadedVideo = $banner->mediaUrl('video_path');
    $artwork = $banner->mediaUrl('image_path') ?? ($uploadedVideo ? null : $banner->artworkUrl());
    $video = $uploadedVideo ?? ($banner->use_original_video ? asset('client-design/videos/banner_mp4.mp4') : null);
    $poster = $banner->mediaUrl('poster_path') ?? ($banner->use_original_video && ! $uploadedVideo ? asset('client-design/videos/banner_poster.0000000.jpg') : null);
@endphp
<div class="banner {{ $blue ? 'is-blue' : '' }}" data-cms-banner="{{ $banner->id }}">
    @if($banner->label)<p class="banner_category">{{ $banner->label }}</p>@endif
    <div class="banner_inner">
        <div>
            @if($banner->mediaUrl('logo_path'))<img src="{{ $banner->mediaUrl('logo_path') }}" loading="lazy" width="154" height="48" alt="{{ $banner->game?->title ?? $banner->title }}">@endif
            @if($banner->subtitle)<p class="jetx-subtitle">{{ $banner->subtitle }}</p>@endif
            <p class="banner_title {{ $blue ? 'is-opposite' : '' }}">{{ $banner->title }}</p>
        </div>
        @if($banner->show_game_stats && $banner->game)
        <div class="banner_info-line {{ $blue ? 'is-opposite' : '' }}">
            @if($banner->game->rtp !== null)<div><p>RTP</p><p class="text-weight-semibold">{{ $banner->game->rtp }}%</p></div>@endif
            @if($banner->game->volatility)<div><p>Volatility</p><p class="text-weight-semibold">{{ $banner->game->volatility->name }}</p></div>@endif
        </div>
        @endif
    </div>
    @if(($banner->primary_label && $banner->buttonUrl('primary')) || $banner->buttonUrl('secondary'))
    <div class="buttons z-index-2">
        @if($banner->primary_label && $banner->buttonUrl('primary'))
        <a href="{{ $banner->buttonUrl('primary') }}" class="button {{ $blue ? 'is-white' : '' }} w-inline-block">
            <p class="button_text">{{ $banner->primary_label }}</p>
            <div class="button_icon w-embed"><svg width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.14645 7.85349C8.75599 8.24395 8.75599 8.87702 9.14645 9.26749L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.463 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5606C15.2439 13.1701 15.2439 12.5369 14.8533 12.1464L10.5605 7.85349C10.17 7.46302 9.53692 7.46302 9.14645 7.85349Z" fill="#F15B4E"/></svg></div>
        </a>
        @endif
        @if($banner->buttonUrl('secondary'))
        <a href="{{ $banner->buttonUrl('secondary') }}" class="button is-secondary w-inline-block">
            <div class="button_icon w-embed"><svg width="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M5.05713 20.8364V3.16364L18.9429 12L5.05713 20.8364Z"/></svg></div>
            <p class="button_text">Play Game</p>
        </a>
        @endif
    </div>
    @endif
    @if($artwork || $video || $poster)
    <div class="banner_image-wrapper {{ $gameCover ? 'is-game-cover' : '' }}">
        @if($video)
        <video class="fullsize-img" autoplay loop muted playsinline preload="auto" @if($poster) poster="{{ $poster }}" @endif><source src="{{ $video }}"></video>
        @elseif($poster)<img src="{{ $poster }}" alt="" class="fullsize-img">@endif
        @if($artwork)<img src="{{ $artwork }}" loading="lazy" width="493" height="617" alt="{{ $banner->game?->title ?? $banner->title }}" class="fullsize-img">@endif
    </div>
    @endif
</div>
