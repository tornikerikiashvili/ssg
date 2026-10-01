@php
    use App\Filament\Resources\PortalNotifications\PortalNotificationResource;
@endphp

<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-bell">
        <x-slot name="heading">Notifications</x-slot>
        <x-slot name="description">Add a notification for all clients or a specific partner company.</x-slot>

        <div style="display:flex;flex-wrap:wrap;gap:0.75rem">
            @if (PortalNotificationResource::canCreate())
                <x-filament::button tag="a" :href="PortalNotificationResource::getUrl('index', ['action' => 'create'])" icon="heroicon-m-plus">
                    Add notification
                </x-filament::button>
            @endif
            <x-filament::button tag="a" :href="PortalNotificationResource::getUrl('index')" color="gray">
                Manage notifications
            </x-filament::button>
        </div>
        <x-admin-communications-list :items="$items" empty-message="No notifications yet." />
    </x-filament::section>
</x-filament-widgets::widget>
