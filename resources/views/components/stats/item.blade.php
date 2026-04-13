@props([
    'label',
    'count' => 0,
    'percentage' => 0,
    'imgSrc' => null,
])

<div class="flex items-center justify-between py-2 px-1 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
    <div class="flex items-center gap-3 flex-1 min-w-0">
        @if (!is_null($imgSrc))
            <div class="flex-shrink-0">
                <img alt="icon" class="w-5 h-5 rounded object-cover" src="{{ $imgSrc }}"/>
            </div>
        @endif
        <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $label }}</span>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
        <span class="font-semibold text-sm text-gray-900 dark:text-gray-100">{{ $count }}</span>
        <div class="flex items-center gap-2">
            <div class="w-12 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-blue-500 dark:bg-blue-400 rounded-full" style="width: {{ $percentage }}%"></div>
            </div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-8 text-right">{{ $percentage }}%</span>
        </div>
    </div>
</div>
