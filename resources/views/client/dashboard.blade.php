@extends('layouts.original-client')
@section('title', 'Dashboard')
@section('content')
      <main class="main">
        <div class="heading-wrapper">
          <h1>Welcome, {{ auth()->user()->name }}</h1>
          <div class="dashboard_info-line">
            <div class="dashboard_info-line-left">
              <p>{{ auth()->user()->company?->name ?? 'SmartSoft' }}</p>
              <div class="red-dot is-s"></div>
              <p>Partner account</p>
            </div>
            <p>{{ now()->format('l, M d, Y') }}</p>
          </div>
        </div>
        <div class="numbers">
@foreach([
    [$gameCount, 'Games Available', $newGameCount.' added in 7 days'],
    [$newAssetCount, 'New Assets', $assetCount.' total available'],
    [$downloadCount, 'Your Downloads', $weeklyDownloadCount.' started in 7 days'],
    [$featuredCount, 'Featured Games', $newFeaturedCount.' added in 7 days'],
] as [$count, $label, $detail])
<div class="numbers_item"><p class="number">{{ $count }}</p><p class="text-weight-semibold">{{ $label }}</p><p class="numbers_ghange">{{ $detail }}</p></div>
@endforeach
</div>
        <div class="dashboard-row">
          @if($pageBanners->isNotEmpty())<x-promo-banner :banner="$pageBanners->first()" />@else
@if($featuredGame)<div class="banner">
            <p class="banner_category">Featured Game</p>
            <div class="banner_inner">
              <div>
                <p class="jetx-subtitle">{{ $featuredGame->category }}{{ $featuredGame->is_demo ? ' · Demo' : '' }}</p>
                <p class="banner_title">{{ $featuredGame->title }}</p>
              </div>
              <div class="banner_info-line">
                <div>
                  <p>RTP</p>
                  <p class="text-weight-semibold">{{ $featuredGame->rtp !== null ? $featuredGame->rtp.'%' : 'Not specified' }}</p>
                </div>
                <div>
                  <p>Release date</p>
                  <p class="text-weight-semibold">{{ $featuredGame->release_date?->format('d M Y') ?? 'To be announced' }}</p>
                </div>
              </div>
            </div>
            <div class="buttons z-index-2">
              <a href="{{ route('games.show', $featuredGame->slug) }}" class="button w-inline-block">
                <p class="button_text">Discover</p>
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.14645 7.85349C8.75599 8.24395 8.75599 8.87702 9.14645 9.26749L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.463 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5606C15.2439 13.1701 15.2439 12.5369 14.8533 12.1464L10.5605 7.85349C10.17 7.46302 9.53692 7.46302 9.14645 7.85349Z" fill="#F15B4E"></path>
                  </svg>
                </div>
              </a>
              <a href="{{ route('games.show', $featuredGame->slug) }}#resources" class="button is-secondary w-inline-block">
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.05713 20.8364V3.16364L18.9429 12L5.05713 20.8364Z"></path>
                  </svg>
                </div>
                <p class="button_text">Game Assets</p>
              </a>
            </div>
            <div class="banner_image-wrapper">@if($featuredGame->cover_image_url)<img src="{{ $featuredGame->cover_image_url }}" alt="{{ $featuredGame->title }}" class="fullsize-img">@endif</div>
          </div>@else<div class="banner"><p class="banner_category">Featured Game</p><p class="banner_title">No featured games yet</p></div>@endif
          @endif
          <div class="dashboard_banner-block">
            <div data-tabs="" class="dashboard_block">
              <div no-scrollbar="" class="dashboard_block-header-wrapper">
                <div class="dashboard_block-header">
                  <a href="#" class="tab-link is-active w-inline-block">
                    <p>Notifications</p>
                    <div class="tab-link_line"></div>
                  </a>
                  <a href="#" class="tab-link w-inline-block">
                    <p>Announcements</p>
                    <div class="tab-link_line"></div>
                  </a>
                </div>
              </div>
              <div>
                <div data-tab-pane="">
                  <div class="notifications_list">@forelse($notifications as $notification)
                    <x-notification-card :notification="$notification" />
@empty<p class="text-color-subtitles">No notifications available.</p>@endforelse</div>
                </div>
                <div data-tab-pane="">
                  <div class="notifications_list">@forelse($announcements as $announcement)
                    <div class="notifications_item">
                      <div class="notifications_item-top">
                        <p class="{{ $announcement->notificationColorClass() }} text-weight-semibold text-size-medium">{{ $announcement->title }}</p>
                        <p>{{ $announcement->created_at->diffForHumans() }}</p>
                      </div>
                      @if(filled($announcement->teaser))<p class="text-color-text">{{ $announcement->teaser }}</p>@endif
                      <p>{{ \Illuminate\Support\Str::limit($announcement->description, 160) }}</p>
                      <div class="notifications_item-line {{ $announcement->notificationColorClass() }}"></div>
                    </div>
@empty<p class="text-color-subtitles">No announcements available.</p>@endforelse</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="dashboard-row is-3-col">
          <div class="dashboard_tranding-block">
            <div class="dashboard_block">
              <div no-scrollbar="" class="dashboard_block-header">
                <a href="#" class="tab-link is-active w-inline-block">
                  <p>Recently added</p>
                  <div class="tab-link_line"></div>
                </a>
              </div>
              <div class="tranding_list">@forelse($recentGames as $game)<div class="tranding_item">
                  <p class="tranding_number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                  <div class="tranding_item-inner">
                    <p class="text-weight-semibold">{{ $game->title }}</p>
                    <p class="text-size-small">{{ $game->category }}</p>
                  </div>
                  <div class="tranding_item-right">
                    <p class="text-weight-semibold text-color-red-orange">{{ $game->rtp !== null ? $game->rtp.' %' : '—' }}</p>
                    <p class="text-size-small">RTP</p>
                  </div>
                </div>@empty<p>No games available.</p>@endforelse</div>
            </div>
          </div>
          <div class="dashboard_block">
            <div no-scrollbar="" class="dashboard_block-header">
              <a href="#" class="tab-link is-active w-inline-block">
                <p>My Games</p>
                <div class="tab-link_line"></div>
              </a>
              <a href="{{ route('games.index') }}" class="tab-link is-right w-inline-block">
                <p>See all</p>
                <div class="tab-link_line"></div>
              </a>
            </div>
            <p class="text-color-subtitles">Total {{ $gameCount }} Games.</p>
            <div class="dashboard_games-list">@forelse($games as $game)<div class="dashboard_games-item"><img src="{{ $game->cover_image_url ?: asset('client-design/images/Logo-icon.svg') }}" loading="lazy" width="70" height="90" alt="{{ $game->title }}" class="dashboard_games-image">
                <div class="dashboard_games-item-inner">
                  <div>
                    <p class="text-size-large">{{ $game->title }}</p>
                    <p class="text-size-small">{{ $game->category }}{{ $game->is_demo ? ' · Demo' : '' }}</p>
                  </div>
                  <div class="buttons is-gap-s">
                    <a href="{{ route('games.show', $game->slug) }}" class="card-button w-inline-block">
                      <p class="button_text">Open</p>
                      <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M9.14645 7.85349C8.75599 8.24395 8.75599 8.87702 9.14645 9.26749L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.463 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5606C15.2439 13.1701 15.2439 12.5369 14.8533 12.1464L10.5605 7.85349C10.17 7.46302 9.53692 7.46302 9.14645 7.85349Z" fill="#F15B4E"></path>
                        </svg>
                      </div>
                    </a>
                    <a href="{{ route('games.show', $game->slug) }}#resources" class="card-button w-inline-block">
                      <p class="button_text">Assets</p>
                      <div class="button_icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path fill-rule="evenodd" clip-rule="evenodd" d="M7.28851 8.62951C6.86291 8.95363 6.64928 9.53626 6.86978 10.1139C7.12731 10.7886 7.63324 11.4086 8.19911 11.8883C8.77126 12.3733 9.48722 12.7854 10.2481 12.9756C10.4135 13.017 10.5865 13.017 10.752 12.9756C11.5128 12.7854 12.2287 12.3733 12.8009 11.8883C13.3668 11.4086 13.8727 10.7886 14.1302 10.1139C14.3507 9.53626 14.1371 8.95363 13.7115 8.62951C13.3276 8.33719 12.8216 8.27984 12.3829 8.4483C12.1379 8.54243 11.8312 8.64463 11.5003 8.72256V4.52527C11.5003 3.97298 11.0526 3.52527 10.5003 3.52527C9.94806 3.52527 9.50035 3.97298 9.50035 4.52527V8.72273C9.16925 8.64477 8.86228 8.54249 8.61705 8.4483C8.17844 8.27983 7.67236 8.33719 7.28851 8.62951ZM5.49902 13.9731C5.49902 13.4207 5.05131 12.973 4.49902 12.973C3.94674 12.973 3.49902 13.4207 3.49902 13.9731V14.8069C3.49902 15.5492 3.82639 16.2377 4.36955 16.7283C4.90934 17.2159 5.62154 17.4747 6.34547 17.4747H14.6545C15.3784 17.4747 16.0906 17.2159 16.6304 16.7283C17.1736 16.2377 17.5009 15.5492 17.5009 14.8069V13.9731C17.5009 13.4207 17.0532 12.973 16.5009 12.973C15.9486 12.973 15.5009 13.4207 15.5009 13.9731V14.8069C15.5009 14.9493 15.4392 15.1092 15.2898 15.2441C15.1371 15.3821 14.91 15.4747 14.6545 15.4747H6.34547C6.08998 15.4747 5.86288 15.3821 5.71012 15.2441C5.56073 15.1092 5.49902 14.9493 5.49902 14.8069V13.9731Z"></path>
                        </svg>
                      </div>
                    </a>
                  </div>
                </div>
              </div>@empty<p class="text-color-subtitles">No games available.</p>@endforelse</div>
          </div>
          <div class="dashboard_block is-highlighted">
            <div no-scrollbar="" class="dashboard_block-header">
              <a href="#" class="tab-link is-active w-inline-block">
                <p>Featured for your company</p>
                <div class="tab-link_line"></div>
              </a>
              <a href="{{ route('games.index') }}" class="tab-link is-right w-inline-block">
                <p>See all</p>
                <div class="tab-link_line"></div>
              </a>
            </div>
            <p class="text-color-subtitles">Total {{ $featuredCount }} Featured.</p>
            <div class="dashboard_games-list">@forelse($featuredGames as $game)<div class="dashboard_games-item"><img src="{{ $game->cover_image_url ?: asset('client-design/images/Logo-icon.svg') }}" loading="lazy" width="70" height="90" alt="{{ $game->title }}" class="dashboard_games-image">
                <div class="dashboard_games-item-inner">
                  <div>
                    <p class="text-size-large">{{ $game->title }}</p>
                    <p class="text-size-small">{{ $game->category }}{{ $game->is_demo ? ' · Demo' : '' }}</p>
                  </div>
                  <div class="buttons is-gap-s">
                    <a href="{{ route('games.show', $game->slug) }}" class="card-button w-inline-block">
                      <p class="button_text">Open</p>
                      <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M9.14645 7.85349C8.75599 8.24395 8.75599 8.87702 9.14645 9.26749L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.463 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5606C15.2439 13.1701 15.2439 12.5369 14.8533 12.1464L10.5605 7.85349C10.17 7.46302 9.53692 7.46302 9.14645 7.85349Z" fill="#F15B4E"></path>
                        </svg>
                      </div>
                    </a>
                  </div>
                </div>
              </div>@empty<p class="text-color-subtitles">No games available.</p>@endforelse</div>
          </div>
        </div>
        <div class="heading-wrapper">
          <h2>My Resources</h2>
          <p class="text-color-subtitles">Access your resources and recently requested downloads in one place.</p>
        </div>
        <div data-collapsing="" class="dashboard-row is-resources">
          <div data-collapsing="" class="dashboard_block is-downloads">
            <div no-scrollbar="" class="dashboard_block-header">
              <a href="#" class="tab-link is-active w-inline-block">
                <p>Your Recent Downloads</p>
                <div class="tab-link_line"></div>
              </a>
              <a href="{{ route('resources.index', 'download') }}" class="tab-link is-right w-inline-block">
                <p>See all</p>
                <div class="tab-link_line"></div>
              </a>
            </div>
            <x-recent-downloads :downloads="$recentDownloads" />
          </div>
          <div class="quick-actions_wrapper">
            <div class="dashboard_block is-dark">
              <div no-scrollbar="" class="dashboard_block-header">
                <a href="#" class="tab-link is-active w-inline-block">
                  <p>Quick Actions</p>
                  <div class="tab-link_line"></div>
                </a>
              </div>
              <div class="quick-actions_list">
                <a href="{{ route('games.index') }}" class="quick-actions_item w-inline-block">
                  <div class="quick-actions_icon-wrapper">
                    <div class="icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7754 3.70295C12.7026 3.50551 12.6062 3.32269 12.4904 3.15674C12.5514 3.17201 12.6123 3.18779 12.6732 3.2041C13.6506 3.46601 14.5696 3.8534 15.457 4.22749C15.5775 4.27833 15.698 4.32912 15.8176 4.37912C16.2889 4.57635 16.5661 5.07468 16.4795 5.57826C16.449 5.75621 16.4187 5.93538 16.3881 6.11558C16.1942 7.26035 15.9932 8.44717 15.6754 9.63344C15.6335 9.78976 15.5898 9.94507 15.5447 10.0994C15.2552 10.0777 14.9574 10.0626 14.654 10.0595C14.5151 9.3017 14.3534 8.5166 14.1433 7.73252C13.8079 6.48101 13.3646 5.28882 12.9612 4.20385C12.8981 4.03429 12.8359 3.86709 12.7754 3.70295ZM5.37765 14.352C5.55446 14.8314 6.04373 15.1243 6.55056 15.0594C6.67912 15.043 6.80843 15.0268 6.93836 15.0104C7.89392 14.8908 8.88372 14.7667 9.86115 14.5049C9.93129 14.486 10.0011 14.4666 10.0707 14.4466C10.075 13.8107 10.1303 13.1981 10.1908 12.6318C10.328 11.3495 11.3514 10.3285 12.6284 10.1862C12.8509 10.1614 13.0809 10.1373 13.3171 10.1168C13.1906 9.44161 13.0463 8.75848 12.8633 8.0755C12.5455 6.88921 12.1262 5.761 11.7217 4.67265C11.658 4.5013 11.5947 4.33094 11.5322 4.1615C11.3554 3.6821 10.8661 3.38915 10.3593 3.45403C10.2306 3.4705 10.1013 3.48671 9.97124 3.503C9.01567 3.62273 8.02613 3.74671 7.0487 4.00861C6.07126 4.27052 5.15231 4.65791 4.26491 5.03201C4.14416 5.08292 4.024 5.13357 3.90435 5.18363C3.43298 5.38085 3.15574 5.87919 3.24231 6.38276C3.27291 6.56075 3.30326 6.73993 3.33378 6.92016C3.52766 8.06495 3.72865 9.25167 4.04651 10.438C4.36437 11.6242 4.78369 12.7525 5.18817 13.8408C5.25179 14.012 5.31521 14.1827 5.37765 14.352ZM8.06131 6.9392C7.99664 6.89231 7.91428 6.87707 7.83713 6.89775C7.75997 6.91843 7.69626 6.9728 7.6637 7.04573L6.53016 9.58537C6.47944 9.69898 6.51459 9.83257 6.61465 9.90653L8.87668 11.5785C8.9416 11.6265 9.02484 11.6423 9.10282 11.6214C9.18079 11.6005 9.24499 11.5452 9.27722 11.4712L10.4002 8.89219C10.4499 8.77812 10.4135 8.64485 10.3128 8.57181L8.06131 6.9392ZM12.7459 17.8136C11.9608 17.726 11.329 17.0943 11.245 16.3088C11.1829 15.7289 11.1305 15.1336 11.1305 14.5267C11.1305 13.9198 11.1829 13.3244 11.245 12.7446C11.329 11.9591 11.9608 11.3273 12.7459 11.2398C13.329 11.1748 13.9277 11.119 14.5383 11.119C15.1487 11.119 15.7475 11.1748 16.3306 11.2398C17.1157 11.3273 17.7474 11.9591 17.8315 12.7446C17.8935 13.3244 17.9459 13.9198 17.9459 14.5267C17.9459 15.1336 17.8935 15.7289 17.8315 16.3088C17.7474 17.0943 17.1157 17.726 16.3306 17.8136C15.7475 17.8786 15.1487 17.9343 14.5383 17.9343C13.9277 17.9343 13.329 17.8786 12.7459 17.8136ZM13.842 15.2338C13.842 14.941 13.6047 14.7037 13.3119 14.7037C13.0192 14.7037 12.7819 14.941 12.7819 15.2338V15.7639C12.7819 16.0566 13.0192 16.294 13.3119 16.294C13.6047 16.294 13.842 16.0566 13.842 15.7639V15.2338ZM15.7128 12.5192C16.0055 12.5192 16.2429 12.7566 16.2429 13.0493V13.5794C16.2429 13.8722 16.0055 14.1094 15.7128 14.1094C15.42 14.1094 15.1827 13.8722 15.1827 13.5794V13.0493C15.1827 12.7566 15.42 12.5192 15.7128 12.5192Z"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="grow">Games</p>
                  <p class="quantity-circle">{{ $gameCount }}</p>
                </a>
                <a href="{{ route('resources.index', 'download') }}" class="quick-actions_item w-inline-block">
                  <div class="quick-actions_icon-wrapper">
                    <div class="icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.28851 8.62963C6.86291 8.95375 6.64928 9.53638 6.86978 10.1141C7.12731 10.7887 7.63324 11.4087 8.19911 11.8884C8.77126 12.3734 9.48722 12.7856 10.2481 12.9758C10.4135 13.0171 10.5865 13.0171 10.752 12.9758C11.5128 12.7856 12.2287 12.3734 12.8009 11.8884C13.3668 11.4087 13.8727 10.7887 14.1302 10.1141C14.3507 9.53638 14.1371 8.95375 13.7115 8.62963C13.3276 8.33731 12.8216 8.27996 12.3829 8.44842C12.1379 8.54255 11.8312 8.64475 11.5003 8.72268V4.52539C11.5003 3.97311 11.0526 3.52539 10.5003 3.52539C9.94806 3.52539 9.50035 3.97311 9.50035 4.52539V8.72285C9.16925 8.64489 8.86228 8.54261 8.61705 8.44842C8.17844 8.27995 7.67236 8.33731 7.28851 8.62963ZM5.49902 13.9732C5.49902 13.4209 5.05131 12.9732 4.49902 12.9732C3.94674 12.9732 3.49902 13.4209 3.49902 13.9732V14.807C3.49902 15.5493 3.82639 16.2378 4.36955 16.7284C4.90934 17.216 5.62154 17.4748 6.34547 17.4748H14.6545C15.3784 17.4748 16.0906 17.216 16.6304 16.7284C17.1736 16.2378 17.5009 15.5493 17.5009 14.807V13.9732C17.5009 13.4209 17.0532 12.9732 16.5009 12.9732C15.9486 12.9732 15.5009 13.4209 15.5009 13.9732V14.807C15.5009 14.9494 15.4392 15.1093 15.2898 15.2442C15.1371 15.3822 14.91 15.4748 14.6545 15.4748H6.34547C6.08998 15.4748 5.86288 15.3822 5.71012 15.2442C5.56073 15.1093 5.49902 14.9494 5.49902 14.807V13.9732Z"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="grow">Downloads</p>
                </a>
                <a href="{{ route('resources.index', 'certificate') }}" class="quick-actions_item w-inline-block">
                  <div class="quick-actions_icon-wrapper">
                    <div class="icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.52748 4.20282C10.0813 3.72708 10.8988 3.72742 11.4523 4.2032C11.6491 4.37233 11.8433 4.54161 12.0351 4.7113C12.2903 4.65934 12.5472 4.60902 12.8064 4.56023C13.5239 4.42515 14.2316 4.83415 14.4731 5.52298C14.559 5.76784 14.6425 6.01152 14.7238 6.25437C14.9707 6.33697 15.2184 6.42188 15.4672 6.50919C16.1562 6.75097 16.5646 7.45904 16.4293 8.17634C16.3802 8.43705 16.3295 8.69547 16.2772 8.95197C16.4508 9.14804 16.624 9.34658 16.797 9.54787C17.2728 10.1014 17.2731 10.9189 16.7974 11.4727C16.6255 11.6728 16.4534 11.8702 16.2808 12.0653C16.3318 12.3162 16.3813 12.569 16.4294 12.8239C16.5647 13.5412 16.1563 14.2493 15.4673 14.4911C15.2185 14.5784 14.9707 14.6634 14.7236 14.746C14.6424 14.9888 14.5589 15.2324 14.4731 15.4773C14.2315 16.1661 13.5238 16.5751 12.8063 16.44C12.5529 16.3923 12.3016 16.3432 12.0521 16.2924C11.8613 16.4612 11.6683 16.6294 11.4727 16.7974C10.9189 17.2731 10.1014 17.2728 9.54786 16.797C9.3511 16.6279 9.15696 16.4586 8.96519 16.2889C8.70995 16.3409 8.4529 16.3912 8.19371 16.44C7.4762 16.5751 6.76846 16.1661 6.52694 15.4773C6.4411 15.2324 6.3576 14.9888 6.27635 14.746C6.02931 14.6633 5.78153 14.5784 5.53267 14.4911C4.84374 14.2493 4.43532 13.5412 4.57058 12.8239C4.61976 12.5631 4.67045 12.3046 4.72274 12.048C4.54922 11.852 4.37614 11.6535 4.2032 11.4523C3.72742 10.8988 3.72708 10.0813 4.20282 9.52748C4.37464 9.32745 4.54672 9.13005 4.71932 8.93503C4.66826 8.68409 4.61874 8.43132 4.57066 8.17634C4.4354 7.45905 4.84383 6.75097 5.53275 6.50919C5.78155 6.42188 6.02927 6.33697 6.27625 6.25437C6.3575 6.01152 6.44101 5.76784 6.52686 5.52298C6.76837 4.83416 7.47612 4.42515 8.19363 4.56023C8.44702 4.60794 8.69837 4.65711 8.94799 4.70785C9.1388 4.5391 9.33188 4.37083 9.52748 4.20282ZM13.0039 9.0555C13.3107 8.77719 13.3338 8.30288 13.0555 7.99609C12.7772 7.6893 12.3029 7.6662 11.9961 7.9445C11.2606 8.61173 10.7001 9.21857 10.2257 9.95909C9.92864 10.4227 9.67341 10.9261 9.42922 11.5109L8.65805 10.7156C8.3697 10.4183 7.89488 10.4109 7.59752 10.6993C7.30016 10.9877 7.29285 11.4625 7.5812 11.7598L9.17407 13.4025C9.35522 13.5893 9.62025 13.6689 9.87434 13.6127C10.1284 13.5565 10.3353 13.3727 10.4208 13.127C10.7865 12.0764 11.1072 11.3637 11.4887 10.7683C11.8672 10.1775 12.3257 9.67072 13.0039 9.0555Z"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="grow">licenses and Certificates</p>
                </a>
                <a href="{{ route('resources.index', 'documentation') }}" class="quick-actions_item w-inline-block">
                  <div class="quick-actions_icon-wrapper">
                    <div class="icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.5 4C9.13176 4 8.02572 4.04125 6.88141 4.11993C5.66848 4.20332 4.70348 5.16952 4.6272 6.38484C4.54381 7.71358 4.5 9.09 4.5 10.5C4.5 11.91 4.54381 13.2864 4.62721 14.6152C4.70348 15.8305 5.66848 16.7967 6.88141 16.8801C8.02572 16.9587 9.13176 17 10.5 17C11.8682 17 12.9743 16.9587 14.1186 16.8801C15.3315 16.7967 16.2965 15.8305 16.3728 14.6152C16.4562 13.2864 16.5 11.91 16.5 10.5C16.5 9.92893 16.4928 9.36338 16.4787 8.80429C16.4664 8.31496 16.3076 7.83676 16.015 7.43803C15.1113 6.2069 14.3786 5.42705 13.1826 4.50394C12.7777 4.19143 12.2842 4.02436 11.7806 4.01336C11.3749 4.00449 10.9516 4 10.5 4ZM7.96875 10.5156C7.96875 10.1705 8.24857 9.89062 8.59375 9.89062H12.4062C12.7514 9.89062 13.0312 10.1705 13.0312 10.5156C13.0312 10.8608 12.7514 11.1406 12.4062 11.1406H8.59375C8.24857 11.1406 7.96875 10.8608 7.96875 10.5156ZM8.59375 6.875C8.24857 6.875 7.96875 7.15482 7.96875 7.5C7.96875 7.84518 8.24857 8.125 8.59375 8.125H10.8944C11.2396 8.125 11.5194 7.84518 11.5194 7.5C11.5194 7.15482 11.2396 6.875 10.8944 6.875H8.59375ZM8.59375 12.7969C8.24857 12.7969 7.96875 13.0767 7.96875 13.4219C7.96875 13.767 8.24857 14.0468 8.59375 14.0468H12.3944C12.7396 14.0468 13.0194 13.767 13.0194 13.4219C13.0194 13.0767 12.7396 12.7969 12.3944 12.7969H8.59375Z"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="grow">Documentations</p>
                  <p class="quantity-circle">{{ $documentationCount }}</p>
                </a>
              </div>
            </div>
            <div class="dashboard_block is-dark">
              <div class="quick-actions_list is-3-col">
                <a href="#" class="quick-actions_item w-inline-block">
                  <div class="quick-actions_icon-wrapper">
                    <div class="icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.52145 5.52145C6.70296 4.33993 8.40797 3.75 10.5 3.75C12.592 3.75 14.297 4.33993 15.4786 5.52145C16.6601 6.70296 17.25 8.40797 17.25 10.5C17.25 12.592 16.6601 14.297 15.4786 15.4786C14.297 16.6601 12.592 17.25 10.5 17.25C8.40797 17.25 6.70296 16.6601 5.52145 15.4786C4.33993 14.297 3.75 12.592 3.75 10.5C3.75 8.40797 4.33993 6.70296 5.52145 5.52145ZM6.59033 6.59033C5.74885 7.43181 5.25 8.71805 5.25 10.5C5.25 11.5446 5.42141 12.4188 5.73277 13.1329H6.69894C7.14134 13.1329 7.56561 12.9572 7.87843 12.6444C8.19125 12.3315 8.36699 11.9073 8.36699 11.4649V10.0351C8.36699 9.59273 8.54274 9.16845 8.85556 8.85563C9.16838 8.54281 9.59265 8.36707 10.035 8.36707C10.4774 8.36707 10.9017 8.19133 11.2145 7.87851C11.5274 7.56569 11.7031 7.14142 11.7031 6.69902V5.3348C11.3304 5.27899 10.9296 5.25 10.5 5.25C8.71805 5.25 7.43181 5.74885 6.59033 6.59033ZM15.75 10.5C15.75 10.4609 15.7498 10.422 15.7493 10.3833C15.4725 10.3099 15.1866 10.2716 14.8983 10.27H12.868C12.4256 10.27 12.0013 10.4457 11.6885 10.7586C11.3757 11.0714 11.2 11.4956 11.2 11.938C11.2 12.3804 11.3757 12.8047 11.6885 13.1175C12.0013 13.4304 12.4256 13.6061 12.868 13.6061C13.184 13.6061 13.4871 13.7316 13.7105 13.9551C13.916 14.1605 14.0386 14.4333 14.057 14.7215C14.1822 14.6237 14.2998 14.5196 14.4097 14.4097C15.2511 13.5682 15.75 12.2819 15.75 10.5Z"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="grow">Smartsoft.com</p>
                </a>
                <a href="#" class="quick-actions_item w-inline-block">
                  <div class="quick-actions_icon-wrapper">
                    <div class="icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5 10.5C5 8.63805 5.52135 7.28931 6.40533 6.40533C7.28931 5.52135 8.63805 5 10.5 5C12.3619 5 13.7107 5.52135 14.5947 6.40533C15.4786 7.28931 16 8.63805 16 10.5C16 11.1747 15.8739 11.7611 15.6647 12.1494C15.4674 12.5157 15.2479 12.6351 15 12.6351C14.6201 12.6351 14.4539 12.5453 14.3802 12.4858C14.3044 12.4247 14.234 12.3211 14.1849 12.1228C14.1338 11.9167 14.1193 11.6631 14.1222 11.3638C14.1231 11.2795 14.1259 11.1792 14.1289 11.0735C14.1346 10.8738 14.1408 10.6545 14.135 10.4856C14.1323 9.43027 13.8312 8.50065 13.1652 7.83467C12.4962 7.16568 11.5612 6.86487 10.4999 6.86487C9.43858 6.86487 8.50354 7.16568 7.83455 7.83467C7.16556 8.50367 6.86475 9.43871 6.86475 10.5C6.86475 11.5613 7.16556 12.4963 7.83455 13.1653C8.50354 13.8343 9.43858 14.1351 10.4999 14.1351C11.5114 14.1351 12.4082 13.8619 13.0693 13.257C13.1682 13.3988 13.2895 13.5332 13.4384 13.6533C13.849 13.9845 14.3799 14.1351 15 14.1351C15.9521 14.1351 16.6076 13.562 16.9853 12.8607C17.3511 12.1815 17.5 11.3253 17.5 10.5C17.5 8.36195 16.8964 6.58569 15.6553 5.34467C14.4143 4.10365 12.6381 3.5 10.5 3.5C8.36195 3.5 6.58569 4.10365 5.34467 5.34467C4.10365 6.58569 3.5 8.36195 3.5 10.5C3.5 12.6381 4.10365 14.4143 5.34467 15.6553C6.58569 16.8964 8.36195 17.5 10.5 17.5C11.4033 17.5 12.2407 17.3928 13.0017 17.1747C13.3999 17.0606 13.6302 16.6454 13.5161 16.2472C13.402 15.849 12.9867 15.6187 12.5885 15.7327C11.9818 15.9066 11.286 16 10.5 16C8.63805 16 7.28931 15.4786 6.40533 14.5947C5.52135 13.7107 5 12.3619 5 10.5ZM12.635 10.4949C12.635 10.4977 12.635 10.5005 12.635 10.5033C12.6344 11.2866 12.416 11.7932 12.1046 12.1047C11.7926 12.4166 11.2851 12.6351 10.4999 12.6351C9.71469 12.6351 9.20716 12.4166 8.89521 12.1047C8.58326 11.7927 8.36475 11.2852 8.36475 10.5C8.36475 9.71481 8.58326 9.20728 8.89521 8.89533C9.20716 8.58338 9.71469 8.36487 10.4999 8.36487C11.2851 8.36487 11.7926 8.58338 12.1046 8.89533C12.4158 9.2066 12.6341 9.7126 12.635 10.4949Z"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="grow">Send as a mail</p>
                </a>
                <a href="#" class="quick-actions_item w-inline-block">
                  <div class="quick-actions_icon-wrapper">
                    <div class="icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.24355 4.54541C9.34778 4.25017 9.54097 3.99451 9.79654 3.81363C10.0521 3.63274 10.3575 3.53554 10.6705 3.53541H11.2865C11.6296 3.53421 11.9626 3.65142 12.2292 3.86727C12.4959 4.08311 12.6799 4.38436 12.7502 4.72014C12.8205 5.05593 12.7727 5.40568 12.615 5.71035C12.4573 6.01501 12.1993 6.25593 11.8846 6.39241L10.5645 6.96941C10.5088 6.99396 10.4632 7.03707 10.4355 7.09141H12.1525C12.3183 7.09141 12.4773 7.15726 12.5945 7.27447C12.7117 7.39168 12.7775 7.55065 12.7775 7.71641C12.7775 7.88217 12.7117 8.04114 12.5945 8.15835C12.4773 8.27556 12.3183 8.34141 12.1525 8.34141H9.78155C9.69939 8.34154 9.61801 8.32547 9.54207 8.29412C9.46612 8.26277 9.3971 8.21675 9.33896 8.15871C9.28082 8.10066 9.23469 8.03171 9.20322 7.95582C9.17175 7.87992 9.15555 7.79857 9.15555 7.71641V7.21141C9.15555 6.60941 9.51155 6.06541 10.0625 5.82441L11.3825 5.24741C11.4345 5.22613 11.4773 5.18733 11.5036 5.13775C11.5299 5.08816 11.5379 5.03094 11.5264 4.97602C11.5149 4.9211 11.4845 4.87196 11.4405 4.83713C11.3965 4.80231 11.3416 4.78401 11.2855 4.78541H10.6695C10.6149 4.78539 10.5617 4.8023 10.5171 4.83383C10.4725 4.86535 10.4388 4.90992 10.4205 4.96141C10.3953 5.04136 10.3542 5.11541 10.2998 5.17916C10.2453 5.2429 10.1786 5.29504 10.1036 5.33247C10.0285 5.36989 9.94674 5.39184 9.86305 5.397C9.77937 5.40217 9.6955 5.39044 9.61645 5.36252C9.53739 5.3346 9.46476 5.29106 9.40288 5.23449C9.34099 5.17793 9.29112 5.10949 9.25624 5.03325C9.22135 4.95701 9.20216 4.87453 9.19981 4.79072C9.19746 4.70691 9.21299 4.62349 9.24355 4.54541ZM15.3465 3.81841C15.4577 3.6918 15.6048 3.60206 15.7682 3.56114C15.9316 3.52023 16.1036 3.53007 16.2613 3.58937C16.419 3.64866 16.5549 3.7546 16.6509 3.89306C16.7469 4.03152 16.7984 4.19594 16.7985 4.36441V6.20341C16.9579 6.21244 17.1078 6.28211 17.2175 6.39814C17.3271 6.51418 17.3882 6.66777 17.3882 6.82741C17.3882 6.98705 17.3271 7.14064 17.2175 7.25667C17.1078 7.37271 16.9579 7.44238 16.7985 7.45141V7.71641C16.7985 7.88217 16.7327 8.04114 16.6155 8.15835C16.4983 8.27556 16.3393 8.34141 16.1735 8.34141C16.0078 8.34141 15.8488 8.27556 15.7316 8.15835C15.6144 8.04114 15.5485 7.88217 15.5485 7.71641V7.45241H14.0185C13.857 7.45252 13.6989 7.40606 13.5631 7.3186C13.4273 7.23113 13.3196 7.10637 13.2529 6.95926C13.1862 6.81215 13.1634 6.64893 13.1871 6.48915C13.2108 6.32938 13.28 6.17982 13.3865 6.05841L15.3465 3.81841ZM14.9215 6.20241L15.5485 5.48541V6.20241H14.9215ZM4.60355 8.26241C5.16955 7.49041 6.22855 7.38041 6.95055 8.00741C7.27055 8.28541 7.58355 8.57541 7.89055 8.86341C8.31055 9.25641 8.35655 9.93341 7.98055 10.3694C7.93755 10.4194 7.78655 10.5774 7.64355 10.7254L7.41355 10.9664C8.13255 12.1754 8.87855 12.9304 10.1095 13.6614C10.1665 13.6084 10.2565 13.5214 10.3495 13.4314C10.4965 13.2914 10.6525 13.1414 10.7015 13.0994C11.1375 12.7224 11.8175 12.7684 12.2146 13.1864C12.5205 13.5084 12.8315 13.8364 13.1245 14.1764C13.7255 14.8764 13.6025 15.9004 12.8545 16.4434C12.7235 16.5394 12.5655 16.6564 12.3615 16.8204C11.8341 17.2385 11.1807 17.4655 10.5077 17.4646C9.83468 17.4637 9.18183 17.2349 8.65555 16.8154C7.03681 15.5127 5.56329 14.0391 4.26055 12.4204C3.84108 11.8941 3.61224 11.2413 3.61133 10.5683C3.61042 9.89528 3.8375 9.24182 4.25555 8.71441C4.40155 8.53141 4.51255 8.38541 4.60355 8.26241Z"></path>
                      </svg>
                    </div>
                  </div>
                  <p class="grow">Support</p>
                </a>
              </div>
            </div>
          </div>
        </div>
      </main>
@endsection
