@extends('layouts.tenant')

@section('title', 'Generate Content')
@section('page-title', 'Generate Content')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
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
    <form method="POST" action="{{ route('tenant.content.store') }}" class="bg-white rounded-lg shadow p-6 space-y-6">
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
                        {{ $page->url_path }}
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
                    <option value="{{ $prompt->id }}" data-template="{{ $prompt->template }}">
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

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('tenant.content.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium">
                Cancel
            </a>
            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium">
                <i class="fas fa-magic mr-2"></i>Generate Content
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
@endpush
@endsection
