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
                            <h2 class="text-2xl font-bold text-gray-900">{{ $prompt->name }}</h2>
                            @if($prompt->is_system)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    System Prompt
                                </span>
                            @endif
                            @if($prompt->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Inactive
                                </span>
                            @endif
                        </div>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                            @if($prompt->alias)
                                <span>Alias: <strong class="font-mono">{{ $prompt->alias }}</strong></span>
                            @endif
                            @if($prompt->category)
                                <span>Category: <strong>{{ $prompt->category }}</strong></span>
                            @endif
                            <span>Created: <strong>{{ $prompt->created_at->format('M j, Y') }}</strong></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if(!$prompt->is_system)
                            <a href="{{ route('tenant.prompts.edit', $prompt) }}" class="btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit Prompt
                            </a>
                        @endif
                        <form method="POST" action="{{ route('tenant.prompts.duplicate', $prompt) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Duplicate
                            </button>
                        </form>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Description -->
                @if($prompt->description)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Description</h3>
                            <p class="text-gray-700">{{ $prompt->description }}</p>
                        </div>
                    </div>
                @endif

                <!-- Template -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Template</h3>
                            <button onclick="copyTemplate()" class="btn-secondary text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy
                            </button>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <pre id="template-content" class="text-sm text-gray-900 whitespace-pre-wrap font-mono">{{ $prompt->template }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Variables -->
                @if($prompt->variables && count($prompt->variables) > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Variables</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($prompt->variables as $variable)
                                    <div class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 rounded-md">
                                        <span class="text-sm font-mono text-blue-900">{{{{ $variable }}}}</span>
                                    </div>
                                @endforeach
                            </div>
                            <p class="text-gray-500 text-sm mt-3">These variables can be replaced with dynamic content during generation</p>
                        </div>
                    </div>
                @endif

                <!-- Usage Examples -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Usage Example</h3>
                        <div class="space-y-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-green-900 mb-2">API Usage</h4>
                                <pre class="text-sm text-green-800 font-mono overflow-x-auto">curl -X POST /api/v1/content/generate \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt_id": "{{ $prompt->id }}",
    "variables": {
      @if($prompt->variables && count($prompt->variables) > 0)
@foreach($prompt->variables as $index => $variable)
"{{ $variable }}": "example_value"{{ $index < count($prompt->variables) - 1 ? ',' : '' }}
@endforeach
@endif
    },
    "page_id": "your_page_id"
  }'</pre>
                            </div>
                            @if($prompt->alias)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-blue-900 mb-2">Using Alias</h4>
                                    <pre class="text-sm text-blue-800 font-mono overflow-x-auto">curl -X POST /api/v1/content/generate \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt_alias": "{{ $prompt->alias }}",
    "variables": { ... },
    "page_id": "your_page_id"
  }'</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Prompt Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Prompt Information</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">ID</dt>
                                <dd class="text-sm text-gray-900 font-mono break-all">{{ $prompt->id }}</dd>
                            </div>
                            @if($prompt->alias)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Alias</dt>
                                    <dd class="text-sm text-gray-900 font-mono">{{ $prompt->alias }}</dd>
                                </div>
                            @endif
                            @if($prompt->category)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                                    <dd class="text-sm text-gray-900">{{ $prompt->category }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type</dt>
                                <dd class="text-sm text-gray-900">{{ $prompt->is_system ? 'System' : 'Custom' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="text-sm text-gray-900">{{ $prompt->is_active ? 'Active' : 'Inactive' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Variables</dt>
                                <dd class="text-sm text-gray-900">{{ $prompt->variables ? count($prompt->variables) : 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="text-sm text-gray-900">{{ $prompt->created_at->format('M j, Y g:i A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="text-sm text-gray-900">{{ $prompt->updated_at->format('M j, Y g:i A') }}</dd>
                            </div>
                        </dl>
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
                                Use for Generation
                            </a>
                            <form method="POST" action="{{ route('tenant.prompts.duplicate', $prompt) }}" class="w-full">
                                @csrf
                                <button type="submit" class="w-full btn-secondary text-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Duplicate Prompt
                                </button>
                            </form>
                            @if(!$prompt->is_system)
                                <a href="{{ route('tenant.prompts.edit', $prompt) }}" class="w-full btn-secondary text-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Prompt
                                </a>
                                <form method="POST"
                                      action="{{ route('tenant.prompts.destroy', $prompt) }}"
                                      onsubmit="return confirm('Are you sure you want to delete this prompt? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full btn-danger text-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete Prompt
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Related Prompts -->
                @if($prompt->category)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Related Prompts</h3>
                            @php
                                $relatedPrompts = \App\Models\Prompt::query()
                                    ->where(function ($q) use ($prompt) {
                                        $q->where('tenant_id', $prompt->tenant_id)
                                          ->orWhere('is_system', true);
                                    })
                                    ->where('category', $prompt->category)
                                    ->where('id', '!=', $prompt->id)
                                    ->limit(5)
                                    ->get();
                            @endphp
                            @if($relatedPrompts->count() > 0)
                                <div class="space-y-2">
                                    @foreach($relatedPrompts as $relatedPrompt)
                                        <a href="{{ route('tenant.prompts.show', $relatedPrompt) }}"
                                           class="block text-sm text-blue-600 hover:text-blue-900">
                                            {{ $relatedPrompt->name }}
                                            @if($relatedPrompt->is_system)
                                                <span class="text-xs text-blue-500">(System)</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No related prompts found</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function copyTemplate() {
    const template = document.getElementById('template-content').textContent;
    navigator.clipboard.writeText(template).then(function() {
        // Show success message
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Copied!';
        button.classList.remove('btn-secondary');
        button.classList.add('btn-success');

        setTimeout(function() {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-secondary');
        }, 2000);
    }).catch(function() {
        alert('Failed to copy template. Please select and copy manually.');
    });
}
</script>
@endsection