<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue (Est.)</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    ${{ number_format(\App\Models\Tenant::where('status', 'active')->count() * 29, 0) }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Based on active subscriptions</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Subscriptions</h3>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">
                    {{ \App\Models\Tenant::where('status', 'active')->count() }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Currently active tenants</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Trial Accounts</h3>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                    {{ \App\Models\Tenant::where('status', 'trial')->count() }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">In trial period</p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
