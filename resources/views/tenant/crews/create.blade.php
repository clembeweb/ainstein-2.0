@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('tenant.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <a href="{{ route('tenant.crews.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 md:ml-2">
                                CrewAI Management
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Crea Nuovo Crew</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="mt-4 text-3xl font-bold text-gray-900">Crea Nuovo Crew</h1>
            <p class="mt-2 text-sm text-gray-600">Configura un nuovo crew di agenti AI per automatizzare i tuoi processi</p>
        </div>

        <!-- Form -->
        <form action="{{ route('tenant.crews.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Template Selection (optional) -->
                        @if(isset($templates) && $templates->count() > 0)
                        <div>
                            <label for="template_id" class="block text-sm font-medium text-gray-700">
                                Template (Opzionale)
                            </label>
                            <select id="template_id" name="template_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">-- Seleziona un template --</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}"
                                        @if($selectedTemplate && $selectedTemplate->id == $template->id) selected @endif>
                                        {{ $template->name }}
                                        @if($template->is_system) (Sistema) @endif
                                        @if($template->is_public) (Pubblico) @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-sm text-gray-500">
                                Seleziona un template per iniziare con agenti e task preconfigurati
                            </p>
                        </div>
                        @endif

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Nome del Crew <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
                                required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                placeholder="es. Crew Marketing Automation">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Descrizione
                            </label>
                            <textarea
                                name="description"
                                id="description"
                                rows="3"
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                placeholder="Descrivi lo scopo e gli obiettivi di questo crew">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Process Type -->
                        <div>
                            <label for="process_type" class="block text-sm font-medium text-gray-700">
                                Tipo di Processo <span class="text-red-500">*</span>
                            </label>
                            <select id="process_type" name="process_type" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="sequential" {{ old('process_type', 'sequential') == 'sequential' ? 'selected' : '' }}>
                                    Sequenziale
                                </option>
                                <option value="hierarchical" {{ old('process_type') == 'hierarchical' ? 'selected' : '' }}>
                                    Gerarchico
                                </option>
                            </select>
                            <div class="mt-2 text-sm text-gray-500">
                                <p class="font-medium">Sequenziale:</p>
                                <p>I task vengono eseguiti uno dopo l'altro nell'ordine definito.</p>
                                <p class="mt-2 font-medium">Gerarchico:</p>
                                <p>Un agente manager coordina e delega i task agli altri agenti.</p>
                            </div>
                            @error('process_type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Configuration (Advanced) -->
                        <div x-data="{ showAdvanced: false }">
                            <button type="button"
                                @click="showAdvanced = !showAdvanced"
                                class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900">
                                <svg class="mr-2 h-5 w-5" :class="{'rotate-90': showAdvanced}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                                Configurazione Avanzata
                            </button>

                            <div x-show="showAdvanced" x-transition class="mt-4 space-y-4">
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <label for="configuration_max_iterations" class="block text-sm font-medium text-gray-700">
                                        Iterazioni Massime
                                    </label>
                                    <input type="number"
                                        name="configuration[max_iterations]"
                                        id="configuration_max_iterations"
                                        value="{{ old('configuration.max_iterations', 10) }}"
                                        min="1"
                                        max="100"
                                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                    <p class="mt-1 text-sm text-gray-500">Numero massimo di iterazioni per l'esecuzione del crew</p>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-md">
                                    <label for="configuration_verbose" class="flex items-center">
                                        <input type="checkbox"
                                            name="configuration[verbose]"
                                            id="configuration_verbose"
                                            value="1"
                                            {{ old('configuration.verbose') ? 'checked' : '' }}
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Modalità Verbose</span>
                                    </label>
                                    <p class="mt-1 text-sm text-gray-500">Abilita log dettagliati durante l'esecuzione</p>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-md">
                                    <label for="configuration_memory" class="flex items-center">
                                        <input type="checkbox"
                                            name="configuration[memory]"
                                            id="configuration_memory"
                                            value="1"
                                            {{ old('configuration.memory') ? 'checked' : '' }}
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Memoria Persistente</span>
                                    </label>
                                    <p class="mt-1 text-sm text-gray-500">Gli agenti ricordano le interazioni precedenti</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <a href="{{ route('tenant.crews.index') }}"
                        class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Annulla
                    </a>
                    <button type="submit"
                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Crea Crew
                    </button>
                </div>
            </div>
        </form>

        <!-- Help Section -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Prossimi Passi</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Dopo aver creato il crew:</p>
                        <ol class="list-decimal list-inside mt-1 space-y-1">
                            <li>Aggiungi gli agenti AI con ruoli e capacità specifiche</li>
                            <li>Definisci i task che devono essere eseguiti</li>
                            <li>Configura le dipendenze tra i task</li>
                            <li>Testa il crew con un'esecuzione di prova</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs" defer></script>
@endpush