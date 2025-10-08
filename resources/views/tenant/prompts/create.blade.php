@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Create New Prompt</h2>
                        <p class="text-gray-600 mt-1">Create a new content generation prompt template</p>
                    </div>
                    <a href="{{ route('tenant.prompts.index') }}" class="btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Prompts
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('tenant.prompts.store') }}" class="p-6 space-y-6" x-data="promptForm">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Prompt Name *
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name') }}"
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
                               value="{{ old('alias') }}"
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
                               value="{{ old('category') }}"
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
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
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
                              placeholder="Write a comprehensive blog post about {{topic}}. The post should be {{length}} words long and target the keyword '{{keyword}}'. Include an introduction, main body with {{sections}} sections, and a conclusion. Use an {{tone}} tone and write for {{audience}}."
                              required
                              x-model="template"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm @error('template') border-red-300 @enderror">{{ old('template') }}</textarea>
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
                    variables: @json(old('variables', [])),
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
                               {{ old('is_active', true) ? 'checked' : '' }}
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

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('tenant.prompts.index') }}" class="btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Create Prompt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('promptForm', () => ({
        template: @json(old('template', '')),
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