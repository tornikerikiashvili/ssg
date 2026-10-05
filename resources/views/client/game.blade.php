@extends('layouts.original-client')
@section('title', $game->title)
@section('design-page', '6a9735751dfd3bb832a4901e')
@section('content')
<main class="main">
        <div class="breadcrumbs">
          <div>
            <a href="{{ route('dashboard') }}" class="link-no-underline">Dashboard</a>
          </div>
          <div class="breadcrumbs_item">
            <a href="{{ route('games.index') }}" class="link-no-underline">Games</a>
          </div>
          <div class="breadcrumbs_item">
            <p class="text-color-subtitles">{{ $game->title }}</p>
          </div>
        </div>
        <div class="games-inner_hero">
          <div class="games-inner_hero-content">
            <h1 class="banner_title">{{ $game->title }}</h1>
            <div class="tags">
              <p class="tag">{{ $game->category }}</p>
              @if($game->is_featured)
              <p class="tag is-red">Featured</p>
              @endif
              @if($game->release_status === 'upcoming')
              <p class="tag is-blue">Upcoming</p>
              @endif
            </div>
            <div class="banner_inner">
              <div class="banner_info-line">
                <div>
                  <p class="games-inner_hero-info-title">RTP</p>
                  <p class="text-weight-semibold text-color-red-orange">{{ $game->rtp !== null ? $game->rtp.'%' : '—' }}</p>
                </div>
                <div>
                  <p class="games-inner_hero-info-title">Volatility</p>
                  <p class="text-weight-semibold">{{ $game->volatility?->name ?? 'Not specified' }}</p>
                </div>
                <div>
                  <p class="games-inner_hero-info-title">Max Bet</p>
                  <p class="text-weight-semibold">{{ $game->max_bet ?? $game->specificationValue('Max bet') ?? '—' }}</p>
                </div>
                <div>
                  <p class="games-inner_hero-info-title">Min Bet</p>
                  <p class="text-weight-semibold">{{ $game->min_bet ?? $game->specificationValue('Min bet') ?? '—' }}</p>
                </div>
                <div>
                  <p class="games-inner_hero-info-title">Certificates</p>
                  <p class="text-weight-semibold">{{ $game->certifications ?: 'Not specified' }}</p>
                </div>
                <div>
                  <p class="games-inner_hero-info-title">Languages</p>
                  <p class="text-weight-semibold">{{ $game->languages ?? $game->specificationValue('Languages') ?? '—' }}</p>
                </div>
              </div>
            </div>
            <div class="wrapper-vertical-l"><p>{{ $game->description }}</p></div>
            <div class="buttons z-index-2">
              @if($game->demo_url)
              <a href="{{ $game->demo_url }}" target="_blank" rel="noopener noreferrer" class="button is-secondary w-inline-block">
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.05713 20.8364V3.16364L18.9429 12L5.05713 20.8364Z"></path>
                  </svg>
                </div>
                <p class="button_text">Play Demo</p>
              </a>
              @endif
              @if($game->canAccessAssets(auth()->user()))
<a href="#resources" class="button w-inline-block">
                <p class="button_text">Download Assets</p>
                <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.78851 10.1296C8.36291 10.4538 8.14928 11.0364 8.36978 11.6141C8.62731 12.2887 9.13324 12.9087 9.69911 13.3884C10.2713 13.8734 10.9872 14.2856 11.7481 14.4758C11.9135 14.5171 12.0865 14.5171 12.252 14.4758C13.0128 14.2856 13.7287 13.8734 14.3009 13.3884C14.8668 12.9087 15.3727 12.2887 15.6302 11.6141C15.8507 11.0364 15.6371 10.4538 15.2115 10.1296C14.8276 9.83731 14.3216 9.77996 13.8829 9.94842C13.6379 10.0426 13.3312 10.1448 13.0003 10.2227V6.02539C13.0003 5.47311 12.5526 5.02539 12.0003 5.02539C11.4481 5.02539 11.0003 5.47311 11.0003 6.02539V10.2229C10.6693 10.1449 10.3623 10.0426 10.1171 9.94842C9.67844 9.77995 9.17236 9.83731 8.78851 10.1296ZM6.99902 15.4732C6.99902 14.9209 6.55131 14.4732 5.99902 14.4732C5.44674 14.4732 4.99902 14.9209 4.99902 15.4732V16.307C4.99902 17.0493 5.32639 17.7378 5.86955 18.2284C6.40934 18.716 7.12154 18.9748 7.84547 18.9748H16.1545C16.8784 18.9748 17.5906 18.716 18.1304 18.2284C18.6736 17.7378 19.0009 17.0493 19.0009 16.307V15.4732C19.0009 14.9209 18.5532 14.4732 18.0009 14.4732C17.4486 14.4732 17.0009 14.9209 17.0009 15.4732V16.307C17.0009 16.4494 16.9392 16.6093 16.7898 16.7442C16.6371 16.8822 16.41 16.9748 16.1545 16.9748H7.84547C7.58998 16.9748 7.36288 16.8822 7.21012 16.7442C7.06073 16.6093 6.99902 16.4494 6.99902 16.307V15.4732Z"></path>
                  </svg>
                </div>
              </a>
              @endif
            </div>
          </div>
          <div class="games-inner_hero-img-wrapper"><img src="{{ $game->cover_image_url ?: asset('client-design/images/game-1.jpg') }}" loading="lazy" width="299" height="374" alt="Image" class="games-inner_hero-img"></div>
        </div>
        <div data-collapsing="" class="dashboard-row">
          <div class="quick-actions_wrapper">
            <div data-tabs="" class="dashboard_block">
              <div no-scrollbar="" class="dashboard_block-header-wrapper">
                <div class="dashboard_block-header">
                  <a href="#" class="tab-link is-active w-inline-block">
                    <p>Game Features</p>
                    <div class="tab-link_line"></div>
                  </a>
                  <a href="#" class="tab-link w-inline-block">
                    <p>Specifications</p>
                    <div class="tab-link_line"></div>
                  </a>
                  <a href="#" class="tab-link w-inline-block">
                    <p>Rules</p>
                    <div class="tab-link_line"></div>
                  </a>
                </div>
              </div>
              <div>
                <div data-tab-pane="">
                  <div class="wrapper-vertical-l">
                    <p style="white-space:pre-line">{{ $game->features ?: 'No features added yet.' }}</p>
                    <div class="tags">
                      @foreach($game->feature_tags ?? [] as $tag)
                      <p @class(['tag', 'is-red' => $loop->index % 3 === 1, 'is-blue' => $loop->index % 3 === 2])>{{ $tag }}</p>
                      @endforeach
                    </div>
                  </div>
                </div>
                <div data-tab-pane="">
                  <ul role="list" class="specifications_list w-list-unstyled">
                    @forelse($game->specifications as $specification)
                    <li class="specifications_list-item">
                      <p>{{ $specification['label'] }}</p>
                      @if(!empty($specification['children']))
                      <ul role="list" class="specifications_list w-list-unstyled">
                        @foreach($specification['children'] as $point)
                        <li class="specifications_list-item-sublevel">{{ $point }}</li>
                        @endforeach
                      </ul>
                      @endif
                    </li>
                    @empty<li>No specifications added yet.</li>@endforelse
                  </ul>
                </div>
                <div data-tab-pane=""><p style="white-space:pre-line">{{ $game->rules ?: 'No rules added yet.' }}</p></div>
              </div>
            </div>
            <div class="dashboard_block is-dark">
              <div no-scrollbar="" class="dashboard_block-header">
                <a href="#" class="tab-link is-active w-inline-block">
                  <p>Engagement tools</p>
                  <div class="tab-link_line"></div>
                </a>
              </div>
              <div class="game-engagement-tools_list">@forelse($tools as $tool)<a class="game-engagement-tools_item" href="{{ route('tools.index') }}"><img src="{{ $tool->coverImageUrl() ?? asset('client-design/images/tool-1.png') }}" width="52" height="54" alt=""><p>{{ $tool->title }}</p></a>@empty<p>No engagement tools assigned.</p>@endforelse</div>
            </div>
          </div>
          <div class="dashboard_block is-dark">
            <div no-scrollbar="" class="dashboard_block-header">
              <a href="#" class="tab-link is-active w-inline-block">
                <p>Regions Available</p>
                <div class="tab-link_line"></div>
              </a>
            </div>
            <x-regions-map :availabilities="$game->regionAvailabilities" />
            <div class="wrapper-vertical-l">@forelse($game->regionAvailabilities as $availability)<div class="map_info-item"><div class="{{ ['available' => 'blue-dot', 'limited' => 'red-dot', 'unavailable' => 'grey-dot'][$availability->status] ?? 'grey-dot' }}"></div><p>{{ $availability->region->name }} — {{ \App\Models\GameRegion::STATUSES[$availability->status] ?? 'Not specified' }}</p></div>@empty<p class="text-color-subtitles">Available in all regions.</p>@endforelse</div>
          </div>
        </div>
        @if($game->canAccessAssets(auth()->user()))
        <div class="dashboard_block is-dark" id="resources">
          <div class="dashboard_block-header"><a class="tab-link is-active"><p>Game Assets</p><div class="tab-link_line"></div></a></div>
          <x-asset-filters :categories="$assetCategories">
            <x-slot:selectionActions>
              <div id="asset-selection-actions" class="buttons" hidden style="display:none">
                <button type="button" data-select-all aria-pressed="false" class="card-button"><span class="button_checkbox" aria-hidden="true"></span><span class="button_text">Select all</span></button>
                <button type="submit" form="asset-archive" class="card-button is-grey">Download selected</button>
              </div>
            </x-slot:selectionActions>
          </x-asset-filters>
          <div id="asset-selection-hint" class="tag" role="status"><p>To select multiple options, please click on the multiplier cards.</p></div>
          <p id="asset-selection-summary" class="text-size-small text-color-subtitles" role="status"></p>
          <p id="asset-selection-error" class="text-size-small text-color-red-orange" role="alert" hidden></p>
          <form id="asset-archive" hidden style="display:none" method="POST" action="{{ route('games.assets.archive', $game->slug) }}">@csrf<div id="asset-selection-inputs"></div></form>
          <x-original-assets :resources="$resources" />
          <p id="asset-filter-empty" class="text-color-subtitles" role="status" hidden>No assets match this category.</p>
        </div>
        @endif
        <div class="dashboard_block is-dark">
          <div no-scrollbar="" class="dashboard_block-header">
            <a href="#" class="tab-link is-active w-inline-block">
              <p>Documentation</p>
              <div class="tab-link_line"></div>
            </a>
          </div>
          <div class="assets_list">@forelse($documents as $document)<x-documentation-card :document="$document" />@empty<p>No documents assigned.</p>@endforelse</div>
        </div>
        <div class="heading-arrows">
          <div class="heading-wrapper">
            <h2 class="heading-style-h1">Related Games</h2>
            <p>Browse, discover and manage SmartSoft products available for your Country</p>
          </div>
          <div class="swiper-arrows">
            <a data-arrow-prev="" href="#" class="swiper-arrow w-inline-block">
              <div class="icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14.8535 7.85355C15.244 8.24401 15.244 8.87708 14.8535 9.26755L11.2675 12.8535L14.8535 16.4395C15.244 16.83 15.244 17.4631 14.8535 17.8535C14.4631 18.244 13.83 18.244 13.4395 17.8535L9.14665 13.5607C8.75613 13.1701 8.75613 12.537 9.14665 12.1464L13.4395 7.85355C13.83 7.46308 14.4631 7.46308 14.8535 7.85355Z"></path>
                </svg>
              </div>
              <div class="tab-link_line is-arrow"></div>
            </a>
            <a data-arrow-next="" href="#" class="swiper-arrow w-inline-block">
              <div class="icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9.14645 7.85355C8.75599 8.24401 8.75599 8.87708 9.14645 9.26755L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.4631 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5607C15.2439 13.1701 15.2439 12.537 14.8533 12.1464L10.5605 7.85355C10.17 7.46308 9.53692 7.46308 9.14645 7.85355Z"></path>
                </svg>
              </div>
              <div class="tab-link_line is-arrow"></div>
            </a>
          </div>
        </div>
        <div class="cards-swiper">
          <div class="swiper-wrapper">@foreach($relatedGames as $related)<div class="swiper_item swiper-slide">
              <div class="games_item">
                <a href="{{ route('games.show', $related->slug) }}" aria-current="page" class="w-inline-block w--current"><img src="{{ $related->cover_image_url ?: asset('client-design/images/Logo-icon.svg') }}" loading="lazy" width="282" height="347" alt="Cover" class="games_item-image"></a>
                <a href="{{ route('games.show', $related->slug) }}" aria-current="page" class="games_item-info-block w-inline-block w--current">
                  <div class="games_item-info-col">
                    <h2>{{ $related->title }}</h2>
                    <p>{{ $related->category }}</p>
                  </div>
                  <div class="games_item-info-col is-right">
                    <p class="text-weight-semibold text-color-red-orange text-size-medium">{{ $related->rtp !== null ? $related->rtp.' %' : '—' }}</p>
                    <p>RTP</p>
                  </div>
                </a>
                <div class="buttons is-gap-s">
                  <a href="{{ route('games.show', $related->slug) }}" aria-current="page" class="card-button w-inline-block w--current">
                    <p class="button_text">Open</p>
                    <div class="button_icon w-embed"><svg width="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.14645 7.85349C8.75599 8.24395 8.75599 8.87702 9.14645 9.26749L12.7325 12.8535L9.14645 16.4395C8.75599 16.83 8.75599 17.463 9.14645 17.8535C9.53692 18.244 10.17 18.244 10.5605 17.8535L14.8533 13.5606C15.2439 13.1701 15.2439 12.5369 14.8533 12.1464L10.5605 7.85349C10.17 7.46302 9.53692 7.46302 9.14645 7.85349Z" fill="#F15B4E"></path>
                      </svg>
                    </div>
                  </a>
                  @if($related->canAccessAssets(auth()->user()))
<a href="{{ route('games.show', $related->slug) }}#resources" class="card-button w-inline-block">
                    <p class="button_text">Download Assets</p>
                    <div class="button_icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.28851 8.62951C6.86291 8.95363 6.64928 9.53626 6.86978 10.1139C7.12731 10.7886 7.63324 11.4086 8.19911 11.8883C8.77126 12.3733 9.48722 12.7854 10.2481 12.9756C10.4135 13.017 10.5865 13.017 10.752 12.9756C11.5128 12.7854 12.2287 12.3733 12.8009 11.8883C13.3668 11.4086 13.8727 10.7886 14.1302 10.1139C14.3507 9.53626 14.1371 8.95363 13.7115 8.62951C13.3276 8.33719 12.8216 8.27984 12.3829 8.4483C12.1379 8.54243 11.8312 8.64463 11.5003 8.72256V4.52527C11.5003 3.97298 11.0526 3.52527 10.5003 3.52527C9.94806 3.52527 9.50035 3.97298 9.50035 4.52527V8.72273C9.16925 8.64477 8.86228 8.54249 8.61705 8.4483C8.17844 8.27983 7.67236 8.33719 7.28851 8.62951ZM5.49902 13.9731C5.49902 13.4207 5.05131 12.973 4.49902 12.973C3.94674 12.973 3.49902 13.4207 3.49902 13.9731V14.8069C3.49902 15.5492 3.82639 16.2377 4.36955 16.7283C4.90934 17.2159 5.62154 17.4747 6.34547 17.4747H14.6545C15.3784 17.4747 16.0906 17.2159 16.6304 16.7283C17.1736 16.2377 17.5009 15.5492 17.5009 14.8069V13.9731C17.5009 13.4207 17.0532 12.973 16.5009 12.973C15.9486 12.973 15.5009 13.4207 15.5009 13.9731V14.8069C15.5009 14.9493 15.4392 15.1092 15.2898 15.2441C15.1371 15.3821 14.91 15.4747 14.6545 15.4747H6.34547C6.08998 15.4747 5.86288 15.3821 5.71012 15.2441C5.56073 15.1092 5.49902 14.9493 5.49902 14.8069V13.9731Z"></path>
                      </svg>
                    </div>
                  </a>
              @endif
                </div>
              </div>
            </div>@endforeach</div>
        </div>
      </main>
@endsection
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js"></script>
<script>
const assetFilters = document.querySelector('#game-asset-filters');
if (assetFilters) {
const assetCards = [...document.querySelectorAll('#resources .assets_item[data-resource-id]')];
const assetList = document.querySelector('#resources .assets_list');
const selectAllButton = document.querySelector('#resources [data-select-all]');
const archiveForm = document.querySelector('#asset-archive');
const assetSelectionHint = document.querySelector('#asset-selection-hint');
const assetCategories = assetFilters.querySelector('.filter_buttons');
const selectionActions = document.querySelector('#asset-selection-actions');
const selectionSummary = document.querySelector('#asset-selection-summary');
const selectionError = document.querySelector('#asset-selection-error');
const downloadAssetsButton = selectionActions.querySelector('[type="submit"]');
const selectedAssets = () => visibleAssets().filter(card => card.classList.contains('is-active'));
const assetSize = card => {
    const value = card.querySelector('[data-asset-size]')?.dataset.assetSize;
    return value === undefined || value === '' ? NaN : Number(value);
};
const totalSize = cards => cards.reduce((total, card) => total + assetSize(card), 0);
function showSelectionError(message = '') {
    selectionError.textContent = message;
    selectionError.hidden = !message;
}
function validSelection(cards) {
    if (cards.length > 50) {
        showSelectionError('Select up to 50 files per download.');
        return false;
    }
    if (cards.some(card => !Number.isFinite(assetSize(card)) || assetSize(card) < 0)) {
        showSelectionError('A file size is unavailable. Please download that file individually.');
        return false;
    }
    if (totalSize(cards) > 100 * 1024 * 1024) {
        showSelectionError('The selected files exceed 100 MB. Please select fewer files.');
        return false;
    }
    showSelectionError();
    return true;
}
const visibleAssets = () => assetCards.filter(card => !card.hidden);
function selectAsset(card, selected) {
    card.classList.toggle('is-active', selected);
    card.querySelector('.assets_item-checkbox').classList.toggle('is-active', selected);
}
function updateAssetSelection() {
    const visible = visibleAssets();
    const hasSelection = visible.some(card => card.classList.contains('is-active'));
    assetCategories.hidden = hasSelection;
    assetCategories.style.display = hasSelection ? 'none' : '';
    document.querySelector('#asset-selection-inputs').replaceChildren();
    selectionActions.hidden = !hasSelection;
    selectionActions.style.display = hasSelection ? '' : 'none';
    selectionSummary.textContent = `${selectedAssets().length} / 50 files selected · ${(totalSize(selectedAssets()) / (1024 * 1024)).toFixed(2)} / 100 MB`;
    assetSelectionHint.hidden = hasSelection;
    assetSelectionHint.style.display = hasSelection ? 'none' : '';
    const allSelected = visible.length > 0 && visible.every(card => card.classList.contains('is-active'));
    selectAllButton.disabled = visible.length === 0;
    selectAllButton.setAttribute('aria-pressed', String(allSelected));
    selectAllButton.querySelector('.button_text').textContent = allSelected ? 'Unselect all' : 'Select all';
    selectAllButton.querySelector('.button_checkbox').classList.toggle('is-active', allSelected);
    downloadAssetsButton.disabled = !hasSelection;
    downloadAssetsButton.textContent = allSelected ? 'Download all' : 'Download selected';
}
function filterAssets() {
    const category = assetFilters.querySelector('[name="asset_category"]:checked').value;
    const sort = assetFilters.querySelector('[name="asset_sort"]:checked').value;
    assetCards.forEach(card => {
        card.hidden = category !== '' && card.dataset.categoryId !== category;
        card.style.display = card.hidden ? 'none' : '';
        if (card.hidden) selectAsset(card, false);
    });
    [...assetCards].sort((a, b) => {
        const comparison = sort === 'newest'
            ? Number(b.dataset.createdAt) - Number(a.dataset.createdAt)
            : a.dataset.title.localeCompare(b.dataset.title);
        return comparison || Number(a.dataset.resourceId) - Number(b.dataset.resourceId);
    }).forEach(card => assetList.append(card));
    assetFilters.querySelectorAll('[name="asset_category"]').forEach(input => {
        input.previousElementSibling.classList.toggle('w--redirected-checked', input.checked);
    });
    document.querySelector('#asset-filter-empty').hidden = assetCards.length === 0 || visibleAssets().length > 0;
    updateAssetSelection();
}
assetFilters.addEventListener('submit', event => event.preventDefault());
assetFilters.addEventListener('change', event => {
    if (event.target.name === 'asset_category') {
        assetCards.forEach(card => selectAsset(card, false));
        showSelectionError();
    }
    filterAssets();
    if (event.target.name === 'asset_sort') {
        const trigger = assetFilters.querySelector('[data-accordion-trigger]');
        trigger.querySelector('p').textContent = event.target.closest('label').textContent.trim();
        if (trigger.getAttribute('aria-expanded') === 'true') trigger.click();
    }
});
assetCards.forEach(card => card.addEventListener('click', event => {
    if (!event.target.closest('a')) {
        const selecting = !card.classList.contains('is-active');
        if (selecting && !validSelection([...selectedAssets(), card])) return;
        showSelectionError();
        selectAsset(card, selecting);
        updateAssetSelection();
    }
}));
selectAllButton.addEventListener('click', () => {
    const visible = visibleAssets();
    const select = visible.some(card => !card.classList.contains('is-active'));
    if (select && !validSelection(visible)) return;
    showSelectionError();
    visible.forEach(card => selectAsset(card, select));
    updateAssetSelection();
});
archiveForm.addEventListener('submit', event => {
    const selected = visibleAssets().filter(card => card.classList.contains('is-active'));
    if (!selected.length || !validSelection(selected)) {
        event.preventDefault();
        return;
    }
    const container = document.querySelector('#asset-selection-inputs');
    container.replaceChildren();
    selected.forEach(card => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = card.dataset.resourceId;
        container.append(input);
    });
});
filterAssets();
}
new Swiper('.cards-swiper', { slidesPerView:'auto', freeMode:true, navigation:{nextEl:'[data-arrow-next]',prevEl:'[data-arrow-prev]'} });
</script>
@endpush
