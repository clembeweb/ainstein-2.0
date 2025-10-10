@extends('layouts.app')

@section('title', 'Modifica Campaign')

@section('content')
<div x-data="{
    keywords: '{{ old('target_keywords', $campaign->keywords) }}',
    get keywordArray() {
        return this.keywords.split(',').map(k => k.trim()).filter(k => k.length > 0);
    }
}">
    <div class="max-w-4xl">
        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100">
                        <i class="fas fa-edit text-xl text-blue-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Modifica Campaign</h3>
                    <p class="text-sm text-gray-600 mt-1">Aggiorna le informazioni della campaign. Gli asset generati non verranno modificati.</p>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="{{ route('tenant.campaigns.update', $campaign->id) }}" class="bg-white rounded-lg shadow-sm p-6">
            @csrf
            @method('PUT')

            <!-- Campaign Name -->
            <div class="mb-6">
                <label for="campaign_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nome Campaign <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="campaign_name"
                    name="campaign_name"
                    value="{{ old('campaign_name', $campaign->name) }}"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="es. Saldi Estivi 2025 - Orologi"
                    required
                >
                @error('campaign_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Campaign Type (Read-only) -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo Campaign
                </label>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold
                        {{ strtolower($campaign->type) === 'pmax' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ strtoupper($campaign->type) }}
                        @if(strtolower($campaign->type) === 'rsa')
                            (Responsive Search Ads)
                        @else
                            (Performance Max)
                        @endif
                    </span>
                    <span class="text-xs text-gray-500">
                        <i class="fas fa-lock mr-1"></i>Il tipo di campaign non può essere modificato
                    </span>
                </div>
            </div>

            <!-- Business Description -->
            <div class="mb-6">
                <label for="business_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Descrizione Business <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="business_description"
                    name="business_description"
                    rows="5"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="Descrivi il tuo business, prodotti o servizi. Sii specifico su cosa ti rende unico."
                    required
                >{{ old('business_description', $campaign->info) }}</textarea>
                @error('business_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>La modifica della descrizione NON rigenererà automaticamente gli asset
                </p>
            </div>

            <!-- Target Keywords -->
            <div class="mb-6">
                <label for="target_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                    Parole Chiave Target <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="target_keywords"
                    name="target_keywords"
                    x-model="keywords"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="es. orologi svizzeri, segnatempo lusso, orologi artigianali"
                    required
                >
                @error('target_keywords')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    Separa le parole chiave con virgole
                </p>

                <!-- Keywords Preview -->
                <div x-show="keywordArray.length > 0" x-transition class="mt-3 flex flex-wrap gap-2">
                    <template x-for="keyword in keywordArray" :key="keyword">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                            <i class="fas fa-tag mr-1 text-amber-500"></i>
                            <span x-text="keyword"></span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- URL -->
            <div class="mb-6">
                <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                    URL Finale
                </label>
                <input
                    type="url"
                    id="url"
                    name="url"
                    value="{{ old('url', $campaign->url) }}"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="https://www.esempio.com"
                >
                @error('url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    URL di destinazione per la campaign
                </p>
            </div>

            <!-- Language -->
            <div class="mb-6">
                <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                    Lingua
                </label>
                <select
                    id="language"
                    name="language"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                >
                    <option value="it" {{ old('language', $campaign->language ?? 'it') === 'it' ? 'selected' : '' }}>Italiano</option>
                    <option value="en" {{ old('language', $campaign->language) === 'en' ? 'selected' : '' }}>English</option>
                    <option value="es" {{ old('language', $campaign->language) === 'es' ? 'selected' : '' }}>Español</option>
                    <option value="fr" {{ old('language', $campaign->language) === 'fr' ? 'selected' : '' }}>Français</option>
                    <option value="de" {{ old('language', $campaign->language) === 'de' ? 'selected' : '' }}>Deutsch</option>
                </select>
                @error('language')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="{{ route('tenant.campaigns.show', $campaign->id) }}"
                   class="text-gray-600 hover:text-gray-900 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Annulla
                </a>

                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium inline-flex items-center shadow-sm transition-colors">
                    <i class="fas fa-save mr-2"></i>Salva Modifiche
                </button>
            </div>
        </form>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-5">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-2xl text-blue-500"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">Nota Importante</h3>
                    <ul class="text-sm text-blue-800 space-y-1.5">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-2 mt-0.5"></i>
                            <span>Le modifiche aggiorneranno solo le informazioni della campaign</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-2 mt-0.5"></i>
                            <span>Gli asset già generati NON verranno modificati automaticamente</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-sync-alt text-blue-600 mr-2 mt-0.5"></i>
                            <span>Per rigenerare gli asset con le nuove informazioni, usa il pulsante "Rigenera Asset" nella pagina di dettaglio</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
