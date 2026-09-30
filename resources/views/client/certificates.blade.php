@extends('layouts.original-client')
@section('title', 'Licenses and Certificates')
@section('design-page', '6a9a84950a3f566ea0f52ab7')
@section('content')
<main class="main">
        <div class="heading-wrapper">
          <h1>Licenses and Certificates</h1>
          <div class="dashboard_info-line">
            <p>Welcome to the Licenses and Certificates page! Here, you can easily explore and manage all the licenses and certificates associated with SmartSoft products tailored for your business needs. Discover the various options available and ensure your company stays compliant and up-to-date.</p>
          </div>
        </div>
        @foreach(['Licenses' => [$licenses, 'license'], 'Certifications' => [$resources, 'certification']] as $sectionTitle => [$sectionResources, $resourceLabel])
        <div class="dashboard_block is-dark">
          <div no-scrollbar="" class="dashboard_block-header">
            <a href="#" class="tab-link is-active w-inline-block">
              <p>{{ $sectionTitle }}</p>
              <div class="tab-link_line"></div>
            </a>
          </div>
          <div class="assets_list">@forelse($sectionResources as $resource)<a @if($resource->hasDownloadableFile()) href="{{ route('resources.download', $resource->id) }}" download @else aria-disabled="true" title="No file available" @endif class="licences_item w-inline-block"><img src="{{ asset('client-design/images/Zip.png') }}" loading="lazy" width="50" height="50" alt="Logo" class="certifications_image">
              <div class="wrapper-vertical-xxs grow">
                <p class="text-style-uppercase text-weight-semibold">{{ $resource->title }}</p>
                @if($resource->description)<p class="text-size-small">{{ $resource->description }}</p>@endif<p class="text-size-small text-color-red-orange">{{ $resource->is_demo ? 'Demo — not a valid '.$resourceLabel : ucfirst($resourceLabel) }}</p>
              </div><span class="card-button is-no-text">
                <div class="button_icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.1227 13.9427C15.1227 14.5776 14.608 15.0922 13.9731 15.0922C13.3382 15.0922 12.8236 14.5776 12.8236 13.9427V10.2552L7.83939 15.2394C7.44891 15.6299 6.81583 15.6299 6.42528 15.2395L6.22298 15.0373C5.83234 14.6468 5.83231 14.0135 6.2229 13.6229L11.2073 8.63896H7.51984C6.88496 8.63896 6.37029 8.12429 6.37029 7.4894C6.37029 6.85452 6.88496 6.33984 7.51984 6.33984H14.9092C15.0271 6.33984 15.1227 6.43542 15.1227 6.55332V13.9427Z"></path>
                  </svg>
                </div>
              </span>
            </a>@empty<p>No {{ strtolower($sectionTitle) }} available.</p>@endforelse</div><x-pagination :records="$sectionResources" />
        </div>
        @endforeach
      </main>
@endsection
