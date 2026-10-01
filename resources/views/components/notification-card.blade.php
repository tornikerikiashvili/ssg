@props(['notification', 'descriptionLimit' => 140])
<div class="notifications_item">
    <div class="notifications_item-top">
        <p class="{{ $notification->notificationColorClass() }} text-weight-semibold text-size-medium">{{ $notification->title }}</p>
        <p>{{ $notification->created_at->diffForHumans() }}</p>
    </div>
    @if(filled($notification->teaser))<p class="text-color-text">{{ $notification->teaser }}</p>@endif
    <p>{{ \Illuminate\Support\Str::limit($notification->description, $descriptionLimit) }}</p>
    <div class="notifications_item-line {{ $notification->notificationColorClass() }}"></div>
</div>
