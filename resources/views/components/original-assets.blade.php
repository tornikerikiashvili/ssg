@props(['resources'])
<div class="assets_list">
@forelse($resources as $resource)
<div class="assets_item" data-resource-id="{{ $resource->id }}" data-category-id="{{ $resource->catalog_option_id }}" data-title="{{ $resource->title }}" data-created-at="{{ $resource->created_at?->timestamp ?? 0 }}">
                <div class="assets_item-icon-wrapper">
                  <div data-asset-fallback>
                    @if($iconUrl = $resource->fileFormat?->getFirstMediaUrl('icon'))
                      <img src="{{ $iconUrl }}" class="assets_item-icon" width="90" height="90" alt="{{ strtoupper(pathinfo($resource->file_path ?? '', PATHINFO_EXTENSION)) }} file" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.hidden=false;">
                      <div hidden><x-asset-file-icon :extension="pathinfo($resource->file_path ?? '', PATHINFO_EXTENSION)" /></div>
                    @else
                      <x-asset-file-icon :extension="pathinfo($resource->file_path ?? '', PATHINFO_EXTENSION)" />
                    @endif
                  </div>
                  @if($resource->supportsDropboxThumbnail())
                    <img src="{{ route('resources.thumbnail', $resource->id) }}" @class(['assets_item-icon', 'assets_item-thumbnail', 'assets_item-thumbnail-jpeg' => in_array(strtolower(pathinfo($resource->file_path ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true)]) width="90" height="90" alt="{{ $resource->title }} preview" loading="lazy" decoding="async" style="opacity:0" onload="this.style.opacity='1'; this.previousElementSibling.style.visibility='hidden';" onerror="this.remove();">
                  @endif
                  <div class="assets_item-checkbox"></div>
                </div>
                <div class="assets_item-inner">
                  <div class="assets_item-info-block">
                    <p class="text-weight-semibold">{{ $resource->title }}</p>
                    <div class="downloads_info-line">
                      <p class="text-color-red-orange">{{ $resource->catalogOption?->name ?? ($resource->is_demo ? 'Demo file' : 'Resource') }}</p>
                      <div class="dot-small"></div>
                      <p>{{ strtoupper(pathinfo($resource->file_path ?? '', PATHINFO_EXTENSION)) }}</p>
                    </div>
                  </div><a href="{{ $resource->hasDownloadableFile() ? route('resources.download', $resource->id) : route('resources.show', $resource->id) }}" @if($resource->hasDownloadableFile()) data-choose-asset data-asset-id="{{ $resource->id }}" data-asset-title="{{ $resource->title }}" data-asset-type="{{ strtoupper(pathinfo($resource->file_path, PATHINFO_EXTENSION)) }}" data-asset-size="{{ $resource->dropbox_file_id ? $resource->file_size : (\Illuminate\Support\Facades\Storage::disk('local')->exists($resource->file_path) ? \Illuminate\Support\Facades\Storage::disk('local')->size($resource->file_path) : 0) }}" @endif aria-label="Download {{ $resource->title }}" class="card-button">
                    <p class="button_text">Download</p>
                    <div class="button_icon w-embed"><svg width="21" viewbox="0 0 21 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.28851 8.62951C6.86291 8.95363 6.64928 9.53626 6.86978 10.1139C7.12731 10.7886 7.63324 11.4086 8.19911 11.8883C8.77126 12.3733 9.48722 12.7854 10.2481 12.9756C10.4135 13.017 10.5865 13.017 10.752 12.9756C11.5128 12.7854 12.2287 12.3733 12.8009 11.8883C13.3668 11.4086 13.8727 10.7886 14.1302 10.1139C14.3507 9.53626 14.1371 8.95363 13.7115 8.62951C13.3276 8.33719 12.8216 8.27984 12.3829 8.4483C12.1379 8.54243 11.8312 8.64463 11.5003 8.72256V4.52527C11.5003 3.97298 11.0526 3.52527 10.5003 3.52527C9.94806 3.52527 9.50035 3.97298 9.50035 4.52527V8.72273C9.16925 8.64477 8.86228 8.54249 8.61705 8.4483C8.17844 8.27983 7.67236 8.33719 7.28851 8.62951ZM5.49902 13.9731C5.49902 13.4207 5.05131 12.973 4.49902 12.973C3.94674 12.973 3.49902 13.4207 3.49902 13.9731V14.8069C3.49902 15.5492 3.82639 16.2377 4.36955 16.7283C4.90934 17.2159 5.62154 17.4747 6.34547 17.4747H14.6545C15.3784 17.4747 16.0906 17.2159 16.6304 16.7283C17.1736 16.2377 17.5009 15.5492 17.5009 14.8069V13.9731C17.5009 13.4207 17.0532 12.973 16.5009 12.973C15.9486 12.973 15.5009 13.4207 15.5009 13.9731V14.8069C15.5009 14.9493 15.4392 15.1092 15.2898 15.2441C15.1371 15.3821 14.91 15.4747 14.6545 15.4747H6.34547C6.08998 15.4747 5.86288 15.3821 5.71012 15.2441C5.56073 15.1092 5.49902 14.9493 5.49902 14.8069V13.9731Z"></path>
                      </svg>
                    </div>
                  </a>
                </div>
              </div>
@empty<p class="text-color-subtitles">No resources found.</p>
@endforelse
</div>
