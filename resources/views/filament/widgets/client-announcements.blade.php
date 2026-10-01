@php
    use App\Filament\Resources\Announcements\AnnouncementResource;
@endphp

<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-megaphone">
        <x-slot name="heading">Announcements</x-slot>
        <x-slot name="description">Share an announcement on the client dashboard or roadmap.</x-slot>

        <div style="display:flex;flex-wrap:wrap;gap:0.75rem">
            @if (AnnouncementResource::canCreate())
                <x-filament::button tag="a" :href="AnnouncementResource::getUrl('index', ['action' => 'create'])" icon="heroicon-m-plus">
                    Add announcement
                </x-filament::button>
            @endif
            <x-filament::button tag="a" :href="AnnouncementResource::getUrl('index')" color="gray">
                Manage announcements
            </x-filament::button>
        </div>
        <x-admin-communications-list :items="$items" empty-message="No announcements yet." />
    </x-filament::section>
</x-filament-widgets::widget>
