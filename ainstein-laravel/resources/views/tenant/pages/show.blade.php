@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h2 class="text-2xl font-bold text-gray-900 truncate">{{ $page->url_path }}</h2>
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'inactive' => 'bg-gray-100 text-gray-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'archived' => 'bg-red-100 text-red-800'
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$page->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($page->status) }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                            <span>Target: <strong>{{ $page->keyword }}</strong></span>
                            @if($page->category)
                                <span>Category: <strong>{{ $page->category }}</strong></span>
                            @endif
                            <span>Language: <strong>{{ strtoupper($page->language) }}</strong></span>
                            <span>Created: <strong>{{ $page->created_at->format('M j, Y') }}</strong></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('tenant.pages.edit', $page) }}" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Page
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Page Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Page Details</h3>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">URL Path</dt>
                                <dd class="mt-1 text-sm text-gray-900 break-all">{{ $page->url_path }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Target Keyword</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $page->keyword }}</dd>
                            </div>
                            @if($page->category)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Category</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $page->category }}</dd>
                            </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Language</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ strtoupper($page->language) }}</dd>
                            </div>
                            @if($page->cms_type)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">CMS Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($page->cms_type) }}</dd>
                            </div>
                            @endif
                            @if($page->cms_page_id)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">CMS Page ID</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $page->cms_page_id }}</dd>
                            </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @php
                                        $priorities = [1 => 'Low', 2 => 'Normal', 3 => 'High', 4 => 'Critical'];
                                    @endphp
                                    {{ $priorities[$page->priority] ?? 'Normal' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $page->updated_at->format('M j, Y g:i A') }}</dd>
                            </div>
                        </dl>

                        @if($page->metadata && !empty($page->metadata))
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Metadata</h4>
                            <div class="space-y-2">
                                @foreach($page->metadata as $key => $value)
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-gray-700">{{ $key }}:</span>
                                    <span class="text-gray-900 ml-2">{{ $value }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Generations -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Recent Content Generations</h3>
                            <a href="#" class="btn-primary text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Generate Content
                            </a>
                        </div>

                        @if($page->generations->count() > 0)
                            <div class="space-y-4">
                                @foreach($page->generations as $generation)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'processing' => 'bg-blue-100 text-blue-800',
                                                        'completed' => 'bg-green-100 text-green-800',
                                                        'failed' => 'bg-red-100 text-red-800'
                                                    ];
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$generation->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($generation->status) }}
                                                </span>
                                                <span class="text-sm text-gray-500">
                                                    {{ $generation->created_at->format('M j, Y g:i A') }}
                                                </span>
                                                @if($generation->tokens_used)
                                                    <span class="text-sm text-gray-500">
                                                        {{ number_format($generation->tokens_used) }} tokens
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('tenant.generation.show', $generation) }}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                            </div>
                                        </div>
                                        @if($generation->content && $generation->status === 'completed')
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-600 line-clamp-3">{{ Str::limit(strip_tags($generation->content), 200) }}</p>
                                            </div>
                                        @endif
                                        @if($generation->error_message && $generation->status === 'failed')
                                            <div class="mt-2">
                                                <p class="text-sm text-red-600">Error: {{ $generation->error_message }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No content generated yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Start by generating content for this page.</p>
                                <div class="mt-6">
                                    <a href="#" class="btn-primary">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Generate Content
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Statistics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Statistics</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Total Generations</span>
                                <span class="text-sm font-medium text-gray-900">{{ $stats['total_generations'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Completed</span>
                                <span class="text-sm font-medium text-green-600">{{ $stats['completed_generations'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Failed</span>
                                <span class="text-sm font-medium text-red-600">{{ $stats['failed_generations'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Pending</span>
                                <span class="text-sm font-medium text-yellow-600">{{ $stats['pending_generations'] }}</span>
                            </div>
                            @if($stats['last_generation'])
                            <div class="pt-2 border-t border-gray-200">
                                <span class="text-sm text-gray-500">Last Generation</span>
                                <p class="text-sm font-medium text-gray-900">{{ $stats['last_generation']->created_at->format('M j, Y') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="#" class="w-full btn-primary text-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Generate Content
                            </a>
                            <a href="{{ route('tenant.pages.edit', $page) }}" class="w-full btn-secondary text-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit Page
                            </a>
                            <form method="POST"
                                  action="{{ route('tenant.pages.destroy', $page) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this page? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full btn-danger text-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete Page
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Related Pages -->
                @if($page->category)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Related Pages</h3>
                            @php
                                $relatedPages = \App\Models\Page::where('tenant_id', $page->tenant_id)
                                    ->where('category', $page->category)
                                    ->where('id', '!=', $page->id)
                                    ->limit(5)
                                    ->get();
                            @endphp
                            @if($relatedPages->count() > 0)
                                <div class="space-y-2">
                                    @foreach($relatedPages as $relatedPage)
                                        <a href="{{ route('tenant.pages.show', $relatedPage) }}"
                                           class="block text-sm text-blue-600 hover:text-blue-900 truncate">
                                            {{ $relatedPage->url_path }}
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No related pages found</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection