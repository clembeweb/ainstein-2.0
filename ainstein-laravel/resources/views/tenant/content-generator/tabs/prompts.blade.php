<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Prompt Templates</h3>
                <p class="text-gray-600">Manage your content generation prompts</p>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('tenant.prompts.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>Create Prompt
                </a>
            </div>
        </div>
    </div>

    <!-- Prompts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($prompts as $prompt)
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-lg font-semibold text-gray-900">{{ $prompt->title }}</h4>
                                @if($prompt->is_system)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        System
                                    </span>
                                @endif
                                @if($prompt->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Inactive
                                    </span>
                                @endif
                            </div>

                            @if($prompt->description)
                                <p class="text-gray-600 mt-2">{{ $prompt->description }}</p>
                            @endif

                            <div class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Category:
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ ucfirst($prompt->category ?? 'general') }}
                                    </span>
                                </p>

                                <div class="bg-gray-50 rounded-lg p-4 mt-2">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Template Preview:</p>
                                    <div class="text-sm text-gray-600 font-mono max-h-24 overflow-y-auto">
                                        {{ Str::limit($prompt->prompt_text, 200) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Variables -->
                            @php
                                preg_match_all('/\{\{([^}]+)\}\}/', $prompt->prompt_text, $matches);
                                $variables = array_unique($matches[1]);
                            @endphp
                            @if(!empty($variables))
                                <div class="mt-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Variables:</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($variables as $variable)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ trim($variable) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 text-sm text-gray-500">
                                <p>Created: {{ $prompt->created_at->format('M j, Y') }}</p>
                                @if($prompt->updated_at->ne($prompt->created_at))
                                    <p>Updated: {{ $prompt->updated_at->format('M j, Y') }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="ml-4">
                            <div class="flex flex-col space-y-2">
                                @if(!$prompt->is_system)
                                    <a href="{{ route('tenant.prompts.edit', $prompt->id) }}" class="text-blue-600 hover:text-blue-700 p-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('tenant.prompts.duplicate', $prompt->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-700 p-2" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </form>
                                @if(!$prompt->is_system)
                                    <form method="POST" action="{{ route('tenant.prompts.destroy', $prompt->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this prompt?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700 p-2" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="text-gray-400">
                        <i class="fas fa-scroll text-4xl mb-4"></i>
                        <p class="text-lg">No prompts found</p>
                        <p class="text-sm">Create your first prompt template to start generating content</p>
                        <a href="{{ route('tenant.prompts.create') }}" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i>Create First Prompt
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($prompts->hasPages())
        <div class="bg-white rounded-lg shadow p-4">
            {{ $prompts->appends(array_merge(request()->query(), ['tab' => 'prompts']))->links() }}
        </div>
    @endif

    @if($prompts->count() > 0)
        <!-- Quick Stats -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Prompt Statistics</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $prompts->total() }}</div>
                    <div class="text-gray-600">Total Prompts</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $prompts->where('is_active', true)->count() }}</div>
                    <div class="text-gray-600">Active</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $prompts->where('is_system', true)->count() }}</div>
                    <div class="text-gray-600">System</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600">{{ $prompts->where('is_system', false)->count() }}</div>
                    <div class="text-gray-600">Custom</div>
                </div>
            </div>
        </div>
    @endif
</div>
