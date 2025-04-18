@props([
    'browsers' => [],
])

@php
    function getBrowserImage($browser): string {
        return match(strtolower($browser)){
            'chrome' =>  asset('browsers/chrome.png'),
            'firefox' => asset('browsers/firefox.png'),
            'safari' => asset('browsers/safari.png'),
            'edge' => asset('browsers/microsoft-edge.png'),
            default => asset('browsers/unknown.png'),
        };
    }
@endphp

<x-request-analytics::stats.list primaryLabel="Browser" secondaryLabel="Visitors">
    @forelse($browsers as $browser)
        <x-request-analytics::stats.item
            label="{{ $browser['browser'] }}"
            count="{{ $browser['visitorCount'] }}"
            percentage="{{ $browser['percentage'] }}"
            imgSrc="{{ getBrowserImage($browser['browser']) }}"
        />
    @empty
        <p class="text-sm text-gray-500 text-center py-5">No browsers</p>
    @endforelse
</x-request-analytics::stats.list>
