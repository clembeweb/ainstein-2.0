@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Pages Management</h2>
                        <p class="text-gray-600 mt-1">Manage your SEO pages and content generation</p>
                    </div>
                    <a href="{{ route('tenant.pages.create') }}" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Page
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('tenant.pages.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text"
                                   name="search"
                                   id="search"
                                   value="{{ request('search') }}"
                                   placeholder="URL, keyword, or category"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select name="category" id="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                                        {{ ucfirst($category) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Language Filter -->
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                            <select name="language" id="language" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Languages</option>
                                @foreach($languages as $lang)
                                    <option value="{{ $lang }}" {{ request('language') === $lang ? 'selected' : '' }}>
                                        {{ strtoupper($lang) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- CMS Type Filter -->
                        <div>
                            <label for="cms_type" class="block text-sm font-medium text-gray-700 mb-1">CMS Type</label>
                            <select name="cms_type" id="cms_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All CMS Types</option>
                                @foreach($cmsTypes as $cmsType)
                                    <option value="{{ $cmsType }}" {{ request('cms_type') === $cmsType ? 'selected' : '' }}>
                                        {{ ucfirst($cmsType) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <div class="flex space-x-2">
                            <button type="submit" class="btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Filter
                            </button>
                            <a href="{{ route('tenant.pages.index') }}" class="btn-secondary">
                                Clear Filters
                            </a>
                        </div>

                        <!-- Bulk Actions -->
                        <div x-data="{ selectedPages: [], showBulkActions: false }" x-init="$watch('selectedPages', value => showBulkActions = value.length > 0)">
                            <div x-show="showBulkActions" x-transition class="flex space-x-2">
                                <select x-ref="bulkStatus" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Change Status</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <button type="button"
                                        @click="bulkUpdateStatus()"
                                        class="btn-secondary">
                                    Update Status
                                </button>
                                <button type="button"
                                        @click="bulkDelete()"
                                        class="btn-danger">
                                    Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pages Table -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                @if($pages->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'url_path', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                       class="flex items-center space-x-1 hover:text-gray-700">
                                        <span>URL Path</span>
                                        @if(request('sort_by') === 'url_path')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if(request('sort_order') === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Keyword
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Language
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                                       class="flex items-center space-x-1 hover:text-gray-700">
                                        <span>Created</span>
                                        @if(request('sort_by') === 'created_at' || !request('sort_by'))
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if(request('sort_order') === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Generations
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pages as $page)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox"
                                               value="{{ $page->id }}"
                                               class="page-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <a href="{{ route('tenant.pages.show', $page) }}"
                                               class="text-blue-600 hover:text-blue-900 font-medium truncate max-w-xs">
                                                {{ $page->url_path }}
                                            </a>
                                            @if($page->cms_type)
                                                <span class="text-xs text-gray-500 mt-1">{{ ucfirst($page->cms_type) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">{{ $page->keyword }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($page->category)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $page->category }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ strtoupper($page->language) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $page->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($page->generations_count > 0)
                                            <span class="text-blue-600 font-medium">{{ $page->generations_count }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('tenant.pages.show', $page) }}"
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                            <a href="{{ route('tenant.pages.edit', $page) }}"
                                               class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            <form method="POST"
                                                  action="{{ route('tenant.pages.destroy', $page) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this page?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No pages found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first page.</p>
                        <div class="mt-6">
                            <a href="{{ route('tenant.pages.create') }}" class="btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create Page
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($pages->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $pages->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Select all functionality
document.getElementById('select-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.page-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateSelectedPages();
});

// Individual checkbox functionality
document.querySelectorAll('.page-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedPages);
});

function updateSelectedPages() {
    const checkedBoxes = document.querySelectorAll('.page-checkbox:checked');
    const selectedPages = Array.from(checkedBoxes).map(cb => cb.value);

    // Update Alpine.js data
    window.dispatchEvent(new CustomEvent('update-selected-pages', {
        detail: { selectedPages }
    }));
}

// Bulk actions
function bulkUpdateStatus() {
    const checkedBoxes = document.querySelectorAll('.page-checkbox:checked');
    const pageIds = Array.from(checkedBoxes).map(cb => cb.value);
    const status = document.querySelector('[x-ref="bulkStatus"]').value;

    if (!status) {
        alert('Please select a status');
        return;
    }

    if (!confirm(`Are you sure you want to update ${pageIds.length} pages to ${status}?`)) {
        return;
    }

    fetch('{{ route("tenant.pages.bulk-status") }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            page_ids: pageIds,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error updating pages');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating pages');
    });
}

function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.page-checkbox:checked');
    const pageIds = Array.from(checkedBoxes).map(cb => cb.value);

    if (!confirm(`Are you sure you want to delete ${pageIds.length} pages? This action cannot be undone.`)) {
        return;
    }

    fetch('{{ route("tenant.pages.bulk-delete") }}', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            page_ids: pageIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error deleting pages');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting pages');
    });
}
</script>
@endsection