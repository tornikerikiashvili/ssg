@props(['item', 'openableGameIds'])
<div class="timeline_item">
    <div class="timeline_item-wrapper">
        @if($item->game?->cover_image_url)<img src="{{ $item->game->cover_image_url }}" loading="lazy" width="70" height="90" alt="{{ $item->game->title }}" class="dashboard_games-image is-timeline" style="width:55px;height:72px;object-fit:cover;align-self:flex-start;flex-shrink:0">@endif
        <div class="timeline_item-inner">
            <p class="tag {{ $item->status === 'released' ? '' : ($item->status === 'in_progress' ? 'is-red' : 'is-blue') }}">{{ \App\Models\RoadmapItem::STATUSES[$item->status] ?? $item->status }}</p>
            <div class="wrapper-vertical-xs">
                @if($item->game && $openableGameIds->contains($item->game_id))
                    <a href="{{ route('games.show', $item->game->slug) }}" class="text-weight-semibold">{{ $item->game->title }}</a>
                @else
                    <p class="text-weight-semibold">{{ $item->game?->title ?? $item->title }}</p>
                @endif
                @if($item->game)<div class="downloads_info-line"><p class="text-color-red-orange">{{ $item->game->category }}</p></div>@endif
            </div>
        </div>
    </div>
    @if($item->progress !== null)
        <div class="timeline_progress-info"><p>Progress</p><p>{{ $item->progress }}%</p></div>
        <div class="timeline_progress-line-wrapper" role="progressbar" aria-label="{{ $item->title }} development progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item->progress }}">
            <div class="timeline_progress-line {{ $item->status === 'released' ? 'is-green' : ($item->status === 'in_progress' ? 'is-red' : 'is-blue') }}" style="width:{{ $item->progress }}%"></div>
        </div>
    @endif
</div>
