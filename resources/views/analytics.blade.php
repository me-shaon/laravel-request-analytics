<x-request-analytics::layouts.app>
    <main class="container px-4 sm:px-6 lg:px-8 py-8" x-data="{ darkMode: localStorage.theme === 'dark' }">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div class="w-full sm:w-auto">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Analytics Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Track your website performance and user insights</p>
            </div>
            <div class="w-full sm:w-auto flex items-center gap-3">
                <!-- Dark Mode Toggle -->
                <button 
                    @click="darkMode = !darkMode; localStorage.theme = darkMode ? 'dark' : 'light'; document.documentElement.classList.toggle('dark', darkMode)"
                    class="flex items-center justify-center w-10 h-10 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    title="Toggle dark mode"
                >
                    <!-- Sun Icon -->
                    <svg x-show="darkMode" class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon -->
                    <svg x-show="!darkMode" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <form method="GET" action="{{ route(config('request-analytics.route.name')) }}" class="flex items-center gap-2 flex-wrap">
                    <x-request-analytics::core.calendar-filter
                        :dateRange="$dateRange"
                        :startDate="request('start_date')"
                        :endDate="request('end_date')"
                    />
                    <select name="request_category" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 pr-6 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-10">
                        <option value="" {{ !request('request_category') ? 'selected' : '' }}>All Requests</option>
                        <option value="web" {{ request('request_category') == 'web' ? 'selected' : '' }}>Web Only</option>
                        <option value="api" {{ request('request_category') == 'api' ? 'selected' : '' }}>API Only</option>
                    </select>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:ring-offset-gray-900 transition-colors h-10">
                        Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-gray-900/50 transition-shadow duration-200">
                <x-request-analytics::stats.count label="Views" :value='$average["views"]'/>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-gray-900/50 transition-shadow duration-200">
                <x-request-analytics::stats.count label="Visitors" :value='$average["visitors"]'/>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-gray-900/50 transition-shadow duration-200">
                <x-request-analytics::stats.count label="Bounce Rate" :value='$average["bounce_rate"]'/>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-gray-900/50 transition-shadow duration-200">
                <x-request-analytics::stats.count label="Average Visit Time" :value='$average["average_visit_time"]'/>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-8">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Traffic Overview</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Daily visitor and page view trends</p>
            </div>
            <x-request-analytics::stats.chart :labels='$labels' :datasets='$datasets' type="line"/>
        </div>

        <!-- Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 ">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <x-request-analytics::analytics.pages :pages='$pages'/>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <x-request-analytics::analytics.referrers :referrers='$referrers'/>
            </div>
        </div>

        <!-- Additional Analytics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <x-request-analytics::analytics.broswers :browsers='$browsers'/>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <x-request-analytics::analytics.operating-systems :operatingSystems='$operatingSystems'/>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <x-request-analytics::analytics.devices :devices='$devices'/>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <x-request-analytics::analytics.countries :countries='$countries'/>
            </div>
        </div>
    </main>
</x-request-analytics::layouts.app>
