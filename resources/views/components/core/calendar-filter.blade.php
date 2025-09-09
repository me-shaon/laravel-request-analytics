@props([
    'dateRange' => 30,
    'startDate' => null,
    'endDate' => null
])

@php
    $currentDate = now();
    $presetRanges = [
        'last_24_hours' => ['label' => 'Last 24 hours', 'key' => 'D'],
        'last_7_days' => ['label' => 'Last 7 days', 'key' => 'W'],
        'last_30_days' => ['label' => 'Last 30 days', 'key' => 'T'],
        'last_3_months' => ['label' => 'Last 3 months', 'key' => 'D'],
        'last_12_months' => ['label' => 'Last 12 months', 'key' => 'A'],
        'month_to_date' => ['label' => 'Month to Date', 'key' => 'M'],
        'quarter_to_date' => ['label' => 'Quarter to Date', 'key' => 'Q'],
        'year_to_date' => ['label' => 'Year to Date', 'key' => 'A']
    ];
@endphp

<div class="relative" x-data="calendarFilter()" x-init="init()">
    <!-- Main Filter Button -->
    <div class="flex items-center gap-2">
        <button
            type="button"
            @click="showPresets = !showPresets"
            class="inline-flex items-center gap-2 bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-10 hover:bg-gray-50"
        >
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span x-text="currentLabel"></span>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>

    <!-- Dropdown Overlay -->
    <div
        x-show="showPresets"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        @click.away="showPresets = false"
        class="absolute right-0 top-full mt-2 bg-white rounded-lg shadow-lg border border-gray-200 z-50 min-w-[800px]"
        x-cloak
    >
        <div class="flex">
            <!-- Left: Date Range Presets -->
            <div class="w-64 p-4 border-r border-gray-200">
                @foreach($presetRanges as $key => $range)
                <button
                    type="button"
                    @click="selectPreset('{{ $key }}')"
                    :class="selectedPreset === '{{ $key }}' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
                    class="w-full text-left px-3 py-2 rounded-lg text-sm mb-1 flex items-center justify-between"
                >
                    <span>{{ $range['label'] }}</span>
                    <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded">{{ $range['key'] }}</span>
                </button>
                @endforeach
            </div>

            <!-- Right: Calendar -->
            <div class="flex-1 p-4">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-4">
                        <!-- Previous Month -->
                        <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-100 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        
                        <!-- Current Month/Year -->
                        <h3 class="text-lg font-semibold" x-text="currentMonthYear"></h3>
                        
                        <!-- Next Month -->
                        <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-100 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-1 mb-4">
                    <!-- Days of week -->
                    <template x-for="day in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']">
                        <div class="h-8 flex items-center justify-center text-xs font-medium text-gray-500" x-text="day"></div>
                    </template>
                    
                    <!-- Calendar days -->
                    <template x-for="day in calendarDays" :key="day.date">
                        <button
                            type="button"
                            @click="selectDate(day.date)"
                            :disabled="day.isDisabled"
                            :class="{
                                'bg-blue-600 text-white': day.isSelected,
                                'bg-blue-100 text-blue-600': day.isInRange && !day.isSelected,
                                'text-gray-300': day.isOtherMonth,
                                'text-gray-900': !day.isOtherMonth && !day.isSelected && !day.isInRange,
                                'hover:bg-blue-50': !day.isSelected && !day.isDisabled && !day.isOtherMonth,
                                'cursor-not-allowed opacity-50': day.isDisabled
                            }"
                            class="h-8 w-8 flex items-center justify-center text-sm rounded-full transition-colors"
                            x-text="day.day"
                        ></button>
                    </template>
                </div>

                <!-- Apply/Cancel buttons -->
                <div class="flex justify-end gap-2 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        @click="cancel()"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="apply()"
                        class="px-4 py-2 text-sm bg-blue-600 text-white hover:bg-blue-700 rounded-lg"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden inputs for form submission -->
    <input type="hidden" name="start_date" x-model="startDate">
    <input type="hidden" name="end_date" x-model="endDate">
</div>

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function calendarFilter() {
    return {
        showPresets: false,
        selectedPreset: 'last_30_days',
        currentLabel: 'Last 30 days',
        startDate: '{{ $startDate }}',
        endDate: '{{ $endDate }}',
        tempStartDate: null,
        tempEndDate: null,
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        calendarDays: [],
        
        init() {
            this.updateCurrentLabel();
            this.generateCalendar();
        },
        
        get currentMonthYear() {
            const date = new Date(this.currentYear, this.currentMonth);
            return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },
        
        selectPreset(preset) {
            this.selectedPreset = preset;
            const now = new Date();
            
            switch(preset) {
                case 'last_24_hours':
                    this.tempStartDate = new Date(now.getTime() - 24*60*60*1000);
                    this.tempEndDate = now;
                    break;
                case 'last_7_days':
                    this.tempStartDate = new Date(now.getTime() - 7*24*60*60*1000);
                    this.tempEndDate = now;
                    break;
                case 'last_30_days':
                    this.tempStartDate = new Date(now.getTime() - 30*24*60*60*1000);
                    this.tempEndDate = now;
                    break;
                case 'last_3_months':
                    this.tempStartDate = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
                    this.tempEndDate = now;
                    break;
                case 'last_12_months':
                    this.tempStartDate = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
                    this.tempEndDate = now;
                    break;
                case 'month_to_date':
                    this.tempStartDate = new Date(now.getFullYear(), now.getMonth(), 1);
                    this.tempEndDate = now;
                    break;
                case 'quarter_to_date':
                    const quarter = Math.floor(now.getMonth() / 3);
                    this.tempStartDate = new Date(now.getFullYear(), quarter * 3, 1);
                    this.tempEndDate = now;
                    break;
                case 'year_to_date':
                    this.tempStartDate = new Date(now.getFullYear(), 0, 1);
                    this.tempEndDate = now;
                    break;
            }
            this.generateCalendar();
        },
        
        selectDate(date) {
            if (!this.tempStartDate || (this.tempStartDate && this.tempEndDate)) {
                this.tempStartDate = new Date(date);
                this.tempEndDate = null;
                this.selectedPreset = 'custom';
            } else if (this.tempStartDate && !this.tempEndDate) {
                if (date >= this.tempStartDate) {
                    this.tempEndDate = new Date(date);
                } else {
                    this.tempEndDate = this.tempStartDate;
                    this.tempStartDate = new Date(date);
                }
            }
            this.generateCalendar();
        },
        
        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
            this.generateCalendar();
        },
        
        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
            this.generateCalendar();
        },
        
        generateCalendar() {
            this.calendarDays = [];
            
            const firstDay = new Date(this.currentYear, this.currentMonth, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                this.calendarDays.push({
                    date: date,
                    day: date.getDate(),
                    isOtherMonth: date.getMonth() !== this.currentMonth,
                    isSelected: this.isDateSelected(date),
                    isInRange: this.isDateInRange(date),
                    isDisabled: date > new Date()
                });
            }
        },
        
        isDateSelected(date) {
            if (!this.tempStartDate) return false;
            const dateStr = this.formatDate(date);
            const startStr = this.formatDate(this.tempStartDate);
            const endStr = this.tempEndDate ? this.formatDate(this.tempEndDate) : null;
            
            return dateStr === startStr || (endStr && dateStr === endStr);
        },
        
        isDateInRange(date) {
            if (!this.tempStartDate || !this.tempEndDate) return false;
            return date >= this.tempStartDate && date <= this.tempEndDate;
        },
        
        formatDate(date) {
            return date.toISOString().split('T')[0];
        },
        
        updateCurrentLabel() {
            if (this.startDate && this.endDate) {
                const start = new Date(this.startDate);
                const end = new Date(this.endDate);
                this.currentLabel = `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`;
            } else {
                this.currentLabel = 'Last 30 days';
            }
        },
        
        apply() {
            if (this.tempStartDate) {
                this.startDate = this.formatDate(this.tempStartDate);
                this.endDate = this.tempEndDate ? this.formatDate(this.tempEndDate) : this.formatDate(this.tempStartDate);
                this.updateCurrentLabel();
                
                // Remove the automatic form submission
                // this.$el.closest('form').submit();
            }
            this.showPresets = false;
        },
        
        cancel() {
            this.tempStartDate = this.startDate ? new Date(this.startDate) : null;
            this.tempEndDate = this.endDate ? new Date(this.endDate) : null;
            this.showPresets = false;
        }
    }
}
</script>
@endpush

<style>
[x-cloak] {
    display: none !important;
}
</style>