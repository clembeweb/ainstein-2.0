@extends('tenant.layout')

@section('title', $crew->name)

@section('content')
<div x-data="{
    activeTab: 'overview',
    isSubmitting: false,
    executionMode: 'mock',
    inputVariables: '{}',
    logs: [],
    loading: false,
    autoRefresh: false,
    showJsonError: false,
    jsonErrorMessage: '',
    validateJson() {
        try {
            JSON.parse(this.inputVariables);
            this.showJsonError = false;
            this.jsonErrorMessage = '';
            return true;
        } catch (e) {
            this.showJsonError = true;
            this.jsonErrorMessage = 'Invalid JSON format: ' + e.message;
            return false;
        }
    },
    async launchExecution() {
        if (!this.validateJson()) {
            return;
        }

        this.isSubmitting = true;

        try {
            const response = await fetch('{{ route('tenant.crews.execute', $crew) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    mode: this.executionMode,
                    input_variables: JSON.parse(this.inputVariables)
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Redirect to execution monitor
                window.location.href = data.execution_url;
            } else {
                alert('Failed to launch execution: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Request failed: ' + error.message);
        } finally {
            this.isSubmitting = false;
        }
    }
}" class="space-y-6">

    <!-- Header Card -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Crew Info Section -->
        <div class="flex items-start justify-between mb-6">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $crew->name }}</h1>
                    @if($crew->status === 'active')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                            Active
                        </span>
                    @elseif($crew->status === 'draft')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                            <span class="w-2 h-2 bg-yellow-600 rounded-full mr-2"></span>
                            Draft
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-800">
                            <span class="w-2 h-2 bg-gray-600 rounded-full mr-2"></span>
                            Archived
                        </span>
                    @endif
                </div>
                @if($crew->description)
                    <p class="text-gray-600">{{ $crew->description }}</p>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-3">
                <!-- Tour Button -->
                <button onclick="window.startCrewLaunchTour()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                        title="Show onboarding tour">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Show Tour
                </button>

                <!-- Back Button -->
                <a href="{{ route('tenant.crews.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Crews
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-700">Total Executions</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $crew->total_executions ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-700">Success Rate</p>
                        <p class="text-2xl font-bold text-green-900">
                            @if($crew->total_executions > 0)
                                {{ number_format(($crew->successful_executions / $crew->total_executions) * 100, 1) }}%
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-purple-700">Avg. Execution Time</p>
                        <p class="text-2xl font-bold text-purple-900">
                            @if($crew->average_execution_time)
                                {{ gmdate('i:s', $crew->average_execution_time) }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-t-lg shadow-sm border-b">
        <div class="flex space-x-1 px-6">
            <button @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-3 border-b-2 font-medium text-sm transition-colors">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                Overview
            </button>
            <button @click="activeTab = 'execute'"
                    :class="activeTab === 'execute' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-3 border-b-2 font-medium text-sm transition-colors">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Execute
            </button>
            <button @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-3 border-b-2 font-medium text-sm transition-colors">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                History
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-b-lg shadow-sm p-6">

        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'" x-transition>
            <div class="space-y-8">

                <!-- Agents Section -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Agents ({{ $crew->agents->count() }})
                    </h3>

                    @if($crew->agents->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($crew->agents->sortBy('order') as $agent)
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="text-sm font-semibold text-gray-900">{{ $agent->name }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">{{ $agent->role }}</p>
                                            @if($agent->tools && count($agent->tools) > 0)
                                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    {{ count($agent->tools) }} tools
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                                #{{ $agent->order }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            No agents configured
                        </div>
                    @endif
                </div>

                <!-- Tasks Section -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Tasks ({{ $crew->tasks->count() }})
                    </h3>

                    @if($crew->tasks->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($crew->tasks->sortBy('order') as $task)
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-green-300 hover:shadow-md transition-all">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="text-sm font-semibold text-gray-900">{{ $task->name }}</h4>
                                            @if($task->agent)
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <span class="text-xs text-gray-500">Assigned to:</span>
                                                    <span class="font-medium">{{ $task->agent->name }}</span>
                                                </p>
                                            @endif
                                            @if($task->description)
                                                <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $task->description }}</p>
                                            @endif
                                        </div>
                                        <div class="ml-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                                #{{ $task->order }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            No tasks configured
                        </div>
                    @endif
                </div>

            </div>
        </div>

        <!-- Execute Tab -->
        <div x-show="activeTab === 'execute'" x-transition>
            <div class="max-w-3xl mx-auto">

                <!-- Execution Mode -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Execution Mode</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all"
                               :class="executionMode === 'mock' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" x-model="executionMode" value="mock" class="sr-only" />
                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                         :class="executionMode === 'mock' ? 'border-blue-600 bg-blue-600' : 'border-gray-400'">
                                        <div class="w-2 h-2 rounded-full bg-white" x-show="executionMode === 'mock'"></div>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <span class="block text-sm font-medium" :class="executionMode === 'mock' ? 'text-blue-900' : 'text-gray-900'">
                                        Mock Mode
                                    </span>
                                    <span class="block text-sm" :class="executionMode === 'mock' ? 'text-blue-700' : 'text-gray-500'">
                                        Test execution without using API tokens
                                    </span>
                                </div>
                            </div>
                        </label>

                        <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all"
                               :class="executionMode === 'real' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" x-model="executionMode" value="real" class="sr-only" />
                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                         :class="executionMode === 'real' ? 'border-blue-600 bg-blue-600' : 'border-gray-400'">
                                        <div class="w-2 h-2 rounded-full bg-white" x-show="executionMode === 'real'"></div>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <span class="block text-sm font-medium" :class="executionMode === 'real' ? 'text-blue-900' : 'text-gray-900'">
                                        Real Mode
                                    </span>
                                    <span class="block text-sm" :class="executionMode === 'real' ? 'text-blue-700' : 'text-gray-500'">
                                        Live execution using AI models
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- API Key Warning -->
                @if(!$openAiConfigured)
                    <div x-show="executionMode === 'real'" x-transition
                         class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">API Key Not Configured</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>OpenAI API key is not configured. Real mode execution will fail.</p>
                                    <a href="{{ route('tenant.settings') }}" class="font-medium underline hover:text-yellow-600">
                                        Configure API Key →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Input Variables -->
                <div class="mb-6">
                    <label for="input_variables" class="block text-sm font-medium text-gray-700 mb-2">
                        Input Variables (JSON)
                    </label>
                    <textarea
                        id="input_variables"
                        x-model="inputVariables"
                        @input="validateJson"
                        rows="6"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                        :class="showJsonError ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''"
                        placeholder='{"topic": "AI Marketing", "target": "SMB owners", "tone": "professional"}'
                    ></textarea>

                    <!-- JSON Error Message -->
                    <p x-show="showJsonError" x-transition class="mt-2 text-sm text-red-600">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span x-text="jsonErrorMessage"></span>
                    </p>

                    <!-- Helper Text -->
                    <p class="mt-2 text-sm text-gray-500">
                        Provide input variables for the crew execution in JSON format. Example:
                        <code class="bg-gray-100 px-2 py-1 rounded text-xs">{"topic": "AI Marketing", "target": "SMB owners"}</code>
                    </p>
                </div>

                <!-- Launch Button -->
                <div class="flex justify-end">
                    <button
                        @click="launchExecution"
                        :disabled="isSubmitting || showJsonError"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg x-show="!isSubmitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <svg x-show="isSubmitting" x-cloak class="animate-spin w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Launching...' : 'Launch Execution'"></span>
                    </button>
                </div>

            </div>
        </div>

        <!-- History Tab -->
        <div x-show="activeTab === 'history'" x-transition>
            @if($crew->executions && $crew->executions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Started At
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Duration
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tokens
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($crew->executions->take(10) as $execution)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($execution->status === 'completed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5"></span>
                                                Completed
                                            </span>
                                        @elseif($execution->status === 'running')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <span class="w-2 h-2 bg-blue-400 rounded-full mr-1.5 animate-pulse"></span>
                                                Running
                                            </span>
                                        @elseif($execution->status === 'failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <span class="w-2 h-2 bg-red-400 rounded-full mr-1.5"></span>
                                                Failed
                                            </span>
                                        @elseif($execution->status === 'cancelled')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>
                                                Cancelled
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <span class="w-2 h-2 bg-yellow-400 rounded-full mr-1.5"></span>
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $execution->started_at ? $execution->started_at->format('Y-m-d H:i:s') : 'Not started' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($execution->started_at && $execution->completed_at)
                                            {{ gmdate('H:i:s', $execution->completed_at->diffInSeconds($execution->started_at)) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($execution->total_tokens_used ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('tenant.crew-executions.show', $execution) }}"
                                           class="text-blue-600 hover:text-blue-900">
                                            View Details →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($crew->executions->count() > 10)
                    <div class="mt-4 text-center">
                        <a href="{{ route('tenant.crew-executions.index', ['crew_id' => $crew->id]) }}"
                           class="text-blue-600 hover:text-blue-800 font-medium">
                            View All Executions →
                        </a>
                    </div>
                @endif
            @else
                <div class="text-center py-12 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-lg font-medium">No executions yet</p>
                    <p class="text-sm mt-1">Launch your first execution from the Execute tab</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection