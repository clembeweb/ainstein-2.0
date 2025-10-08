<div class="space-y-6">
    <!-- Header with Search and Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Manage Pages</h3>
                <p class="text-gray-600">View and manage your content pages</p>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('tenant.pages.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>Create Page
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <form method="GET" action="{{ route('tenant.content.index') }}" class="mt-6">
            <input type="hidden" name="tab" value="pages">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search pages, keywords, categories..."
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <select name="category" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="{{ route('tenant.content.index', ['tab' => 'pages']) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Pages List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Language</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pages as $page)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $page->url_path }}</div>
                                    @if($page->keyword)
                                        <div class="text-sm text-gray-600">{{ $page->keyword }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if($page->category)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ ucfirst($page->category) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ strtoupper($page->language ?? 'EN') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium">{{ $page->generations->count() }}</span>
                                    @if($page->generations->where('status', 'completed')->count() > 0)
                                        <span class="text-green-600 text-xs">
                                            ({{ $page->generations->where('status', 'completed')->count() }} completed)
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($page->is_published)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $page->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('tenant.pages.edit', $page->id) }}" class="text-blue-600 hover:text-blue-700" title="Edit Page">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('tenant.content.create', ['page_id' => $page->id]) }}" class="text-purple-600 hover:text-purple-700" title="Generate Content">
                                        <i class="fas fa-magic"></i>
                                    </a>
                                    <form method="POST" action="{{ route('tenant.pages.destroy', $page->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this page? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700" title="Delete Page">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-file-alt text-4xl mb-4"></i>
                                    <p class="text-lg">No pages found</p>
                                    <p class="text-sm">Create your first page to get started with content generation</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($pages->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $pages->appends(array_merge(request()->query(), ['tab' => 'pages']))->links() }}
            </div>
        @endif
    </div>
</div>
