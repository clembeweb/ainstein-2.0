<x-tenant-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit Content Generation') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Modify the AI-generated content and notes
                </p>
            </div>
            <a href="{{ route('tenant.content.index', ['tab' => 'generations']) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>Back to Generations
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Generation Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Page URL</label>
                            <div class="text-gray-900">
                                @if($generation->content)
                                    {{ $generation->content->url_path }}
                                @else
                                    <span class="text-gray-400 italic">Page deleted</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prompt Type</label>
                            <div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $generation->prompt_type }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">AI Model</label>
                            <div>
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                    {{ $generation->ai_model ?? 'Default' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <div>
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
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tokens Used</label>
                            <div class="text-gray-900">
                                @if($generation->tokens_used)
                                    <i class="fas fa-coins text-blue-500 mr-1"></i>
                                    {{ number_format($generation->tokens_used) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                            <div class="text-gray-900">
                                {{ $generation->created_at->format('M j, Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <form method="POST" action="{{ route('tenant.content.update', $generation) }}">
                @csrf
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Generated Content</h3>

                        <div class="mb-4">
                            <label for="generated_content" class="block text-sm font-medium text-gray-700 mb-2">
                                Content <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                name="generated_content"
                                id="generated_content"
                                rows="15"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                required>{{ old('generated_content', $generation->generated_content) }}</textarea>
                            @error('generated_content')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">
                                Edit the AI-generated content as needed. Changes will be saved to this generation record.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Notes (Optional)
                            </label>
                            <textarea
                                name="notes"
                                id="notes"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Add any notes or comments about this generation...">{{ old('notes', $generation->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Character Count -->
                        <div class="flex items-center justify-between text-sm text-gray-600 mb-6">
                            <div>
                                <i class="fas fa-text-width mr-1"></i>
                                Characters: <span id="charCount">{{ strlen($generation->generated_content ?? '') }}</span>
                            </div>
                            <div>
                                <i class="fas fa-align-left mr-1"></i>
                                Words: <span id="wordCount">{{ str_word_count($generation->generated_content ?? '') }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                            <a href="{{ route('tenant.content.index', ['tab' => 'generations']) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg">
                                Cancel
                            </a>
                            <button type="button" onclick="copyToClipboard()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-copy mr-2"></i>Copy Content
                            </button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Original Prompt (if available) -->
            @if($generation->prompt)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Original Prompt Used</h3>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="font-medium text-gray-700 mb-2">{{ $generation->prompt->name }}</div>
                            <div class="text-sm text-gray-600 whitespace-pre-wrap">{{ $generation->prompt->content }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Character and word count
        const textarea = document.getElementById('generated_content');
        const charCount = document.getElementById('charCount');
        const wordCount = document.getElementById('wordCount');

        function updateCounts() {
            const text = textarea.value;
            charCount.textContent = text.length;
            wordCount.textContent = text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
        }

        textarea.addEventListener('input', updateCounts);

        // Copy to clipboard function
        function copyToClipboard() {
            const content = textarea.value;
            navigator.clipboard.writeText(content).then(function() {
                // Show success feedback
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
                btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                btn.classList.add('bg-green-700');

                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-green-700');
                    btn.classList.add('bg-green-600', 'hover:bg-green-700');
                }, 2000);
            }, function(err) {
                console.error('Could not copy text: ', err);
                alert('Failed to copy content to clipboard');
            });
        }
    </script>
</x-tenant-layout>
