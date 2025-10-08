@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="space-y-6">
        <!-- Tenant Information -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Tenant Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tenant Name</label>
                    <input type="text" value="{{ $tenant->name }}" readonly
                           class="w-full rounded-lg border-gray-300 bg-gray-50 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($tenant->status) }}
                    </span>
                </div>

                @if($tenant->domain)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Domain</label>
                        <input type="text" value="{{ $tenant->domain }}" readonly
                               class="w-full rounded-lg border-gray-300 bg-gray-50 focus:border-amber-500 focus:ring-amber-500">
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Created</label>
                    <input type="text" value="{{ $tenant->created_at->format('M j, Y') }}" readonly
                           class="w-full rounded-lg border-gray-300 bg-gray-50 focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>
        </div>
    </div>

    <!-- Token Usage -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Token Usage</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($tenant->tokens_used_current) }}</div>
                    <div class="text-gray-600">Current Usage</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ number_format($tenant->tokens_monthly_limit - $tenant->tokens_used_current) }}</div>
                    <div class="text-gray-600">Remaining</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-600">{{ number_format($tenant->tokens_monthly_limit) }}</div>
                    <div class="text-gray-600">Monthly Limit</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Usage Progress</span>
                    <span>{{ number_format(($tenant->tokens_used_current / $tenant->tokens_monthly_limit) * 100, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-amber-500 h-3 rounded-full transition-all duration-300"
                         style="width: {{ min(($tenant->tokens_used_current / $tenant->tokens_monthly_limit) * 100, 100) }}%"></div>
                </div>
            </div>

            <div class="text-sm text-gray-600">
                <p>Your token usage resets on the 1st of each month.</p>
                <p>Contact support if you need to upgrade your token limit.</p>
            </div>
        </div>
    </div>

    <!-- User Profile -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">User Profile</h3>
        </div>
        <form class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" value="{{ $user->name }}"
                           class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" value="{{ $user->email }}"
                           class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" readonly
                           class="w-full rounded-lg border-gray-300 bg-gray-50 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            @if($user->last_login)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Login</label>
                    <input type="text" value="{{ $user->last_login->format('M j, Y \a\t H:i') }}" readonly
                           class="w-full rounded-lg border-gray-300 bg-gray-50 focus:border-amber-500 focus:ring-amber-500">
                </div>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium">
                    Update Profile
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
        </div>
        <form class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                <input type="password" name="current_password"
                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="password"
                           class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation"
                           class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
                    Change Password
                </button>
            </div>
        </form>
    </div>

    <!-- API Information -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">API Access</h3>
        </div>
        <div class="p-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    <p class="text-sm text-blue-800">Use the API to integrate content generation into your applications.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Endpoint</label>
                    <div class="flex">
                        <input type="text" value="{{ url('/api') }}" readonly
                               class="flex-1 rounded-l-lg border-gray-300 bg-gray-50 focus:border-amber-500 focus:ring-amber-500">
                        <button onclick="copyToClipboard('api-endpoint')" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-r-lg">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div id="api-endpoint" class="hidden">{{ url('/api') }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current API Token</label>
                    <div class="flex">
                        <input type="password" value="********************************" readonly
                               class="flex-1 rounded-l-lg border-gray-300 bg-gray-50 focus:border-amber-500 focus:ring-amber-500">
                        <button class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-r-lg">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i>Generate New Token
                    </button>
                    <a href="{{ route('api.docs') }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-book mr-2"></i>API Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-lg shadow border border-red-200">
        <div class="px-6 py-4 border-b border-red-200">
            <h3 class="text-lg font-semibold text-red-800">Danger Zone</h3>
        </div>
        <div class="p-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    <p class="text-sm text-red-800">These actions cannot be undone. Please be certain.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-medium text-gray-900">Reset All Data</h4>
                        <p class="text-sm text-gray-600">Delete all pages, content generations, and prompts. This cannot be undone.</p>
                    </div>
                    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                        Reset Data
                    </button>
                </div>

                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-medium text-gray-900">Delete Account</h4>
                        <p class="text-sm text-gray-600">Permanently delete your account and all associated data.</p>
                    </div>
                    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;

    navigator.clipboard.writeText(text).then(function() {
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.remove('bg-gray-600', 'hover:bg-gray-700');
        button.classList.add('bg-green-600', 'hover:bg-green-700');

        setTimeout(function() {
            button.innerHTML = originalHtml;
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-gray-600', 'hover:bg-gray-700');
        }, 2000);
    });
}
</script>
@endsection