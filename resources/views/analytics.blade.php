<x-request-analytics::layouts.app>
    <main class="container px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div class="w-full sm:w-auto">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">Analytics Dashboard</h1>
                <p class="text-sm text-gray-500">Track your website performance and user insights</p>
            </div>
            <div class="w-full sm:w-auto">
                <form method="GET" action="{{ route(config('request-analytics.route.name')) }}" class="flex items-center gap-2 flex-wrap">
                    <x-request-analytics::core.calendar-filter 
                        :dateRange="$dateRange" 
                        :startDate="request('start_date')"
                        :endDate="request('end_date')"
                    />
                    <select name="request_category" class="bg-white border border-gray-300 rounded-lg px-3 py-2.5 pr-6 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-10">
                        <option value="" {{ !request('request_category') ? 'selected' : '' }}>All Requests</option>
                        <option value="web" {{ request('request_category') == 'web' ? 'selected' : '' }}>Web Only</option>
                        <option value="api" {{ request('request_category') == 'api' ? 'selected' : '' }}>API Only</option>
                    </select>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors h-10">
                        Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
                <x-request-analytics::stats.count label="Views" :value='$average["views"]'/>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
                <x-request-analytics::stats.count label="Visitors" :value='$average["visitors"]'/>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
                <x-request-analytics::stats.count label="Bounce Rate" :value='$average["bounce_rate"]'/>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-200">
                <x-request-analytics::stats.count label="Average Visit Time" :value='$average["average_visit_time"]'/>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Traffic Overview</h2>
                <p class="text-sm text-gray-500">Daily visitor and page view trends</p>
            </div>
            <x-request-analytics::stats.chart :labels='$labels' :datasets='$datasets' type="line"/>
        </div>

        <!-- Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <x-request-analytics::analytics.pages :pages='$pages'/>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <x-request-analytics::analytics.referrers :referrers='$referrers'/>
            </div>
        </div>

        <!-- Additional Analytics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <x-request-analytics::analytics.broswers :browsers='$browsers'/>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <x-request-analytics::analytics.operating-systems :operatingSystems='$operatingSystems'/>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <x-request-analytics::analytics.devices :devices='$devices'/>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <x-request-analytics::analytics.countries :countries='$countries'/>
            </div>
        </div>
    </main>
</x-request-analytics::layouts.app>
