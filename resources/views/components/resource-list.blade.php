@props(['resources'])
<div class="resource-list">
    @forelse($resources as $resource)
        <article class="resource-row">
            <div class="file-icon" aria-hidden="true">{{ $resource->file_path ? strtoupper(pathinfo($resource->file_path, PATHINFO_EXTENSION)) : 'DOC' }}</div>
            <div class="resource-copy">
                <div class="tag-row"><span class="eyebrow">{{ \App\Models\ResourceItem::KINDS[$resource->kind] ?? $resource->kind }}</span><x-demo-badge :record="$resource" /></div>
                <a href="{{ route('resources.show', $resource->id) }}">{{ $resource->title }}</a>
                <p>{{ $resource->game?->title ?? 'General resource' }}</p>
            </div>
            @if($resource->hasDownloadableFile())
                <a class="button secondary" href="{{ route('resources.download', $resource->id) }}" aria-label="Download {{ $resource->title }}">Download ↓</a>
            @else
                <a class="button secondary" href="{{ route('resources.show', $resource->id) }}">Read →</a>
            @endif
        </article>
    @empty
        <div class="panel"><h2>No resources found</h2><p>Try another search or check back when new content is available.</p></div>
    @endforelse
</div>
