@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Edit Prompt</h2>
                        <p class="text-gray-600 mt-1 truncate max-w-lg">{{ $prompt->name }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('tenant.prompts.show', $prompt) }}" class="btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View Prompt
                        </a>
                        <a href="{{ route('tenant.prompts.index') }}" class="btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back to Prompts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('tenant.prompts.update', $prompt) }}" class="p-6 space-y-6" x-data="promptForm">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Prompt Name *
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $prompt->name) }}"
                           placeholder="e.g., SEO Blog Post Generator"
                           required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">A descriptive name for this prompt template</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Alias -->
                    <div>
                        <label for="alias" class="block text-sm font-medium text-gray-700 mb-2">
                            Alias
                        </label>
                        <input type="text"
                               name="alias"
                               id="alias"
                               value="{{ old('alias', $prompt->alias) }}"
                               placeholder="e.g., seo-blog-post"
                               pattern="[a-z0-9_-]+"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('alias') border-red-300 @enderror">
                        @error('alias')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 text-sm mt-1">Unique identifier (lowercase, numbers, hyphens, underscores only)</p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Category
                        </label>
                        <input type="text"
                               name="category"
                               id="category"
                               value="{{ old('category', $prompt->category) }}"
                               placeholder="e.g., Blog, Product, Marketing"
                               list="categories"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('category') border-red-300 @enderror">
                        <datalist id="categories">
                            @foreach($categories as $category)
                                <option value="{{ $category }}">
                            @endforeach
                        </datalist>
                        @error('category')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              placeholder="Describe what this prompt does and when to use it..."
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-300 @enderror">{{ old('description', $prompt->description) }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">Brief description of the prompt's purpose and usage</p>
                </div>

                <!-- Template -->
                <div>
                    <label for="template" class="block text-sm font-medium text-gray-700 mb-2">
                        Prompt Template *
                    </label>
                    <textarea name="template"
                              id="template"
                              rows="10"
                              placeholder="Write a comprehensive blog post about {{topic}}..."
                              required
                              x-model="template"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm @error('template') border-red-300 @enderror">{{ old('template', $prompt->template) }}</textarea>
                    @error('template')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">Use {{variable_name}} syntax for dynamic variables</p>
                </div>

                <!-- Variables Detection -->
                <div x-show="detectedVariables.length > 0" x-transition>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Detected Variables
                    </label>
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <p class="text-sm text-blue-700 mb-2">The following variables were detected in your template:</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="variable in detectedVariables" :key="variable">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{<span x-text="variable"></span>}}
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Manual Variables -->
                <div x-data="{
                    variables: @json(old('variables', $prompt->variables ?? [])),
                    addVariable() {
                        this.variables.push('');
                    },
                    removeVariable(index) {
                        this.variables.splice(index, 1);
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Additional Variables
                    </label>
                    <div class="space-y-3">
                        <template x-for="(variable, index) in variables" :key="index">
                            <div class="flex space-x-3 items-center">
                                <input type="text"
                                       :name="`variables[${index}]`"
                                       x-model="variables[index]"
                                       placeholder="variable_name"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button"
                                        @click="removeVariable(index)"
                                        class="text-red-600 hover:text-red-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button type="button"
                                @click="addVariable()"
                                class="flex items-center text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Variable
                        </button>
                    </div>
                    <p class="text-gray-500 text-sm mt-1">Add variables not automatically detected in the template</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $prompt->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Active (prompt can be used for content generation)</span>
                    </label>
                </div>

                <!-- Preview -->
                <div x-show="template.length > 0" x-transition>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Template Preview
                    </label>
                    <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                        <pre class="text-sm text-gray-700 whitespace-pre-wrap" x-text="template"></pre>
                    </div>
                </div>

                <!-- Prompt Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Prompt Information</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Created:</span>
                            <span class="ml-2 text-gray-900">{{ $prompt->created_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Last Updated:</span>
                            <span class="ml-2 text-gray-900">{{ $prompt->updated_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Type:</span>
                            <span class="ml-2 text-gray-900">{{ $prompt->is_system ? 'System' : 'Custom' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Prompt ID:</span>
                            <span class="ml-2 text-gray-900 font-mono">{{ $prompt->id }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('tenant.prompts.show', $prompt) }}" class="btn-secondary">
                            Cancel
                        </a>
                        <a href="{{ route('tenant.prompts.index') }}" class="text-gray-600 hover:text-gray-900">
                            Back to Prompts
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <form method="POST"
                              action="{{ route('tenant.prompts.destroy', $prompt) }}"
                              class="inline"
                              onsubmit="return confirm('Are you sure you want to delete this prompt? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete Prompt
                            </button>
                        </form>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Prompt
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('promptForm', () => ({
        template: @json(old('template', $prompt->template)),
        detectedVariables: [],

        init() {
            this.$watch('template', (value) => {
                this.detectVariables(value);
            });
            this.detectVariables(this.template);
        },

        detectVariables(template) {
            const regex = /\{\{([^}]+)\}\}/g;
            const variables = [];
            let match;

            while ((match = regex.exec(template)) !== null) {
                const variable = match[1].trim();
                if (!variables.includes(variable)) {
                    variables.push(variable);
                }
            }

            this.detectedVariables = variables;
        }
    }));
});
</script>
@endsection