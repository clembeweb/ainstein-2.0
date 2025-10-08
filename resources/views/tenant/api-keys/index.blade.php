@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">API Keys Management</h2>
                        <p class="text-gray-600 mt-1">Manage your API keys for accessing the Ainstein Platform</p>
                    </div>
                    <form method="POST" action="{{ route('tenant.api-keys.generate') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-primary" onclick="return confirm('Are you sure you want to generate a new API key?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Generate New Key
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('tenant.api-keys.index') }}" class="flex space-x-4">
                    <div class="flex-1">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Filter
                        </button>
                        <a href="{{ route('tenant.api-keys.index') }}" class="btn-secondary">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- API Keys List -->
        <div class="space-y-4">
            @forelse($apiKeys as $apiKey)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ $apiKey->name ?? 'API Key' }}
                                    </h3>
                                    @php
                                        $isExpired = $apiKey->expires_at && $apiKey->expires_at->isPast();
                                        $isActive = $apiKey->is_active && !$isExpired;
                                    @endphp
                                    @if($isActive)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @elseif($isExpired)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Expired
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    @endif
                                </div>

                                <!-- API Key Display -->
                                <div class="bg-gray-50 border border-gray-200 rounded-md p-3 mb-3">
                                    <div class="flex items-center justify-between">
                                        <code class="text-sm font-mono text-gray-900 break-all mr-4">{{ $apiKey->key_prefix }}••••••••••••••••••••••••••••••••</code>
                                        <button onclick="copyToClipboard('{{ $apiKey->key_prefix }}', this)" class="btn-secondary text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            Copy
                                        </button>
                                    </div>
                                </div>

                                <!-- Metadata -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                    <div>
                                        <span class="font-medium">Created:</span> {{ $apiKey->created_at->format('M j, Y g:i A') }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Last Used:</span>
                                        {{ $apiKey->last_used_at ? $apiKey->last_used_at->format('M j, Y g:i A') : 'Never' }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Expires:</span>
                                        {{ $apiKey->expires_at ? $apiKey->expires_at->format('M j, Y') : 'Never' }}
                                    </div>
                                </div>

                                @if($apiKey->usage_count > 0)
                                    <div class="mt-3 text-sm text-gray-600">
                                        <span class="font-medium">Usage:</span> {{ number_format($apiKey->usage_count) }} requests
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center space-x-2 ml-6">
                                <a href="{{ route('tenant.api-keys.show', $apiKey) }}" class="btn-secondary text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View Details
                                </a>

                                @if($isActive)
                                    <form method="POST" action="{{ route('tenant.api-keys.revoke', $apiKey) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn-secondary text-sm text-red-600 hover:text-red-800"
                                                onclick="return confirm('Are you sure you want to revoke this API key?')">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                                            </svg>
                                            Revoke
                                        </button>
                                    </form>
                                @elseif(!$isExpired)
                                    <form method="POST" action="{{ route('tenant.api-keys.activate', $apiKey) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn-secondary text-sm text-green-600 hover:text-green-800"
                                                onclick="return confirm('Are you sure you want to activate this API key?')">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Activate
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('tenant.api-keys.destroy', $apiKey) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn-danger text-sm"
                                            onclick="return confirm('Are you sure you want to permanently delete this API key? This action cannot be undone.')">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No API keys found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by generating your first API key.</p>
                        <div class="mt-6">
                            <form method="POST" action="{{ route('tenant.api-keys.generate') }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-primary" onclick="return confirm('Are you sure you want to generate a new API key?')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Generate API Key
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if(method_exists($apiKeys, 'hasPages') && $apiKeys->hasPages())
            <div class="mt-6">
                {{ $apiKeys->appends(request()->query())->links() }}
            </div>
        @endif

        <!-- Quick Start Guide -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-4">Quick Start Guide</h3>
            <div class="space-y-4 text-sm text-blue-800">
                <div>
                    <h4 class="font-semibold">1. Generate an API Key</h4>
                    <p>Click "Generate New Key" to create your first API key for accessing the platform.</p>
                </div>
                <div>
                    <h4 class="font-semibold">2. Authentication</h4>
                    <p>Include your API key in the Authorization header: <code class="bg-blue-100 px-2 py-1 rounded">Authorization: Bearer YOUR_API_KEY</code></p>
                </div>
                <div>
                    <h4 class="font-semibold">3. Make API Calls</h4>
                    <p>Use your API key to access endpoints like content generation, pages management, and more.</p>
                </div>
                <div>
                    <h4 class="font-semibold">4. Monitor Usage</h4>
                    <p>View detailed usage statistics and manage your keys from this dashboard.</p>
                </div>
            </div>
            <div class="mt-4">
                <a href="/api/docs" class="text-blue-700 hover:text-blue-900 font-medium">
                    View API Documentation →
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text, button) {
    // In a real implementation, you would copy the full API key
    // For security, we're only showing the prefix in the demo
    navigator.clipboard.writeText(text + '... (full key would be copied)').then(function() {
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Copied!';
        button.classList.remove('btn-secondary');
        button.classList.add('btn-success');

        setTimeout(function() {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-secondary');
        }, 2000);
    }).catch(function() {
        alert('Failed to copy API key. Please select and copy manually.');
    });
}
</script>
@endsection