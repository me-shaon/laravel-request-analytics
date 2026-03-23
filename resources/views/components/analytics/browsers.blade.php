@props([
    'browsers' => [],
    'allBrowsers' => null,
])

@php
    if (!function_exists('getBrowserImage')) {
        function getBrowserImage($browser): string {
            return match(strtolower($browser)){
                'chrome' =>  asset('vendor/request-analytics/browsers/chrome.png'),
                'firefox' => asset('vendor/request-analytics/browsers/firefox.png'),
                'safari' => asset('vendor/request-analytics/browsers/safari.png'),
                'edge' => asset('vendor/request-analytics/browsers/ms-edge.png'),
                default => asset('vendor/request-analytics/browsers/unknown.png'),
            };
        }
    }

    $displayBrowsers = array_slice($browsers, 0, 5);
    $allBrowsersData = $allBrowsers ?? $browsers;
    $hasMore = count($allBrowsersData) > 5;
    
    $modalItems = collect($allBrowsersData)->map(function($browser) {
        return [
            'label' => $browser['browser'],
            'count' => $browser['count'],
            'percentage' => $browser['percentage'],
            'imgSrc' => getBrowserImage($browser['browser'])
        ];
    })->toArray();
@endphp

<x-request-analytics::stats.list 
    primaryLabel="Browser" 
    secondaryLabel=""
    :showViewAll="$hasMore"
    modalTitle="All Browsers"
    :allItems="$modalItems"
    modalId="browsers-modal"
>
    @forelse($displayBrowsers as $browser)
        <x-request-analytics::stats.item
            label="{{ $browser['browser'] }}"
            count="{{ $browser['count'] }}"
            percentage="{{ $browser['percentage'] }}"
            imgSrc="{{ getBrowserImage($browser['browser']) }}"
        />
    @empty
        <p class="text-sm text-gray-500 text-center py-5">No browsers</p>
    @endforelse
</x-request-analytics::stats.list>
