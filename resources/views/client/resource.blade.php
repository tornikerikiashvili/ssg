@extends('layouts.original-client')
@section('title', $resource->title)
@section('content')
<main class="main">
    <div class="breadcrumbs"><a class="link-no-underline" href="{{ route('resources.index', $resource->kind) }}">Resource library</a></div>
    <div class="heading-wrapper"><h1>{{ $resource->title }}</h1></div>
    <article class="dashboard_block is-dark">
        <div class="wrapper-vertical-l">
            <p class="tag">{{ $resource->is_demo ? 'Demo' : (\App\Models\ResourceItem::KINDS[$resource->kind] ?? $resource->kind) }}</p>
            <p>{{ $resource->description }}</p>
            @if($resource->game)<p>Related game: <a href="{{ route('games.show', $resource->game->slug) }}">{{ $resource->game->title }}</a></p>@endif
            @if($resource->hasDownloadableFile())
                <div class="buttons"><a class="button" href="{{ route('resources.download', $resource->id) }}"><span class="button_text">Download {{ $resource->is_demo ? 'sample' : 'file' }}</span></a></div>
            @endif
        </div>
    </article>
</main>
@endsection
