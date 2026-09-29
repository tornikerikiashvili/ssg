@props(['game'])
<a class="game-card" href="{{ route('games.show', $game->slug) }}">
    <div class="game-art">
        @if(array_key_exists($game->cover_image ?? '', \App\Models\Game::COVERS))
            <img src="{{ asset($game->cover_image) }}" alt="{{ $game->title }} reference artwork" loading="lazy">
        @else
            <span class="art-placeholder">{{ $game->title }}</span>
        @endif
        <x-demo-badge :record="$game" />
    </div>
    <div class="game-card-body"><span class="eyebrow">{{ $game->category }}</span><h2>{{ $game->title }} <span aria-hidden="true">↗</span></h2></div>
</a>
