@extends('layouts.original-client')
@section('title', 'Engagement Tools')
@section('design-page', '6a99e4bcc7cbafaf413ea5c3')
@section('content')
<main class="main">
        <div class="heading-wrapper">
          <h1>Engagement Tools</h1>
          <div class="max-width-900">
            <div class="dashboard_info-line">
              <p>Manage player engagement campaigns, promotional mechanics, missions, leaderboards, reward systems and other interactive tools designed to increase player retention and activity across SmartSoft products.</p>
            </div>
          </div>
        </div>
        <div class="tools_list">@forelse($tools as $tool)<div class="tools_item"><img src="{{ $tool->coverImageUrl() ?? asset('client-design/images/'.['tools-1.png', 'tool-2_1.png', 'tool-3.png'][$loop->index % 3]) }}" loading="lazy" width="396" height="384" alt="{{ $tool->title }}" class="tools_image">
            <h2 class="tools_name {{ ['text-color-red', 'text-color-red-orange', 'text-color-green'][$loop->index % 3] }}">{{ $tool->title }}</h2>
            <p>{{ $tool->description }}</p>
          </div>@empty<p>No tools available.</p>@endforelse</div><x-pagination :records="$tools" />
        <div class="dashboard_block is-dark">
          <div no-scrollbar="" class="dashboard_block-header">
            <a href="#" class="tab-link is-active w-inline-block">
              <p>Documentation</p>
              <div class="tab-link_line"></div>
            </a>
          </div>
          <div class="assets_list">
            @forelse($documents as $document)
                <x-documentation-card :document="$document" />
            @empty
                <p class="text-color-subtitles">No documents available.</p>
            @endforelse
          </div>
        </div>
      </main>
@endsection
