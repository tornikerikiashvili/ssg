@props(['items', 'emptyMessage'])

<ul class="admin-communications-list">
    @forelse ($items as $item)
        <li class="admin-communications-item">
            <div class="admin-communications-title-row">
                <span class="admin-communications-title">{{ $item->title }}</span>
                <x-filament::badge :color="$item->is_published ? 'success' : 'gray'">
                    {{ $item->is_published ? 'Published' : 'Draft' }}
                </x-filament::badge>
            </div>
            <div class="admin-communications-meta">
                <span>{{ $item->company?->name ?? 'All clients' }}</span>
                <time datetime="{{ $item->created_at?->toIso8601String() }}" title="{{ $item->created_at?->format('M j, Y H:i') }}">{{ $item->created_at?->diffForHumans() }}</time>
            </div>
        </li>
    @empty
        <li class="admin-communications-meta">{{ $emptyMessage }}</li>
    @endforelse
</ul>
