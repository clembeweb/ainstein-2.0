@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-2xl font-bold text-gray-900">API Key Details</h2>
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
                        <div class="mt-2 text-sm text-gray-500">
                            Created {{ $apiKey->created_at->format('M j, Y g:i A') }}
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($isActive)
                            <form method="POST" action="{{ route('tenant.api-keys.revoke', $apiKey) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="btn-danger"
                                        onclick="return confirm('Are you sure you want to revoke this API key?')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                                    </svg>
                                    Revoke Key
                                </button>
                            </form>
                        @elseif(!$isExpired)
                            <form method="POST" action="{{ route('tenant.api-keys.activate', $apiKey) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="btn-success"
                                        onclick="return confirm('Are you sure you want to activate this API key?')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Activate Key
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('tenant.api-keys.index') }}" class="btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back to API Keys
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- API Key Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">API Key Information</h3>

                        <!-- API Key Display -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                                <div class="flex items-center justify-between">
                                    <code class="text-sm font-mono text-gray-900 break-all mr-4">{{ $apiKey->key_prefix }}••••••••••••••••••••••••••••••••</code>
                                    <button onclick="copyApiKey('{{ $apiKey->key_prefix }}', this)" class="btn-secondary text-sm">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        Copy Key
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Keep this key secure and do not share it publicly.</p>
                        </div>

                        <!-- Key Details -->
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Key ID</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $apiKey->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($isActive)
                                        <span class="text-green-600">Active</span>
                                    @elseif($isExpired)
                                        <span class="text-red-600">Expired</span>
                                    @else
                                        <span class="text-gray-600">Inactive</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $apiKey->created_at->format('M j, Y g:i A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Used</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $apiKey->last_used_at ? $apiKey->last_used_at->format('M j, Y g:i A') : 'Never' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Expires</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $apiKey->expires_at ? $apiKey->expires_at->format('M j, Y g:i A') : 'Never' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Requests</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ number_format($apiKey->usage_count) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Usage Examples -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Usage Examples</h3>
                        <div class="space-y-6">
                            <!-- cURL Example -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">cURL Example</h4>
                                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto">
                                    <pre class="text-sm"><code>curl -X POST {{ url('/api/v1/content/generate') }} \
  -H "Authorization: Bearer {{ $apiKey->key_prefix }}••••••••••••••••••••••••••••••••" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt_id": "your_prompt_id",
    "variables": {
      "topic": "AI Technology",
      "length": "1000"
    },
    "page_id": "your_page_id"
  }'</code></pre>
                                </div>
                            </div>

                            <!-- JavaScript Example -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">JavaScript Example</h4>
                                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto">
                                    <pre class="text-sm"><code>const response = await fetch('{{ url('/api/v1/content/generate') }}', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer {{ $apiKey->key_prefix }}••••••••••••••••••••••••••••••••',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    prompt_id: 'your_prompt_id',
    variables: {
      topic: 'AI Technology',
      length: '1000'
    },
    page_id: 'your_page_id'
  })
});

const result = await response.json();</code></pre>
                                </div>
                            </div>

                            <!-- Python Example -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">Python Example</h4>
                                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto">
                                    <pre class="text-sm"><code>import requests

url = "{{ url('/api/v1/content/generate') }}"
headers = {
    "Authorization": "Bearer {{ $apiKey->key_prefix }}••••••••••••••••••••••••••••••••",
    "Content-Type": "application/json"
}
data = {
    "prompt_id": "your_prompt_id",
    "variables": {
        "topic": "AI Technology",
        "length": "1000"
    },
    "page_id": "your_page_id"
}

response = requests.post(url, headers=headers, json=data)
result = response.json()</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Usage Statistics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Usage Statistics</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Total Requests</span>
                                <span class="text-sm font-medium text-gray-900">{{ number_format($apiKey->usage_count) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">This Month</span>
                                <span class="text-sm font-medium text-gray-900">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">This Week</span>
                                <span class="text-sm font-medium text-gray-900">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Today</span>
                                <span class="text-sm font-medium text-gray-900">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="{{ route('tenant.api-keys.usage', $apiKey) }}" class="w-full btn-primary text-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                View Usage Details
                            </a>

                            @if($isActive)
                                <form method="POST" action="{{ route('tenant.api-keys.revoke', $apiKey) }}" class="w-full">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="w-full btn-secondary text-red-600 hover:text-red-800 text-center"
                                            onclick="return confirm('Are you sure you want to revoke this API key?')">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                                        </svg>
                                        Revoke Key
                                    </button>
                                </form>
                            @elseif(!$isExpired)
                                <form method="POST" action="{{ route('tenant.api-keys.activate', $apiKey) }}" class="w-full">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="w-full btn-success text-center"
                                            onclick="return confirm('Are you sure you want to activate this API key?')">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Activate Key
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('tenant.api-keys.destroy', $apiKey) }}" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full btn-danger text-center"
                                        onclick="return confirm('Are you sure you want to permanently delete this API key? This action cannot be undone.')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete Key
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security Tips -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-yellow-900 mb-4">Security Best Practices</h3>
                    <ul class="space-y-2 text-sm text-yellow-800">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Keep your API key secure and never share it publicly
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Regenerate keys regularly for enhanced security
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Monitor your API usage for unusual activity
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Revoke keys immediately if compromised
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyApiKey(prefix, button) {
    // In a real implementation, you would copy the full API key
    // For security, we're only showing the prefix in the demo
    const fullKey = prefix + '••••••••••••••••••••••••••••••••';
    navigator.clipboard.writeText(fullKey + ' (demo - full key would be copied)').then(function() {
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Copied!';
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