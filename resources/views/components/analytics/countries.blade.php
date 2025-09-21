@props([
    'countries' => [],
    'allCountries' => null,
])

@php
    $displayCountries = array_slice($countries, 0, 5);
    $allCountriesData = $allCountries ?? $countries;
    $hasMore = count($allCountriesData) > 5;
    
    $modalItems = collect($allCountriesData)->map(function($country) {
        return [
            'label' => $country['name'],
            'count' => $country['count'],
            'percentage' => $country['percentage'],
            'imgSrc' => "https://www.worldatlas.com/r/w236/img/flag/{$country['code']}-flag.jpg"
        ];
    })->toArray();
@endphp

<x-request-analytics::stats.list 
    primaryLabel="Countries" 
    secondaryLabel=""
    :showViewAll="$hasMore"
    modalTitle="All Countries"
    :allItems="$modalItems"
    modalId="countries-modal"
>
    @forelse($displayCountries as $country)
        <x-request-analytics::stats.item
                label="{{ $country['name'] }}"
                count="{{ $country['count'] }}"
                percentage="{{ $country['percentage'] }}"
                imgSrc="https://www.worldatlas.com/r/w236/img/flag/{{$country['code']}}-flag.jpg"
        />
    @empty
        <p class="text-sm text-gray-500 text-center py-5">No countries</p>
    @endforelse
</x-request-analytics::stats.list>
