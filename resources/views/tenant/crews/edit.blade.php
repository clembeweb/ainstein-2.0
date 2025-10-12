@extends('layouts.app')

@section('content')
<div class="py-6" x-data="crewEditor()">
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
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Modifica: {{ $crew->name }}</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="mt-4 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Modifica Crew</h1>
                    <p class="mt-2 text-sm text-gray-600">Modifica la configurazione del crew e gestisci agenti e task</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('tenant.crews.show', $crew) }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Visualizza
                    </a>
                    @if($crew->status === 'active')
                    <form action="{{ route('tenant.crews.execute', $crew) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Esegui
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Generale
                </button>
                <button @click="activeTab = 'agents'"
                    :class="activeTab === 'agents' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Agenti ({{ $crew->agents->count() }})
                </button>
                <button @click="activeTab = 'tasks'"
                    :class="activeTab === 'tasks' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Task ({{ $crew->tasks->count() }})
                </button>
                <button @click="activeTab = 'configuration'"
                    :class="activeTab === 'configuration' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Configurazione
                </button>
            </nav>
        </div>

        <!-- General Tab -->
        <div x-show="activeTab === 'general'" x-transition>
            <form action="{{ route('tenant.crews.update', $crew) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Nome del Crew <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                    name="name"
                                    id="name"
                                    value="{{ old('name', $crew->name) }}"
                                    required
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
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
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('description', $crew->description) }}</textarea>
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
                                    <option value="sequential" {{ old('process_type', $crew->process_type) == 'sequential' ? 'selected' : '' }}>
                                        Sequenziale
                                    </option>
                                    <option value="hierarchical" {{ old('process_type', $crew->process_type) == 'hierarchical' ? 'selected' : '' }}>
                                        Gerarchico
                                    </option>
                                </select>
                                @error('process_type')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">
                                    Stato <span class="text-red-500">*</span>
                                </label>
                                <select id="status" name="status" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="draft" {{ old('status', $crew->status) == 'draft' ? 'selected' : '' }}>
                                        Bozza
                                    </option>
                                    <option value="active" {{ old('status', $crew->status) == 'active' ? 'selected' : '' }}>
                                        Attivo
                                    </option>
                                    <option value="archived" {{ old('status', $crew->status) == 'archived' ? 'selected' : '' }}>
                                        Archiviato
                                    </option>
                                </select>
                                @error('status')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Salva Modifiche
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Agents Tab -->
        <div x-show="activeTab === 'agents'" x-transition>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Agenti del Crew</h3>
                        <button type="button"
                            @click="showAddAgentModal = true"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Aggiungi Agente
                        </button>
                    </div>

                    @if($crew->agents->count() > 0)
                        <div class="space-y-3">
                            @foreach($crew->agents as $agent)
                            <div class="border rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">{{ $agent->name }}</h4>
                                        <p class="mt-1 text-sm text-gray-600">{{ $agent->role }}</p>
                                        <p class="mt-2 text-sm text-gray-500">{{ $agent->goal }}</p>
                                        @if($agent->tools)
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach($agent->tools as $tool)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $tool }}
                                            </span>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        <button type="button"
                                            @click="editAgent({{ $agent->id }})"
                                            class="text-gray-400 hover:text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <form action="{{ route('tenant.crews.agents.destroy', [$crew, $agent]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                onclick="return confirm('Sei sicuro di voler eliminare questo agente?')"
                                                class="text-red-400 hover:text-red-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Nessun agente</h3>
                            <p class="mt-1 text-sm text-gray-500">Inizia aggiungendo un agente al crew.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tasks Tab -->
        <div x-show="activeTab === 'tasks'" x-transition>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Task del Crew</h3>
                        <button type="button"
                            @click="showAddTaskModal = true"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Aggiungi Task
                        </button>
                    </div>

                    @if($crew->tasks->count() > 0)
                        <div class="space-y-3">
                            @foreach($crew->tasks as $task)
                            <div class="border rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">{{ $task->name }}</h4>
                                        <p class="mt-1 text-sm text-gray-600">{{ $task->description }}</p>
                                        @if($task->agent)
                                        <p class="mt-2 text-xs text-gray-500">
                                            Assegnato a: <span class="font-medium">{{ $task->agent->name }}</span>
                                        </p>
                                        @endif
                                        <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                            <span>Ordine: {{ $task->order }}</span>
                                            @if($task->context)
                                            <span>Ha contesto</span>
                                            @endif
                                            @if($task->output_format)
                                            <span>Output: {{ $task->output_format }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        <button type="button"
                                            @click="editTask({{ $task->id }})"
                                            class="text-gray-400 hover:text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <form action="{{ route('tenant.crews.tasks.destroy', [$crew, $task]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                onclick="return confirm('Sei sicuro di voler eliminare questo task?')"
                                                class="text-red-400 hover:text-red-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Nessun task</h3>
                            <p class="mt-1 text-sm text-gray-500">Inizia aggiungendo un task al crew.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Configuration Tab -->
        <div x-show="activeTab === 'configuration'" x-transition>
            <form action="{{ route('tenant.crews.update', $crew) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Configurazione Avanzata</h3>

                        <div class="space-y-4">
                            <div>
                                <label for="config_max_iterations" class="block text-sm font-medium text-gray-700">
                                    Iterazioni Massime
                                </label>
                                <input type="number"
                                    name="configuration[max_iterations]"
                                    id="config_max_iterations"
                                    value="{{ old('configuration.max_iterations', $crew->configuration['max_iterations'] ?? 10) }}"
                                    min="1"
                                    max="100"
                                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label for="config_verbose" class="flex items-center">
                                    <input type="checkbox"
                                        name="configuration[verbose]"
                                        id="config_verbose"
                                        value="1"
                                        {{ old('configuration.verbose', $crew->configuration['verbose'] ?? false) ? 'checked' : '' }}
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Modalità Verbose</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500">Abilita log dettagliati durante l'esecuzione</p>
                            </div>

                            <div>
                                <label for="config_memory" class="flex items-center">
                                    <input type="checkbox"
                                        name="configuration[memory]"
                                        id="config_memory"
                                        value="1"
                                        {{ old('configuration.memory', $crew->configuration['memory'] ?? false) ? 'checked' : '' }}
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Memoria Persistente</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500">Gli agenti ricordano le interazioni precedenti</p>
                            </div>

                            <div>
                                <label for="config_cache" class="flex items-center">
                                    <input type="checkbox"
                                        name="configuration[cache]"
                                        id="config_cache"
                                        value="1"
                                        {{ old('configuration.cache', $crew->configuration['cache'] ?? false) ? 'checked' : '' }}
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Cache dei Risultati</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500">Memorizza i risultati per migliorare le performance</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Salva Configurazione
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="mt-8 bg-red-50 border border-red-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-red-800">Zona Pericolosa</h3>
            <div class="mt-3 flex items-center space-x-3">
                <form action="{{ route('tenant.crews.clone', $crew) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Clona Crew
                    </button>
                </form>

                <form action="{{ route('tenant.crews.destroy', $crew) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        onclick="return confirm('Sei sicuro di voler eliminare questo crew? Questa azione non può essere annullata.')"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Elimina Crew
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function crewEditor() {
    return {
        activeTab: 'general',
        showAddAgentModal: false,
        showAddTaskModal: false,

        editAgent(agentId) {
            // Implementation for editing agent
            console.log('Edit agent:', agentId);
        },

        editTask(taskId) {
            // Implementation for editing task
            console.log('Edit task:', taskId);
        }
    }
}
</script>
@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs" defer></script>
@endpush