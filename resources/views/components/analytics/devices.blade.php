@props([
    'devices' => [],
    'allDevices' => null,
])

@php
    if (!function_exists('getDeviceImage')) {
        function getDeviceImage($device): string {
            return match(strtolower($device)){
                'mobile' => asset('vendor/request-analytics/devices/smartphone.png'),
                'tablet' => asset('vendor/request-analytics/devices/ipad.png'),
                'desktop' => asset('vendor/request-analytics/devices/laptop.png'),
                'tv' => asset('vendor/request-analytics/devices/tv.png'),
                default => asset('vendor/request-analytics/devices/unknown.png'),
            };
        }
    }

    $displayDevices = array_slice($devices, 0, 5);
    $allDevicesData = $allDevices ?? $devices;
    $hasMore = count($allDevicesData) > 5;
    
    $modalItems = collect($allDevicesData)->map(function($device) {
        return [
            'label' => $device['name'],
            'count' => $device['count'],
            'percentage' => $device['percentage'],
            'imgSrc' => getDeviceImage($device['name'])
        ];
    })->toArray();
@endphp

<x-request-analytics::stats.list 
    primaryLabel="Devices" 
    secondaryLabel=""
    :showViewAll="$hasMore"
    modalTitle="All Devices"
    :allItems="$modalItems"
    modalId="devices-modal"
>
    @forelse($displayDevices as $device)
        <x-request-analytics::stats.item
            label="{{ $device['name'] }}"
            count="{{ $device['count'] }}"
            percentage="{{ $device['percentage'] }}"
            imgSrc="{{ getDeviceImage($device['name']) }}"
        />
    @empty
        <p class="text-sm text-gray-500 text-center py-5">No devices</p>
    @endforelse
</x-request-analytics::stats.list>
