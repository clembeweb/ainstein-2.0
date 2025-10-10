@extends('layouts.app')

@section('title', 'Campaign Generator')

@section('content')
<div x-data="{ showFilters: false }">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Google Ads Campaigns</h3>
                <p class="text-sm text-gray-600 mt-1">Genera campagne pubblicitarie ottimizzate con l'AI (RSA & Performance Max)</p>
            </div>

            <div class="flex items-center space-x-3">
                <button @click="showFilters = !showFilters" class="text-gray-600 hover:text-gray-900 px-4 py-2 border border-gray-300 rounded-lg">
                    <i class="fas fa-filter mr-2"></i>
                    <span x-text="showFilters ? 'Nascondi filtri' : 'Mostra filtri'"></span>
                </button>
                <a href="{{ route('tenant.campaigns.create') }}" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-medium inline-flex items-center shadow-sm">
                    <i class="fas fa-plus mr-2"></i>Nuova Campaign
                </a>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" x-show="showFilters" x-transition class="mt-6 pt-6 border-t">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo Campaign</label>
                    <select name="campaign_type" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Tutti i tipi</option>
                        @foreach($campaignTypes as $type)
                            <option value="{{ $type }}" {{ request('campaign_type') === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2"></div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Filtra
                    </button>
                    <a href="{{ route('tenant.campaigns.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Campaigns List -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if($campaigns->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assets</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($campaigns as $campaign)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $campaign->name }}</div>
                                        <div class="text-sm text-gray-600 mt-1">{{ Str::limit($campaign->info, 70) }}</div>
                                        @if($campaign->keywords)
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach(array_slice(array_map('trim', explode(',', $campaign->keywords)), 0, 3) as $keyword)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                        {{ $keyword }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                        {{ strtolower($campaign->type) === 'pmax' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ strtoupper($campaign->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center text-sm text-gray-900">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        {{ $campaign->assets_count ?? 0 }} asset{{ ($campaign->assets_count ?? 0) != 1 ? 's' : '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($campaign->tokens_used)
                                        <div class="flex items-center text-gray-900">
                                            <i class="fas fa-coins text-amber-500 mr-2"></i>
                                            {{ number_format($campaign->tokens_used) }}
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div>{{ $campaign->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $campaign->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('tenant.campaigns.show', $campaign->id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                            <i class="fas fa-eye mr-1"></i>Vedi
                                        </a>
                                        <form method="POST" action="{{ route('tenant.campaigns.destroy', $campaign->id) }}" class="inline" onsubmit="return confirm('Sei sicuro di voler eliminare questa campaign?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">
                                                <i class="fas fa-trash mr-1"></i>Elimina
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($campaigns->hasPages())
                <div class="px-6 py-4 border-t bg-gray-50">
                    {{ $campaigns->appends(request()->query())->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="px-6 py-16 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 mb-4">
                    <i class="fas fa-bullhorn text-2xl text-amber-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nessuna campaign trovata</h3>
                <p class="text-gray-600 mb-6">Crea la tua prima campaign per iniziare a generare asset pubblicitari con l'AI</p>
                <a href="{{ route('tenant.campaigns.create') }}" class="inline-flex items-center bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-medium shadow-sm">
                    <i class="fas fa-plus mr-2"></i>Crea la tua prima campaign
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
