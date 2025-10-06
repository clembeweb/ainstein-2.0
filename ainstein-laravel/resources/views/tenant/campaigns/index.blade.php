@extends('layouts.app')

@section('title', 'Campaign Generator')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="space-y-6">
    <!-- Header with Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Campaign Generator</h3>
                <p class="text-gray-600">Generate Google Ads campaigns (RSA & PMAX) with AI</p>
            </div>

            <div>
                <a href="{{ route('tenant.campaigns.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>New Campaign
                </a>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Type</label>
                    <select name="campaign_type" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Types</option>
                        @foreach($campaignTypes as $type)
                            <option value="{{ $type }}" {{ request('campaign_type') === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('tenant.campaigns.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Campaigns List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assets</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $campaign->name }}</div>
                                    <div class="text-sm text-gray-600">{{ Str::limit($campaign->info, 60) }}</div>
                                    @if($campaign->keywords)
                                        @php
                                            $keywordsArray = is_string($campaign->keywords) ? array_map('trim', explode(',', $campaign->keywords)) : $campaign->keywords;
                                        @endphp
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach(array_slice($keywordsArray, 0, 3) as $keyword)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $keyword }}
                                                </span>
                                            @endforeach
                                            @if(count($keywordsArray) > 3)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                    +{{ count($keywordsArray) - 3 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ strtolower($campaign->type) === 'pmax' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ strtoupper($campaign->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center text-sm text-gray-900">
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                    {{ $campaign->assets_count ?? 0 }} asset{{ ($campaign->assets_count ?? 0) != 1 ? 's' : '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if($campaign->tokens_used)
                                    <div class="flex items-center">
                                        <i class="fas fa-coins text-blue-500 mr-1"></i>
                                        {{ number_format($campaign->tokens_used) }}
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div>{{ $campaign->created_at->format('M j, Y') }}</div>
                                <div class="text-xs">{{ $campaign->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="{{ route('tenant.campaigns.show', $campaign->id) }}" class="text-blue-600 hover:text-blue-700" title="View">
                                        View
                                    </a>
                                    <form method="POST" action="{{ route('tenant.campaigns.destroy', $campaign->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this campaign?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-bullhorn text-4xl mb-4"></i>
                                    <p class="text-lg">No campaigns found</p>
                                    <p class="text-sm">Create your first campaign to get started</p>
                                    <a href="{{ route('tenant.campaigns.create') }}" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                                        <i class="fas fa-plus mr-2"></i>Create Campaign
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($campaigns->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $campaigns->appends(request()->query())->links() }}
            </div>
        @endif
        </div>
    </div>
</div>
@endsection
