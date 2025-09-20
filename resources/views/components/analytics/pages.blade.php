@props([
    'pages' => [],
    'allPages' => null,
])

@php
    $displayPages = array_slice($pages, 0, 5);
    $allPagesData = $allPages ?? $pages;
    $hasMore = count($allPagesData) > 5;
    
    $modalItems = collect($allPagesData)->map(function($page) {
        return [
            'label' => $page['path'],
            'count' => $page['views'],
            'percentage' => $page['percentage'],
            'imgSrc' => null
        ];
    })->toArray();
@endphp

<x-request-analytics::stats.list 
    primaryLabel="Pages" 
    secondaryLabel="Views"
    :showViewAll="$hasMore"
    modalTitle="All Pages"
    :allItems="$modalItems"
    modalId="pages-modal"
>
    @forelse($displayPages as $page)
        <x-request-analytics::stats.item
            label="{{ $page['path'] }}"
            count="{{ $page['views'] }}"
            percentage="{{ $page['percentage'] }}"
        />
    @empty
        <p class="text-sm text-gray-500 text-center py-5">No pages</p>
    @endforelse
</x-request-analytics::stats.list>
