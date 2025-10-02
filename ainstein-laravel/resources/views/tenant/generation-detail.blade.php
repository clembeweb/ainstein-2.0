@extends('layouts.tenant')

@section('title', 'Generation Details')
@section('page-title', 'Generation Details')

@section('content')
<div class="space-y-6">
    <!-- Back Navigation -->
    <div class="flex items-center space-x-4">
        <a href="{{ route('tenant.generations') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Back to Generations
        </a>
    </div>

    <!-- Generation Info -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Generation #{{ $generation->id }}</h3>
                    @if($generation->page)
                        <p class="text-gray-600">{{ $generation->page->url_path }}</p>
                    @endif
                </div>
                <div class="text-right">
                    @if($generation->status === 'completed')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check mr-1"></i>Completed
                        </span>
                    @elseif($generation->status === 'failed')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times mr-1"></i>Failed
                        </span>
                    @elseif($generation->status === 'processing')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Processing
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Generation Details -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Prompt Type</label>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $generation->prompt_type }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">AI Model</label>
                        <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                            {{ $generation->ai_model ?? 'Default' }}
                        </span>
                    </div>

                    @if($generation->tokens_used)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tokens Used</label>
                            <div class="flex items-center mt-1">
                                <i class="fas fa-coins text-amber-500 mr-1"></i>
                                <span class="font-medium">{{ number_format($generation->tokens_used) }}</span>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Created</label>
                        <p class="text-sm text-gray-900">{{ $generation->created_at->format('M j, Y \a\t H:i') }}</p>
                    </div>

                    @if($generation->updated_at->ne($generation->created_at))
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                            <p class="text-sm text-gray-900">{{ $generation->updated_at->format('M j, Y \a\t H:i') }}</p>
                        </div>
                    @endif
                </div>

                <!-- Page Details -->
                @if($generation->page)
                    <div class="space-y-4">
                        <h4 class="font-medium text-gray-900">Page Information</h4>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">URL Path</label>
                            <p class="text-sm text-gray-900">{{ $generation->page->url_path }}</p>
                        </div>

                        @if($generation->page->keyword)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Keyword</label>
                                <p class="text-sm text-gray-900">{{ $generation->page->keyword }}</p>
                            </div>
                        @endif

                        @if($generation->page->category)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ ucfirst($generation->page->category) }}
                                </span>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Language</label>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ strtoupper($generation->page->language ?? 'EN') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Error Details (if failed) -->
    @if($generation->status === 'failed' && $generation->error)
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                <h4 class="text-lg font-medium text-red-800">Error Details</h4>
            </div>
            <div class="bg-red-100 rounded-lg p-4">
                <pre class="text-sm text-red-800 whitespace-pre-wrap">{{ $generation->error }}</pre>
            </div>
            <div class="mt-4">
                <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-redo mr-2"></i>Retry Generation
                </button>
            </div>
        </div>
    @endif

    <!-- Generated Content -->
    @if($generation->status === 'completed' && $generation->generated_content)
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <div class="flex justify-between items-center">
                    <h4 class="text-lg font-semibold text-gray-900">Generated Content</h4>
                    <button onclick="copyToClipboard('generated-content')" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-copy mr-2"></i>Copy Content
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div id="generated-content" class="prose max-w-none">
                    {!! nl2br(e($generation->generated_content)) !!}
                </div>
            </div>
        </div>
    @endif

    <!-- Meta Information -->
    @if($generation->status === 'completed' && ($generation->meta_title || $generation->meta_description))
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h4 class="text-lg font-semibold text-gray-900">Meta Information</h4>
            </div>
            <div class="p-6 space-y-4">
                @if($generation->meta_title)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-900">{{ $generation->meta_title }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ strlen($generation->meta_title) }} characters</p>
                        </div>
                        <button onclick="copyToClipboard('meta-title')" class="mt-2 text-amber-600 hover:text-amber-700 text-sm">
                            <i class="fas fa-copy mr-1"></i>Copy Title
                        </button>
                        <div id="meta-title" class="hidden">{{ $generation->meta_title }}</div>
                    </div>
                @endif

                @if($generation->meta_description)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-900">{{ $generation->meta_description }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ strlen($generation->meta_description) }} characters</p>
                        </div>
                        <button onclick="copyToClipboard('meta-description')" class="mt-2 text-amber-600 hover:text-amber-700 text-sm">
                            <i class="fas fa-copy mr-1"></i>Copy Description
                        </button>
                        <div id="meta-description" class="hidden">{{ $generation->meta_description }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Actions</h4>
        <div class="flex flex-wrap gap-3">
            @if($generation->status === 'failed')
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-redo mr-2"></i>Retry Generation
                </button>
            @endif

            @if($generation->status === 'completed' && $generation->generated_content)
                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-download mr-2"></i>Export Content
                </button>
            @endif

            <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-trash mr-2"></i>Delete Generation
            </button>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = elementId === 'generated-content'
        ? element.innerText
        : element.textContent;

    navigator.clipboard.writeText(text).then(function() {
        // Show success message (you can implement a toast notification here)
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
        button.classList.remove('bg-amber-600', 'hover:bg-amber-700');
        button.classList.add('bg-green-600', 'hover:bg-green-700');

        setTimeout(function() {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-amber-600', 'hover:bg-amber-700');
        }, 2000);
    });
}
</script>
@endsection