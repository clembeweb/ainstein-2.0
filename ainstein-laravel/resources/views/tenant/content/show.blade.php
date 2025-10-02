@extends('layouts.app')

@section('title', 'Content Generation Details')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-4">
                    <li>
                        <a href="{{ route('tenant.content.index') }}" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Content Generations</span>
                            <svg class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12L6.535 8.535A1 1 0 017.95 7.12L10 9.17l2.05-2.05a1 1 0 111.414 1.415L10 12z"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <a href="{{ route('tenant.content.index') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Content Generations</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-4 text-sm font-medium text-gray-500">Generation #{{ $generation->id }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status Card -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Generation Status</h3>
                            <span class="px-3 py-1 text-sm font-semibold rounded-full
                                @if($generation->status === 'completed') bg-green-100 text-green-800
                                @elseif($generation->status === 'processing') bg-yellow-100 text-yellow-800
                                @elseif($generation->status === 'failed') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($generation->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $generation->created_at->format('M j, Y g:i A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $generation->updated_at->format('M j, Y g:i A') }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generated Content -->
                @if($generation->status === 'completed' && $generation->generated_content)
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Generated Content</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="prose max-w-none">
                                {!! nl2br(e($generation->generated_content)) !!}
                            </div>
                        </div>
                    </div>
                @elseif($generation->status === 'failed' && $generation->error_message)
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 text-red-600">Error Details</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                                <div class="text-sm text-red-700">
                                    {{ $generation->error_message }}
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($generation->status === 'processing')
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                            <p class="mt-2 text-sm text-gray-600">Content is being generated...</p>
                        </div>
                    </div>
                @endif

                <!-- Prompt Template Used -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Prompt Template</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="bg-gray-50 rounded-md p-4">
                            <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ $generation->prompt_template }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Variables Used -->
                @if($generation->variables && count($generation->variables) > 0)
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Variables Used</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($generation->variables as $key => $value)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ $key }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Additional Instructions -->
                @if($generation->additional_instructions)
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Additional Instructions</h3>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700">{{ $generation->additional_instructions }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Page Information -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Target Page</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">URL Path</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $generation->page->url_path }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Keyword</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $generation->page->keyword }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if($generation->page->status === 'active') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($generation->page->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div class="pt-3">
                                <a href="{{ route('tenant.pages.show', $generation->page) }}" class="btn-secondary w-full text-center">
                                    View Page Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prompt Information -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Prompt Used</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $generation->prompt->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Category</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $generation->prompt->category }}</dd>
                            </div>
                            @if($generation->prompt->is_system)
                                <div>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        System Prompt
                                    </span>
                                </div>
                            @endif
                            <div class="pt-3">
                                <a href="{{ route('tenant.prompts.show', $generation->prompt) }}" class="btn-secondary w-full text-center">
                                    View Prompt Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Actions</h3>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        @if($generation->status === 'completed')
                            <a href="{{ route('tenant.content.create', ['page_id' => $generation->page_id, 'prompt_id' => $generation->prompt_id]) }}"
                               class="btn-primary w-full text-center">
                                Generate Again
                            </a>
                        @endif

                        <a href="{{ route('tenant.content.create', ['page_id' => $generation->page_id]) }}"
                           class="btn-secondary w-full text-center">
                            New Generation for Page
                        </a>

                        <a href="{{ route('tenant.content.index') }}"
                           class="btn-secondary w-full text-center">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection