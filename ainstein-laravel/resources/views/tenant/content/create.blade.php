@extends('layouts.app')

@section('title', 'Generate Content')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="contentGenerationForm()">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Generate New Content</h3>
                <p class="text-gray-600">Use AI to generate content for your pages</p>
            </div>
            <a href="{{ route('tenant.content.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to Generations
            </a>
        </div>
    </div>

    <!-- Generation Form -->
    <form method="POST" action="{{ route('tenant.content.store') }}" @submit="handleSubmit" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <!-- Page Selection -->
        <div>
            <label for="page_id" class="block text-sm font-medium text-gray-700 mb-2">
                Select Page <span class="text-red-500">*</span>
            </label>
            <select id="page_id" name="page_id" required
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                <option value="">Choose a page...</option>
                @foreach($pages as $page)
                    <option value="{{ $page->id }}" {{ ($selectedPage && $selectedPage->id === $page->id) ? 'selected' : '' }}>
                        {{ $page->url }}
                        @if($page->keyword)
                            - {{ $page->keyword }}
                        @endif
                    </option>
                @endforeach
            </select>
            @error('page_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Prompt Selection -->
        <div>
            <label for="prompt_id" class="block text-sm font-medium text-gray-700 mb-2">
                Select Prompt Template <span class="text-red-500">*</span>
            </label>
            <select id="prompt_id" name="prompt_id" required
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                <option value="">Choose a prompt...</option>
                @foreach($prompts as $prompt)
                    <option value="{{ $prompt->id }}" data-template="{{ htmlspecialchars($prompt->template, ENT_QUOTES) }}">
                        {{ $prompt->title }} ({{ $prompt->alias }})
                    </option>
                @endforeach
            </select>
            @error('prompt_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <!-- Prompt Preview -->
            <div id="prompt-preview" class="mt-3 hidden">
                <p class="text-sm font-medium text-gray-700 mb-2">Template Preview:</p>
                <div class="bg-gray-50 rounded-lg p-4">
                    <pre class="text-sm text-gray-600 whitespace-pre-wrap font-mono" id="prompt-preview-text"></pre>
                </div>
            </div>
        </div>

        <!-- Variables Section (dynamic based on prompt) -->
        <div id="variables-section" class="hidden space-y-4">
            <div>
                <p class="text-sm font-medium text-gray-700 mb-3">Template Variables</p>
                <div id="variables-container" class="space-y-3"></div>
            </div>
        </div>

        <!-- Additional Instructions -->
        <div>
            <label for="additional_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                Additional Instructions (Optional)
            </label>
            <textarea id="additional_instructions" name="additional_instructions" rows="4"
                      placeholder="Any specific requirements or context for the AI..."
                      class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"></textarea>
            @error('additional_instructions')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-500">Max 2000 characters</p>
        </div>

        <!-- Execution Mode Toggle -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" x-model="syncMode" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-5 w-5">
                <div class="ml-3 flex-1">
                    <span class="font-medium text-gray-900" x-show="syncMode">⚡ Instant Generation</span>
                    <span class="font-medium text-gray-900" x-show="!syncMode">🔄 Background Generation</span>
                    <p class="text-sm text-gray-600 mt-1" x-show="syncMode">
                        Generate content immediately and see results right away (may take 30-60 seconds)
                    </p>
                    <p class="text-sm text-gray-600 mt-1" x-show="!syncMode">
                        Content will be generated in the background. You can continue working and check back later.
                    </p>
                </div>
            </label>
            <input type="hidden" name="execution_mode" :value="syncMode ? 'sync' : 'async'">
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('tenant.content.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium">
                Cancel
            </a>
            <button type="submit" :disabled="isSubmitting" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!isSubmitting">
                    <i class="fas fa-magic mr-2"></i>
                    <span x-text="syncMode ? 'Generate Now' : 'Start Generation'"></span>
                </span>
                <span x-show="isSubmitting">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Processing...
                </span>
            </button>
        </div>
    </form>

    <!-- Progress Modal (for sync mode) -->
    <div x-show="showProgressModal"
         x-cloak
         class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center"
         style="display: none;">
        <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <!-- Spinner -->
                <div class="mb-4">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-5xl"></i>
                </div>

                <!-- Status Message -->
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Generating Content...</h3>
                <p class="text-gray-600 mb-6" x-text="statusMessage"></p>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" :style="'width: ' + progress + '%'"></div>
                </div>

                <!-- Elapsed Time -->
                <p class="text-sm text-gray-500">
                    Elapsed time: <span x-text="elapsedTime"></span>s
                </p>

                <!-- Warning for long requests -->
                <div x-show="elapsedTime > 30" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        This is taking longer than usual. Please wait...
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@verbatim
<script>
// Alpine.js component for content generation form
function contentGenerationForm() {
    return {
        syncMode: false,
        isSubmitting: false,
        showProgressModal: false,
        progress: 0,
        elapsedTime: 0,
        statusMessage: 'Preparing your request...',
        timerInterval: null,
        progressInterval: null,

        init() {
            // Initialize prompt preview functionality
            const promptSelect = document.getElementById('prompt_id');
            const promptPreview = document.getElementById('prompt-preview');
            const promptPreviewText = document.getElementById('prompt-preview-text');
            const variablesSection = document.getElementById('variables-section');
            const variablesContainer = document.getElementById('variables-container');

            promptSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const template = selectedOption.dataset.template;

                if (template) {
                    // Show preview
                    promptPreview.classList.remove('hidden');
                    promptPreviewText.textContent = template;

                    // Extract variables from template ({{variable}})
                    const variableRegex = /\{\{([^}]+)\}\}/g;
                    const matches = [...template.matchAll(variableRegex)];
                    const variables = [...new Set(matches.map(m => m[1].trim()))];

                    if (variables.length > 0) {
                        // Show variables section
                        variablesSection.classList.remove('hidden');

                        // Create input fields for each variable
                        variablesContainer.innerHTML = variables.map(variable => `
                            <div>
                                <label for="var_${variable}" class="block text-sm font-medium text-gray-700 mb-1">
                                    ${variable}
                                </label>
                                <input type="text"
                                       id="var_${variable}"
                                       name="variables[${variable}]"
                                       class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                                       placeholder="Enter value for ${variable}">
                            </div>
                        `).join('');
                    } else {
                        variablesSection.classList.add('hidden');
                        variablesContainer.innerHTML = '';
                    }
                } else {
                    // Hide all
                    promptPreview.classList.add('hidden');
                    variablesSection.classList.add('hidden');
                    variablesContainer.innerHTML = '';
                }
            });
        },

        handleSubmit(event) {
            // If async mode, allow normal form submission
            if (!this.syncMode) {
                return true;
            }

            // Prevent default and handle sync mode with AJAX
            event.preventDefault();
            this.submitSyncGeneration(event.target);
        },

        async submitSyncGeneration(form) {
            this.isSubmitting = true;
            this.showProgressModal = true;
            this.progress = 0;
            this.elapsedTime = 0;
            this.statusMessage = 'Preparing your request...';

            // Start elapsed time counter
            this.timerInterval = setInterval(() => {
                this.elapsedTime++;
            }, 1000);

            // Simulate progress (since we can't get real progress from server)
            this.progressInterval = setInterval(() => {
                if (this.progress < 90) {
                    this.progress += Math.random() * 10;
                    if (this.progress > 90) this.progress = 90;
                }

                // Update status messages based on progress
                if (this.progress < 30) {
                    this.statusMessage = 'Preparing your request...';
                } else if (this.progress < 60) {
                    this.statusMessage = 'Calling AI service...';
                } else {
                    this.statusMessage = 'Almost done...';
                }
            }, 1000);

            try {
                // Collect form data
                const formData = new FormData(form);

                // Make AJAX request
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                // Complete progress
                this.progress = 100;
                this.statusMessage = 'Complete!';

                // Clear intervals
                clearInterval(this.timerInterval);
                clearInterval(this.progressInterval);

                if (response.ok && data.success) {
                    // Success - redirect to result page
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 500);
                } else {
                    // Error from server
                    this.showProgressModal = false;
                    this.isSubmitting = false;
                    alert('Generation failed: ' + (data.error || 'Unknown error'));
                }

            } catch (error) {
                // Network or other error
                clearInterval(this.timerInterval);
                clearInterval(this.progressInterval);
                this.showProgressModal = false;
                this.isSubmitting = false;
                alert('Request failed: ' + error.message);
            }
        }
    };
}
</script>
@endverbatim
@endpush
@endsection
