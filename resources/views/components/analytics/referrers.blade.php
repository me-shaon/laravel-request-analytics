@props([
    'referrers' => [],
    'allReferrers' => null,
])

@php
    $displayReferrers = array_slice($referrers, 0, 5);
    $allReferrersData = $allReferrers ?? $referrers;
    $hasMore = count($allReferrersData) > 5;
    
    $modalItems = collect($allReferrersData)->map(function($referrer) {
        return [
            'label' => $referrer['domain'],
            'count' => $referrer['visits'],
            'percentage' => $referrer['percentage'],
            'imgSrc' => null
        ];
    })->toArray();
@endphp

<x-request-analytics::stats.list 
    primaryLabel="Referrers" 
    secondaryLabel="Views"
    :showViewAll="$hasMore"
    modalTitle="All Referrers"
    :allItems="$modalItems"
    modalId="referrers-modal"
>
    @forelse($displayReferrers as $referrer)
        <x-request-analytics::stats.item
            label="{{ $referrer['domain'] }}"
            count="{{ $referrer['visits'] }}"
            percentage="{{ $referrer['percentage'] }}"
        />
    @empty
        <p class="text-sm text-gray-500 text-center py-5">No referrers</p>
    @endforelse
</x-request-analytics::stats.list>
