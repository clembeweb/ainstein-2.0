<div class="space-y-6">
    <!-- Header with Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Content Generations</h3>
                <p class="text-gray-600">Monitor and manage your AI content generations</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('tenant.content.index') }}" class="mt-6">
            <input type="hidden" name="tab" value="generations">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        @foreach($generationStatuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by page, keyword..."
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('tenant.content.index', ['tab' => 'generations']) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Generations List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prompt Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AI Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($generations as $generation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    @if($generation->content)
                                        <div class="font-medium text-gray-900">{{ $generation->content->url_path }}</div>
                                        @if($generation->content->keyword)
                                            <div class="text-sm text-gray-600">{{ $generation->content->keyword }}</div>
                                        @endif
                                    @else
                                        <div class="text-gray-400 italic">Page deleted</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $generation->prompt_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ $generation->ai_model ?? 'Default' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($generation->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>Completed
                                    </span>
                                @elseif($generation->status === 'failed')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times mr-1"></i>Failed
                                    </span>
                                @elseif($generation->status === 'processing')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>Processing
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <i class="fas fa-clock mr-1"></i>Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if($generation->tokens_used)
                                    <div class="flex items-center">
                                        <i class="fas fa-coins text-blue-500 mr-1"></i>
                                        {{ number_format($generation->tokens_used) }}
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div>{{ $generation->created_at->format('M j, Y') }}</div>
                                <div class="text-xs">{{ $generation->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="{{ route('tenant.content.show', $generation) }}" class="text-blue-600 hover:text-blue-800 p-1" title="View Details">
                                        <i class="fas fa-eye text-lg"></i>
                                    </a>
                                    @if($generation->status === 'completed' && $generation->generated_content)
                                        <a href="{{ route('tenant.content.edit', $generation) }}" class="text-purple-600 hover:text-purple-800 p-1" title="Edit">
                                            <i class="fas fa-edit text-lg"></i>
                                        </a>
                                        <button onclick="copyToClipboard('{{ addslashes($generation->generated_content) }}')" class="text-green-600 hover:text-green-800 p-1" title="Copy Content">
                                            <i class="fas fa-copy text-lg"></i>
                                        </button>
                                    @endif
                                    @if($generation->status === 'failed')
                                        <button class="text-blue-600 hover:text-blue-800 p-1" title="Retry">
                                            <i class="fas fa-redo text-lg"></i>
                                        </button>
                                    @endif
                                    <form method="POST" action="{{ route('tenant.content.destroy', $generation) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this generation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 p-1" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-magic text-4xl mb-4"></i>
                                    <p class="text-lg">No content generations found</p>
                                    <p class="text-sm">Start generating content from your pages to see results here</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($generations->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $generations->appends(array_merge(request()->query(), ['tab' => 'generations']))->links() }}
            </div>
        @endif
    </div>

    @if($generations->count() > 0)
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-download mr-2"></i>Export All Completed
                </button>
                <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-trash mr-2"></i>Delete Failed
                </button>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-redo mr-2"></i>Retry Failed
                </button>
            </div>
        </div>
    @endif
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Content copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>
