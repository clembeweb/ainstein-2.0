@extends('tenant.layout')

@section('title', 'Execution #' . $execution->id)

@section('content')
<div x-data="{
    logs: @json($execution->logs ?? []),
    loading: false,
    autoRefresh: {{ in_array($execution->status, ['pending', 'running']) ? 'true' : 'false' }},
    showCopiedToast: false,
    async fetchLogs() {
        if (!this.autoRefresh) return;

        this.loading = true;

        try {
            const response = await fetch('{{ route('tenant.crew-executions.logs', $execution) }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Failed to fetch logs');

            const data = await response.json();

            if (data.success) {
                this.logs = data.data.logs || [];

                // Auto-scroll to bottom
                this.$nextTick(() => {
                    const container = this.$refs.logsContainer;
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });

                // Stop auto-refresh if execution is completed/failed/cancelled
                if (data.data.status && !['pending', 'running'].includes(data.data.status)) {
                    this.autoRefresh = false;
                    // Reload page to update status and stats
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            }
        } catch (error) {
            console.error('Error fetching logs:', error);
        } finally {
            this.loading = false;
        }
    },
    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showCopiedToast = true;
            setTimeout(() => {
                this.showCopiedToast = false;
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    },
    downloadResults() {
        const content = @json($execution->results['final_output'] ?? '');
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'execution_{{ $execution->id }}_results.txt';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    },
    async cancelExecution() {
        if (!confirm('Are you sure you want to cancel this execution?')) return;

        try {
            const response = await fetch('{{ route('tenant.crew-executions.cancel', $execution) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                window.location.reload();
            } else {
                alert('Failed to cancel execution: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Request failed: ' + error.message);
        }
    },
    async retryExecution() {
        if (!confirm('Are you sure you want to retry this execution?')) return;

        try {
            const response = await fetch('{{ route('tenant.crew-executions.retry', $execution) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                window.location.href = data.execution_url;
            } else {
                alert('Failed to retry execution: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Request failed: ' + error.message);
        }
    }
}"
x-init="if (autoRefresh) { fetchLogs(); setInterval(() => fetchLogs(), 2000); }"
class="space-y-6">

    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $execution->crew->name }} - Execution
                    </h1>

                    <!-- Status Badge -->
                    @if($execution->status === 'completed')
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-base font-semibold bg-green-100 text-green-800">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Completed
                        </span>
                    @elseif($execution->status === 'running')
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-base font-semibold bg-blue-100 text-blue-800">
                            <svg class="animate-spin w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Running
                        </span>
                    @elseif($execution->status === 'failed')
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-base font-semibold bg-red-100 text-red-800">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            Failed
                        </span>
                    @elseif($execution->status === 'cancelled')
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-base font-semibold bg-gray-100 text-gray-800">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            Cancelled
                        </span>
                    @else
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-base font-semibold bg-yellow-100 text-yellow-800">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Pending
                        </span>
                    @endif
                </div>

                <!-- Timestamps -->
                <div class="flex items-center space-x-6 text-sm text-gray-600">
                    @if($execution->started_at)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Started: {{ $execution->started_at->format('Y-m-d H:i:s') }}</span>
                        </div>
                    @endif
                    @if($execution->completed_at)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Completed: {{ $execution->completed_at->format('Y-m-d H:i:s') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-3">
                <!-- Tour Button -->
                <button onclick="window.startExecutionMonitorTour()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                        title="Show onboarding tour">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Show Tour
                </button>

                <!-- Back Button -->
                <a href="{{ route('tenant.crews.show', $execution->crew) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Crew
                </a>
            </div>
        </div>

        <!-- Progress Bar (for running/pending) -->
        @if(in_array($execution->status, ['running', 'pending']))
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                    <span>Progress</span>
                    <span>{{ $execution->progress ?? 0 }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500 {{ $execution->status === 'running' ? 'animate-pulse' : '' }}"
                         style="width: {{ $execution->progress ?? 0 }}%">
                    </div>
                </div>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <!-- Tokens Used -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-700">Tokens Used</p>
                        <p class="text-2xl font-bold text-blue-900">{{ number_format($execution->total_tokens_used ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <!-- Cost -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-700">Cost</p>
                        <p class="text-2xl font-bold text-green-900">${{ number_format($execution->cost ?? 0, 4) }}</p>
                    </div>
                </div>
            </div>

            <!-- Duration -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-purple-700">Duration</p>
                        <p class="text-2xl font-bold text-purple-900">
                            @if($execution->started_at && $execution->completed_at)
                                {{ gmdate('H:i:s', $execution->completed_at->diffInSeconds($execution->started_at)) }}
                            @elseif($execution->started_at)
                                {{ gmdate('H:i:s', now()->diffInSeconds($execution->started_at)) }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-2 mt-6 pt-6 border-t">
            @if($execution->status === 'running')
                <button @click="cancelExecution"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel Execution
                </button>
            @endif

            @if(in_array($execution->status, ['failed', 'cancelled']))
                <button @click="retryExecution"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Retry Execution
                </button>
            @endif
        </div>
    </div>

    <!-- Real-time Logs Section -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Execution Logs
                    <span x-show="loading" class="ml-3 text-sm text-gray-500">
                        <svg class="animate-spin inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Updating...
                    </span>
                </h2>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="autoRefresh" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                    <span class="ml-2 text-sm text-gray-600">Auto-refresh</span>
                </label>
            </div>
        </div>

        <!-- Logs Container -->
        <div x-ref="logsContainer" class="p-6 bg-gray-900 text-gray-100 font-mono text-sm max-h-96 overflow-y-auto">
            <template x-if="logs.length === 0">
                <div class="text-gray-500 text-center py-8">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    No logs available yet...
                </div>
            </template>

            <template x-for="(log, index) in logs" :key="index">
                <div class="mb-2 flex items-start space-x-3 hover:bg-gray-800 px-2 py-1 rounded">
                    <!-- Timestamp -->
                    <span class="text-gray-500 text-xs whitespace-nowrap" x-text="log.timestamp || new Date(log.created_at).toLocaleTimeString()"></span>

                    <!-- Level Badge -->
                    <span class="px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap"
                          :class="{
                              'bg-blue-900 text-blue-200': log.level === 'info',
                              'bg-yellow-900 text-yellow-200': log.level === 'warning',
                              'bg-red-900 text-red-200': log.level === 'error',
                              'bg-gray-700 text-gray-300': log.level === 'debug'
                          }"
                          x-text="log.level ? log.level.toUpperCase() : 'INFO'">
                    </span>

                    <!-- Message -->
                    <span class="flex-1 text-gray-200" x-text="log.message"></span>
                </div>
            </template>
        </div>
    </div>

    <!-- Results Section (only if completed) -->
    @if($execution->status === 'completed' && isset($execution->results['final_output']))
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Final Results
                    </h2>
                    <div class="flex items-center space-x-2">
                        <button @click="copyToClipboard('{{ addslashes($execution->results['final_output']) }}')"
                                class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Copy
                        </button>
                        <button @click="downloadResults"
                                class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Download
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <textarea readonly
                          rows="12"
                          class="w-full rounded-lg border-gray-300 bg-gray-50 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                >{{ $execution->results['final_output'] }}</textarea>
            </div>
        </div>
    @endif

    <!-- Error Details (only if failed) -->
    @if($execution->status === 'failed' && isset($execution->results['error']))
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Execution Failed</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <pre class="whitespace-pre-wrap font-mono">{{ $execution->results['error'] }}</pre>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Copy Toast Notification -->
    <div x-show="showCopiedToast"
         x-transition
         x-cloak
         class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 z-50">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>Copied to clipboard!</span>
    </div>
</div>
@endsection