@extends('layouts.client')
@section('title', 'Updates')
@section('content')
<p class="eyebrow">Stay informed</p><h1>UPDATES</h1>
<div class="content-list">@forelse($announcements as $announcement)<article class="panel"><div class="tag-row"><span class="tag">{{ ucfirst($announcement->priority) }}</span><x-demo-badge :record="$announcement" /></div><h2>{{ $announcement->title }}</h2><p class="long-copy">{{ $announcement->description }}</p><small class="muted">{{ $announcement->created_at->format('d M Y') }}</small></article>@empty<p class="muted">No updates yet.</p>@endforelse</div><x-pagination :records="$announcements" />
@endsection
