@extends('layouts.app')

@section('title', $campaign->campaign_name)

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="space-y-6">
    <!-- Campaign Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center space-x-3">
                    <h3 class="text-2xl font-semibold text-gray-900">{{ $campaign->name }}</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ strtolower($campaign->type) === 'pmax' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ strtoupper($campaign->type) }}
                    </span>
                </div>
                <p class="mt-2 text-gray-600">{{ $campaign->info }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('tenant.campaigns.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <form method="POST" action="{{ route('tenant.campaigns.destroy', $campaign->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this campaign?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </form>
            </div>
        </div>

        <!-- Campaign Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-2xl text-green-500"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Generated Assets</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $campaign->assets->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-coins text-2xl text-blue-500"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tokens Used</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($campaign->tokens_used ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar text-2xl text-purple-500"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Created</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $campaign->created_at->format('M j, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Target Keywords -->
        @if($campaign->keywords)
            @php
                $keywordsArray = is_string($campaign->keywords) ? array_map('trim', explode(',', $campaign->keywords)) : $campaign->keywords;
            @endphp
            <div class="mt-6 pt-6 border-t">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Target Keywords</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($keywordsArray as $keyword)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-tag mr-2 text-xs"></i>{{ $keyword }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Generated Assets -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Generated Assets</h3>

        @if($campaign->assets->count() > 0)
            <div class="space-y-4">
                @foreach($campaign->assets as $asset)
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ ucfirst($asset->asset_type) }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-coins mr-1"></i>{{ number_format($asset->tokens_used) }} tokens
                                    </span>
                                </div>
                                <p class="text-gray-900">{{ $asset->content }}</p>
                            </div>
                            <button onclick="copyToClipboard('{{ addslashes($asset->content) }}')" class="ml-4 text-blue-600 hover:text-blue-700" title="Copy to clipboard">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Export Button -->
            <div class="mt-6 pt-6 border-t">
                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-download mr-2"></i>Export All Assets
                </button>
            </div>
        @else
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p class="text-lg">No assets generated yet</p>
            </div>
        @endif
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success feedback (you can enhance this with a toast notification)
        alert('Copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>
@endsection
