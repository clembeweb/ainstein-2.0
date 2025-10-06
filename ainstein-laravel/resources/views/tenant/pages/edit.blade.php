@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Edit Page</h2>
                        <p class="text-gray-600 mt-1 truncate max-w-lg">{{ $page->url_path }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('tenant.pages.show', $page) }}" class="btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View Page
                        </a>
                        <a href="{{ route('tenant.pages.index') }}" class="btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back to Pages
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form method="POST" action="{{ route('tenant.pages.update', $page) }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <!-- URL Path -->
                <div>
                    <label for="url_path" class="block text-sm font-medium text-gray-700 mb-2">
                        URL Path *
                    </label>
                    <input type="text"
                           name="url_path"
                           id="url_path"
                           value="{{ old('url_path', $page->url_path) }}"
                           placeholder="e.g., /blog/best-practices-seo"
                           required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('url_path') border-red-300 @enderror">
                    @error('url_path')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">The URL path where this page will be published</p>
                </div>

                <!-- Target Keyword -->
                <div>
                    <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                        Target Keyword *
                    </label>
                    <input type="text"
                           name="keyword"
                           id="keyword"
                           value="{{ old('keyword', $page->keyword) }}"
                           placeholder="e.g., best SEO practices"
                           required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('keyword') border-red-300 @enderror">
                    @error('keyword')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-sm mt-1">The main keyword this page should target for SEO</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Category
                        </label>
                        <input type="text"
                               name="category"
                               id="category"
                               value="{{ old('category', $page->category) }}"
                               placeholder="e.g., Blog, Product, Service"
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

                    <!-- Language -->
                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                            Language *
                        </label>
                        <select name="language"
                                id="language"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('language') border-red-300 @enderror">
                            <option value="">Select Language</option>
                            @foreach($languages as $lang)
                                <option value="{{ $lang }}" {{ old('language', $page->language) === $lang ? 'selected' : '' }}>
                                    {{ strtoupper($lang) }}
                                </option>
                            @endforeach
                        </select>
                        @error('language')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- CMS Type -->
                    <div>
                        <label for="cms_type" class="block text-sm font-medium text-gray-700 mb-2">
                            CMS Type
                        </label>
                        <select name="cms_type"
                                id="cms_type"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('cms_type') border-red-300 @enderror">
                            <option value="">Select CMS Type</option>
                            @foreach($cmsTypes as $cmsType)
                                <option value="{{ $cmsType }}" {{ old('cms_type', $page->cms_type) === $cmsType ? 'selected' : '' }}>
                                    {{ ucfirst($cmsType) }}
                                </option>
                            @endforeach
                        </select>
                        @error('cms_type')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- CMS Page ID -->
                    <div>
                        <label for="cms_page_id" class="block text-sm font-medium text-gray-700 mb-2">
                            CMS Page ID
                        </label>
                        <input type="text"
                               name="cms_page_id"
                               id="cms_page_id"
                               value="{{ old('cms_page_id', $page->cms_page_id) }}"
                               placeholder="e.g., post_123, node_456"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('cms_page_id') border-red-300 @enderror">
                        @error('cms_page_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 text-sm mt-1">The ID of this page in your CMS (optional)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status *
                        </label>
                        <select name="status"
                                id="status"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('status') border-red-300 @enderror">
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ old('status', $page->status) === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                            Priority
                        </label>
                        <select name="priority"
                                id="priority"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('priority') border-red-300 @enderror">
                            @foreach($priorities as $value => $label)
                                <option value="{{ $value }}" {{ old('priority', $page->priority) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Metadata -->
                <div x-data="{
                    metadata: @json(old('metadata', $page->metadata ?? [])),
                    addMetaField() {
                        this.metadata.push({ key: '', value: '' });
                    },
                    removeMetaField(index) {
                        this.metadata.splice(index, 1);
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Metadata
                    </label>
                    <div class="space-y-3">
                        <template x-for="(meta, index) in metadata" :key="index">
                            <div class="flex space-x-3 items-center">
                                <input type="text"
                                       :name="`metadata[${index}][key]`"
                                       x-model="meta.key"
                                       placeholder="Key"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <input type="text"
                                       :name="`metadata[${index}][value]`"
                                       x-model="meta.value"
                                       placeholder="Value"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button"
                                        @click="removeMetaField(index)"
                                        class="text-red-600 hover:text-red-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button type="button"
                                @click="addMetaField()"
                                class="flex items-center text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Metadata Field
                        </button>
                    </div>
                    <p class="text-gray-500 text-sm mt-1">Additional metadata for this page (optional)</p>
                </div>

                <!-- Page Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Page Information</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Created:</span>
                            <span class="ml-2 text-gray-900">{{ $page->created_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Last Updated:</span>
                            <span class="ml-2 text-gray-900">{{ $page->updated_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Total Generations:</span>
                            <span class="ml-2 text-gray-900">{{ $page->generations->count() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Page ID:</span>
                            <span class="ml-2 text-gray-900 font-mono">{{ $page->id }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('tenant.pages.show', $page) }}" class="btn-secondary">
                            Cancel
                        </a>
                        <a href="{{ route('tenant.pages.index') }}" class="text-gray-600 hover:text-gray-900">
                            Back to Pages
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button type="button"
                                onclick="if(confirm('Are you sure you want to delete this page? This action cannot be undone.')) { document.getElementById('delete-form-{{ $page->id }}').submit(); }"
                                class="btn-danger">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Page
                        </button>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Page
                        </button>
                    </div>
                </div>
            </form>

            <!-- Delete Form (outside main form) -->
            <form id="delete-form-{{ $page->id }}"
                  method="POST"
                  action="{{ route('tenant.pages.destroy', $page) }}"
                  style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
@endsection