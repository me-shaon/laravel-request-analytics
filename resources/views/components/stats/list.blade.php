@props([
    'primaryLabel',
    'secondaryLabel',
    'footer' => null,
    'showViewAll' => false,
    'modalTitle' => null,
    'allItems' => [],
    'modalId' => null
])

@php
    $modalId = $modalId ?? 'modal-' . Str::random(8);
    $modalTitle = $modalTitle ?? $primaryLabel;
@endphp

<div class="p-6 min-h-[400px] flex flex-col">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ $primaryLabel }}</h3>
        <span class="text-sm font-medium text-gray-500">{{ $secondaryLabel }}</span>
    </div>
    <div class="flex-1 flex flex-col space-y-3">
        {{ $slot }}
    </div>
    @if($footer)
        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $footer }}
        </div>
    @endif
    @if($showViewAll && count($allItems) > 0)
        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-center">
            <button
                type="button"
                onclick="openModal('{{ $modalId }}')"
                class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150"
            >
                View All
            </button>
        </div>
    @endif
</div>

@if($showViewAll && count($allItems) > 0)
<!-- Modal -->
<div id="{{ $modalId }}" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="modal-content bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
        <div class="modal-header flex items-center justify-between p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ $modalTitle }}</h2>
            <button 
                type="button" 
                onclick="closeModal('{{ $modalId }}')"
                class="text-gray-400 hover:text-gray-600 transition-colors duration-150"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body p-6 overflow-y-auto max-h-[60vh]">
            <div class="space-y-3">
                @foreach($allItems as $item)
                    <x-request-analytics::stats.item
                        :label="$item['label']"
                        :count="$item['count']"
                        :percentage="$item['percentage']"
                        :imgSrc="$item['imgSrc'] ?? null"
                    />
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

@once
<script>
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeModal(e.target.id);
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const visibleModal = document.querySelector('.modal-overlay:not(.hidden)');
        if (visibleModal) {
            closeModal(visibleModal.id);
        }
    }
});
</script>
@endonce
