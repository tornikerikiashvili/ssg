@props(['document'])
<a href="{{ $document->documentationUrl() }}" @if($document->isDocumentationLink()) target="_blank" rel="noopener noreferrer" @endif class="docs_item w-inline-block">
    <p class="tag">{{ $document->catalogOption?->name ?? 'Document' }}</p>
    <div class="wrapper-vertical-xs">
        <p class="text-weight-semibold">{{ $document->title }}</p>
        <p class="text-size-small">{{ $document->description }}</p>
    </div>
    <span class="card-button"><p>{{ $document->isDocumentationLink() ? 'Open link' : 'Download' }}</p></span>
</a>
