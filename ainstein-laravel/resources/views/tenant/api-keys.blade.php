@extends('layouts.tenant')

@section('title', 'API Keys')
@section('page-title', 'API Keys')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">API Keys</h3>
                <p class="text-gray-600">Manage your API keys for programmatic access</p>
            </div>

            <div class="flex space-x-3">
                <button onclick="openCreateModal()" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-plus mr-2"></i>Create API Key
                </button>
            </div>
        </div>
    </div>

    <!-- API Keys List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Used</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($apiKeys as $apiKey)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $apiKey->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                                    ak_•••••••••••••••••••••••••••••••
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($apiKey->is_active && (!$apiKey->expires_at || $apiKey->expires_at->isFuture()))
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>Active
                                    </span>
                                @elseif($apiKey->expires_at && $apiKey->expires_at->isPast())
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times mr-1"></i>Revoked
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if($apiKey->last_used)
                                    {{ $apiKey->last_used->format('M j, Y H:i') }}
                                @else
                                    <span class="text-gray-400">Never</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if($apiKey->expires_at)
                                    {{ $apiKey->expires_at->format('M j, Y') }}
                                    @if($apiKey->expires_at->isPast())
                                        <span class="text-red-500">(Expired)</span>
                                    @endif
                                @else
                                    <span class="text-green-600">Never</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $apiKey->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                @if($apiKey->is_active)
                                    <button onclick="revokeApiKey('{{ $apiKey->id }}')" class="text-red-600 hover:text-red-700" title="Revoke">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-key text-4xl mb-4"></i>
                                    <p class="text-lg">No API keys found</p>
                                    <p class="text-sm">Create your first API key to get started</p>
                                    <button onclick="openCreateModal()" class="mt-4 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg font-medium">
                                        <i class="fas fa-plus mr-2"></i>Create API Key
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- API Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Using Your API Keys</h3>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                <p class="text-sm text-blue-800">Include your API key in the Authorization header of your requests.</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Endpoint</label>
                <div class="flex">
                    <input type="text" value="{{ url('/api') }}" readonly class="flex-1 rounded-l-lg border-gray-300 bg-gray-50">
                    <button onclick="copyToClipboard('{{ url('/api') }}')" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-r-lg">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Example Usage</label>
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono">
                    curl -X GET {{ url('/api/pages') }} \<br>
                    &nbsp;&nbsp;-H "Authorization: Bearer YOUR_API_KEY" \<br>
                    &nbsp;&nbsp;-H "Content-Type: application/json"
                </div>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('api.docs') }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-book mr-2"></i>API Documentation
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<div id="createApiKeyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create API Key</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="createApiKeyForm" onsubmit="createApiKey(event)">
                <div class="mb-4">
                    <label for="keyName" class="block text-sm font-medium text-gray-700 mb-2">Key Name</label>
                    <input type="text" id="keyName" name="name" required class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500" placeholder="e.g. Production API Key">
                </div>

                <div class="mb-4">
                    <label for="expiresAt" class="block text-sm font-medium text-gray-700 mb-2">Expires At (Optional)</label>
                    <input type="date" id="expiresAt" name="expires_at" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for no expiration</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg">
                        Create Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Show API Key Modal -->
<div id="showApiKeyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">API Key Created</h3>
                <button onclick="closeShowModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                    <p class="text-sm text-yellow-800">Save this API key now. You won't be able to see it again!</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Your API Key</label>
                <div class="flex">
                    <input type="text" id="newApiKey" readonly class="flex-1 rounded-l-lg border-gray-300 bg-gray-50 font-mono text-sm">
                    <button onclick="copyApiKey()" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-r-lg">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="flex justify-end">
                <button onclick="closeShowModal()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                    I've Saved It
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createApiKeyModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createApiKeyModal').classList.add('hidden');
    document.getElementById('createApiKeyForm').reset();
}

function closeShowModal() {
    document.getElementById('showApiKeyModal').classList.add('hidden');
    location.reload();
}

async function createApiKey(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('{{ route('tenant.api-keys.create') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('newApiKey').value = result.api_key;
            closeCreateModal();
            document.getElementById('showApiKeyModal').classList.remove('hidden');
        } else {
            alert('Error creating API key');
        }
    } catch (error) {
        alert('Error creating API key');
    }
}

async function revokeApiKey(keyId) {
    if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch(`{{ route('tenant.api-keys.revoke', '') }}/${keyId}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert('Error revoking API key');
        }
    } catch (error) {
        alert('Error revoking API key');
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show success message
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            button.innerHTML = originalHtml;
        }, 2000);
    });
}

function copyApiKey() {
    const apiKey = document.getElementById('newApiKey').value;
    copyToClipboard(apiKey);
}
</script>
@endsection