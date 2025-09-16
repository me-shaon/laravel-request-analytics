@props([
    'operatingSystems' => [],
])

@php
    function getOperatingSystemImage($os): string {
        $normalizedOs = str_replace(' ', '', strtolower($os));

        // Handle Windows variants (Windows 10, Windows 7, etc.)
        if (str_starts_with($normalizedOs, 'windows')) {
            return asset('operating-systems/windows-logo.png');
        }

        return match($normalizedOs){
            'linux' => asset('operating-systems/linux.png'),
            'macosx' => asset('operating-systems/mac-logo.png'),
            'android' => asset('operating-systems/android-os.png'),
            'ios' => asset('operating-systems/iphone.png'),
            default => asset('operating-systems/unknown.png'),
        };
    }
@endphp

<x-request-analytics::stats.list primaryLabel="Os" secondaryLabel="Visitors">
    @forelse($operatingSystems as $os)
        <x-request-analytics::stats.item
            label="{{ $os['name'] }}"
            count="{{ $os['visitorCount'] }}"
            percentage="{{ $os['percentage'] }}"
            imgSrc="{{ getOperatingSystemImage($os['name']) }}"
        />
    @empty
        <p class="text-sm text-gray-500 text-center py-5">No operating systems</p>
    @endforelse
</x-request-analytics::stats.list>
