@extends('layouts.original-client')
@section('title', 'Roadmap')
@section('design-page', '6a9a87f77339e26eacde66a4')
@section('content')
<main class="main">
        <div class="heading-wrapper">
          <h1>Roadmap</h1>
          <div class="dashboard_info-line">
            <p>Track upcoming game releases, platform improvements and future product updates</p>
          </div>
        </div>
        <div class="dashboard-row">
          <div class="banner">
            <p class="banner_category">Coming Next</p>
            <div class="banner_inner">
              <div>
                <p class="banner_title">Crash Games</p>
              </div>
              <div class="banner_info-line">
                <div>
                  <p>RTP</p>
                  <p class="text-weight-semibold">97.0%</p>
                </div>
                <div>
                  <p>Volatility</p>
                  <p class="text-weight-semibold">Very High</p>
                </div>
              </div>
            </div>
            <div class="buttons z-index-2">
              <a href="{{ route('games.index') }}" class="button w-inline-block">
                <p class="button_text">Discover</p>
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.14645 7.85349C8.75599 8.24395 8.75599 8.87702 9.14645 9.26749L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.463 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5606C15.2439 13.1701 15.2439 12.5369 14.8533 12.1464L10.5605 7.85349C10.17 7.46302 9.53692 7.46302 9.14645 7.85349Z" fill="#F15B4E"></path>
                  </svg>
                </div>
              </a>
              <a href="#" class="button is-secondary w-inline-block">
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.05713 20.8364V3.16364L18.9429 12L5.05713 20.8364Z"></path>
                  </svg>
                </div>
                <p class="button_text">Play Game</p>
              </a>
            </div>
            <div class="banner_image-wrapper is-game-cover">
              <div data-poster-url="{{ asset('client-design/videos/banner_poster.0000000.jpg') }}" data-video-urls="{{ asset('client-design/videos/banner_mp4.mp4') }},{{ asset('client-design/videos/banner_webm.webm') }}" data-autoplay="true" data-loop="true" data-wf-ignore="true" class="fullsize-img w-background-video w-background-video-atom"><video id="f85a1e35-d52e-91ff-a3a7-279d83baf753-video" autoplay="" loop="" style="background-image:url(&quot;{{ asset('client-design/videos/banner_poster.0000000.jpg') }}&quot;)" muted="" playsinline="" data-wf-ignore="true" data-object-fit="cover">
                  <source src="{{ asset('client-design/videos/banner_mp4.mp4') }}" data-wf-ignore="true">
                  <source src="{{ asset('client-design/videos/banner_webm.webm') }}" data-wf-ignore="true">
                </video></div><img src="{{ asset('client-design/images/7680b7339e68db2788ae2443fcf8f92994cdf7ac.png') }}" loading="lazy" width="493" height="617" alt="Name" class="fullsize-img">
            </div>
          </div>
          <div class="banner is-blue">
            <p class="banner_category">Coming Next</p>
            <div class="banner_inner">
              <div>
                <p class="banner_title is-opposite">Available Soon</p>
              </div>
              <div class="banner_info-line is-opposite">
                <div>
                  <p>RTP</p>
                  <p class="text-weight-semibold">97.0%</p>
                </div>
                <div>
                  <p>Volatility</p>
                  <p class="text-weight-semibold">Very High</p>
                </div>
              </div>
            </div>
            <div class="buttons z-index-2">
              <a href="{{ route('games.index') }}" class="button is-white w-inline-block">
                <p class="button_text">Discover</p>
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.14645 7.85349C8.75599 8.24395 8.75599 8.87702 9.14645 9.26749L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.463 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5606C15.2439 13.1701 15.2439 12.5369 14.8533 12.1464L10.5605 7.85349C10.17 7.46302 9.53692 7.46302 9.14645 7.85349Z" fill="#F15B4E"></path>
                  </svg>
                </div>
              </a>
              <a href="#" class="button is-secondary w-inline-block">
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.05713 20.8364V3.16364L18.9429 12L5.05713 20.8364Z"></path>
                  </svg>
                </div>
                <p class="button_text">Play Game</p>
              </a>
            </div>
            <div class="banner_image-wrapper is-game-cover">
              <div data-poster-url="{{ asset('client-design/videos/banner_poster.0000000.jpg') }}" data-video-urls="{{ asset('client-design/videos/banner_mp4.mp4') }},{{ asset('client-design/videos/banner_webm.webm') }}" data-autoplay="true" data-loop="true" data-wf-ignore="true" class="fullsize-img w-background-video w-background-video-atom"><video id="e4235cab-9596-fe7c-4503-e4808cfd0190-video" autoplay="" loop="" style="background-image:url(&quot;{{ asset('client-design/videos/banner_poster.0000000.jpg') }}&quot;)" muted="" playsinline="" data-wf-ignore="true" data-object-fit="cover">
                  <source src="{{ asset('client-design/videos/banner_mp4.mp4') }}" data-wf-ignore="true">
                  <source src="{{ asset('client-design/videos/banner_webm.webm') }}" data-wf-ignore="true">
                </video></div><img src="{{ asset('client-design/images/e020298b5f0f8d25d27842e7c8a17ba9d424f6a5.png') }}" loading="lazy" width="493" height="617" alt="Name" class="fullsize-img">
            </div>
          </div>
        </div>
        <div class="heading-wrapper">
          <h2>Roadmap Timeline</h2>
        </div>
        <div class="games_list-small">
          @forelse($items as $item)<div class="timeline_item">
            <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
              <div class="timeline_item-inner">
                <p class="tag">{{ \App\Models\RoadmapItem::STATUSES[$item->status] ?? $item->status }}</p>
                <div class="wrapper-vertical-xs">
                  <p class="text-weight-semibold">{{ $item->title }}</p>
                  <div class="downloads_info-line">
                    <p class="text-color-red-orange">{{ $item->target_date?->format('M Y') ?? 'To be announced' }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="timeline_progress-info">
              <p>{{ $item->is_demo ? 'Demo plan' : 'Status' }}</p>
              <p>{{ \App\Models\RoadmapItem::STATUSES[$item->status] ?? $item->status }}</p>
            </div>
            <div class="timeline_progress-line-wrapper">
              <div class="timeline_progress-line {{ $item->status === 'released' ? 'is-green' : 'is-blue' }}"></div>
            </div>
          </div>@empty<p>No roadmap entries available.</p>@endforelse
        </div>
        <div data-collapsing="" class="dashboard-row is-resources">
          <div data-collapsing="" class="dashboard_block is-downloads is-dark">
            <div no-scrollbar="" class="dashboard_block-header">
              <a href="#" class="tab-link is-active w-inline-block">
                <p>Regional Availability</p>
                <div class="tab-link_line"></div>
              </a>
            </div>
            <div data-dark-scrollbar="" class="downloads_list">
              <div class="availability_item">
                <p>World Champion X</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Comunbia</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
              <div class="availability_item">
                <p>Multi Hot 5 Wild</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
              <div class="availability_item">
                <p>Gates of Merlyn</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Comunbia</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
              <div class="availability_item">
                <p>World Champion X</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Comunbia</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
              <div class="availability_item">
                <p>Multi Hot 5 Wild</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
              <div class="availability_item">
                <p>World Champion X</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Comunbia</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
              <div class="availability_item">
                <p>World Champion X</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Comunbia</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
              <div class="availability_item">
                <p>World Champion X</p>
                <div class="wrapper-vertical-xs text-size-small">
                  <div class="tags">
                    <p class="text-color-blue">Available:</p>
                    <p class="tag is-blue">Netherlands</p>
                    <p class="tag is-blue">Peru</p>
                    <p class="tag is-blue">Comunbia</p>
                    <p class="tag is-blue">Malta</p>
                  </div>
                  <div class="tags">
                    <p class="text-color-red-orange">Upcoming:</p>
                    <p class="tag is-red">Latvia</p>
                    <p class="tag is-red">Bulgaria</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="dashboard_banner-block">
            <div data-tabs="" class="dashboard_block">
              <div no-scrollbar="" class="dashboard_block-header-wrapper">
                <div class="dashboard_block-header">
                  <a href="#" class="tab-link is-active w-inline-block">
                    <p>Announcements</p>
                    <div class="tab-link_line"></div>
                  </a>
                </div>
              </div>
              <div class="notifications_list">
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-blue text-weight-semibold text-size-medium">Documentation</p>
                    <p>2h ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p class="text-color-text">REST API v3.2 documentation released</p>
                  <p>WebSocket support, new RTP endpoints and enhanced reporting now documented.</p>
                  <div class="notifications_item-line text-color-blue"></div>
                </div>
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-green text-weight-semibold text-size-medium">Game Release</p>
                    <p>1d ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p class="text-color-text">Crash Duel X is live in Europe</p>
                  <p>UKGC &amp; MGA certified. Available for immediate integration in 12 markets.</p>
                  <div class="notifications_item-line text-color-green"></div>
                </div>
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-red-orange text-weight-semibold text-size-medium">JetX asset pack updated</p>
                    <p>2d ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p>This updated asset pack is designed to elevate your projects with fresh visuals and innovative features.</p>
                  <div class="notifications_item-line text-color-red-orange"></div>
                </div>
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-pink text-weight-semibold text-size-medium">MGA certificate renewed</p>
                    <p>2d ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p>Valid until Dec 2026</p>
                  <div class="notifications_item-line text-color-pink"></div>
                </div>
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-green text-weight-semibold text-size-medium">Game Release</p>
                    <p>1d ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p class="text-color-text">Crash Duel X is live in Europe</p>
                  <p>UKGC &amp; MGA certified. Available for immediate integration in 12 markets.</p>
                  <div class="notifications_item-line text-color-green"></div>
                </div>
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-green text-weight-semibold text-size-medium">Game Release</p>
                    <p>1d ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p class="text-color-text">Crash Duel X is live in Europe</p>
                  <p>UKGC &amp; MGA certified. Available for immediate integration in 12 markets.</p>
                  <div class="notifications_item-line text-color-green"></div>
                </div>
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-red-orange text-weight-semibold text-size-medium">JetX asset pack updated</p>
                    <p>2d ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p>This updated asset pack is designed to elevate your projects with fresh visuals and innovative features.</p>
                  <div class="notifications_item-line text-color-red-orange"></div>
                </div>
                <div class="notifications_item">
                  <div class="notifications_item-top">
                    <p class="text-color-red-orange text-weight-semibold text-size-medium">JetX asset pack updated</p>
                    <p>2d ago</p><button type="button" aria-label="Close" data-close-notification="" class="notifications_item-close">
                      <div class="icon w-embed"><svg width="14" viewbox="0 0 14 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                          <path d="M8.0717 6.49991L11.8224 3.01712L10.7508 2.02203L7.00007 5.50483L3.24944 2.0221L2.17781 3.01719L5.92844 6.49991L2.17773 9.98271L3.24936 10.9778L7.00007 7.495L10.7508 10.9779L11.8225 9.98278L8.0717 6.49991Z"></path>
                        </svg>
                      </div>
                    </button>
                  </div>
                  <p>This updated asset pack is designed to elevate your projects with fresh visuals and innovative features.</p>
                  <div class="notifications_item-line text-color-red-orange"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <h2>Roadmap Per Gegion</h2>
        <div class="dashboard_block is-dark">
          <div no-scrollbar="" class="dashboard_block-header">
            <a href="#" class="tab-link is-active w-inline-block">
              <p>BRAZIL</p>
              <div class="tab-link_line"></div>
            </a>
          </div>
          <div class="games_list-small">
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag">Released</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">Cheesy Road</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>100%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line is-green"></div>
              </div>
            </div>
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag is-red">Under Development</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">World Champion X</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>90%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line is-red"></div>
              </div>
            </div>
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag is-blue">Planning</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">World Champion X</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>90%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line is-blue"></div>
              </div>
            </div>
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag is-grey">Concept</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">World Champion X</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>90%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line"></div>
              </div>
            </div>
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag is-blue">Planning</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">World Champion X</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>90%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line is-blue"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="dashboard_block is-dark">
          <div no-scrollbar="" class="dashboard_block-header">
            <a href="#" class="tab-link is-active w-inline-block">
              <p>Netherlands</p>
              <div class="tab-link_line"></div>
            </a>
          </div>
          <div class="games_list-small">
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag">Released</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">Cheesy Road</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>100%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line is-green"></div>
              </div>
            </div>
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag is-red">Under Development</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">World Champion X</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>90%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line is-red"></div>
              </div>
            </div>
            <div class="timeline_item">
              <div class="timeline_item-wrapper"><img src="{{ asset('client-design/images/game-1.jpg') }}" loading="lazy" width="70" height="90" alt="Name" class="dashboard_games-image is-timeline">
                <div class="timeline_item-inner">
                  <p class="tag is-blue">Planning</p>
                  <div class="wrapper-vertical-xs">
                    <p class="text-weight-semibold">World Champion X</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">Crash Games</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="timeline_progress-info">
                <p>Progress</p>
                <p>90%</p>
              </div>
              <div class="timeline_progress-line-wrapper">
                <div class="timeline_progress-line is-blue"></div>
              </div>
            </div>
          </div>
        </div>
      </main>
@endsection
