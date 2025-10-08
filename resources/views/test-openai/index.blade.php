<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OpenAI Service - Test Interface</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="testApp()">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">OpenAI Service - Test Interface</h1>
            <p class="mt-2 text-sm text-gray-600">Testa tutti i metodi del servizio OpenAI da browser</p>

            <!-- Service Status -->
            <div class="mt-4 flex items-center space-x-4">
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 mr-2">Service Status:</span>
                    @if($isUsingMock)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                            🧪 Mock Mode
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            ✅ Real API
                        </span>
                    @endif
                </div>
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 mr-2">Available Models:</span>
                    <span class="text-sm text-gray-600">{{ count($availableModels) }} modelli</span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'chat'"
                        :class="activeTab === 'chat' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Chat Completion
                </button>
                <button @click="activeTab = 'completion'"
                        :class="activeTab === 'completion' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Simple Completion
                </button>
                <button @click="activeTab = 'json'"
                        :class="activeTab === 'json' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    JSON Parsing
                </button>
                <button @click="activeTab = 'embeddings'"
                        :class="activeTab === 'embeddings' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Embeddings
                </button>
                <button @click="activeTab = 'error'"
                        :class="activeTab === 'error' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Error Handling
                </button>
            </nav>
        </div>

        <!-- Chat Completion Tab -->
        <div x-show="activeTab === 'chat'" class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Chat Completion Test</h2>

                <form @submit.prevent="testChat()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea x-model="chatForm.message" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Scrivi un messaggio per l'AI..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Use Case</label>
                            <select x-model="chatForm.use_case" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Default</option>
                                <option value="campaigns">Campaigns (temp 0.8, 1000 tokens)</option>
                                <option value="articles">Articles (temp 0.7, 4000 tokens)</option>
                                <option value="seo">SEO (temp 0.5, 2000 tokens)</option>
                                <option value="internal_links">Internal Links (temp 0.5, 1500 tokens)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Model (opzionale)</label>
                            <select x-model="chatForm.model" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Auto (from use case)</option>
                                @foreach($availableModels as $model)
                                    <option value="{{ $model }}">{{ $model }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading">Test Chat</span>
                        <span x-show="loading">Testing...</span>
                    </button>
                </form>
            </div>

            <!-- Result -->
            <div x-show="result" class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Result</h3>
                <div class="space-y-3">
                    <div x-show="result?.success === true" class="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p class="text-sm font-medium text-green-800 mb-2">✅ Success</p>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="result?.result?.content"></p>
                        <div class="mt-3 flex space-x-4 text-xs text-gray-600">
                            <span>Tokens: <strong x-text="result?.result?.tokens_used"></strong></span>
                            <span>Model: <strong x-text="result?.result?.model"></strong></span>
                            <span>Finish: <strong x-text="result?.result?.finish_reason"></strong></span>
                        </div>
                    </div>

                    <div x-show="result?.success === false" class="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm font-medium text-red-800 mb-2">❌ Error</p>
                        <p class="text-sm text-red-700" x-text="result?.error"></p>
                    </div>

                    <details class="text-xs">
                        <summary class="cursor-pointer text-gray-600 hover:text-gray-900">Raw JSON Response</summary>
                        <pre class="mt-2 p-3 bg-gray-100 rounded overflow-x-auto" x-text="JSON.stringify(result, null, 2)"></pre>
                    </details>
                </div>
            </div>
        </div>

        <!-- Simple Completion Tab -->
        <div x-show="activeTab === 'completion'" class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Simple Completion Test</h2>

                <form @submit.prevent="testCompletion()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prompt</label>
                        <textarea x-model="completionForm.prompt" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Scrivi un prompt..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">System Message (opzionale)</label>
                        <input type="text" x-model="completionForm.system_message"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="Es: You are a helpful assistant">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model (opzionale)</label>
                        <select x-model="completionForm.model" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Default</option>
                            @foreach($availableModels as $model)
                                <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!loading">Test Completion</span>
                        <span x-show="loading">Testing...</span>
                    </button>
                </form>
            </div>

            <!-- Result (same structure) -->
            <div x-show="result" class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Result</h3>
                <div class="space-y-3">
                    <div x-show="result?.success === true" class="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p class="text-sm font-medium text-green-800 mb-2">✅ Success</p>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="result?.result?.content"></p>
                        <div class="mt-3 flex space-x-4 text-xs text-gray-600">
                            <span>Tokens: <strong x-text="result?.result?.tokens_used"></strong></span>
                            <span>Model: <strong x-text="result?.result?.model"></strong></span>
                        </div>
                    </div>
                    <div x-show="result?.success === false" class="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm font-medium text-red-800 mb-2">❌ Error</p>
                        <p class="text-sm text-red-700" x-text="result?.error"></p>
                    </div>
                    <details class="text-xs">
                        <summary class="cursor-pointer text-gray-600 hover:text-gray-900">Raw JSON Response</summary>
                        <pre class="mt-2 p-3 bg-gray-100 rounded overflow-x-auto" x-text="JSON.stringify(result, null, 2)"></pre>
                    </details>
                </div>
            </div>
        </div>

        <!-- JSON Parsing Tab -->
        <div x-show="activeTab === 'json'" class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">JSON Parsing Test</h2>

                <form @submit.prevent="testJSON()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prompt (richiedi JSON)</label>
                        <textarea x-model="jsonForm.prompt" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                  placeholder="Es: Generate JSON with user data: name, email, age"></textarea>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!loading">Test JSON Parsing</span>
                        <span x-show="loading">Testing...</span>
                    </button>
                </form>
            </div>

            <div x-show="result" class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Result</h3>
                <div class="space-y-3">
                    <div x-show="result?.success === true" class="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p class="text-sm font-medium text-green-800 mb-2">✅ JSON Parsed Successfully</p>
                        <pre class="mt-2 p-3 bg-white rounded border text-xs overflow-x-auto" x-text="JSON.stringify(result?.result?.parsed, null, 2)"></pre>
                        <div class="mt-3 flex space-x-4 text-xs text-gray-600">
                            <span>Tokens: <strong x-text="result?.result?.tokens_used"></strong></span>
                        </div>
                    </div>
                    <div x-show="result?.success === false" class="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm font-medium text-red-800 mb-2">❌ Error</p>
                        <p class="text-sm text-red-700" x-text="result?.error"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Embeddings Tab -->
        <div x-show="activeTab === 'embeddings'" class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Embeddings Test</h2>

                <form @submit.prevent="testEmbeddings()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Text</label>
                        <textarea x-model="embeddingsForm.text" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                  placeholder="Inserisci testo per generare embeddings..."></textarea>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!loading">Test Embeddings</span>
                        <span x-show="loading">Testing...</span>
                    </button>
                </form>
            </div>

            <div x-show="result" class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Result</h3>
                <div class="space-y-3">
                    <div x-show="result?.success === true" class="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p class="text-sm font-medium text-green-800 mb-2">✅ Embeddings Generated</p>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Embeddings Count:</span>
                                <strong x-text="result?.result?.embeddings_count"></strong>
                            </div>
                            <div>
                                <span class="text-gray-600">Dimensions:</span>
                                <strong x-text="result?.result?.embedding_dimensions"></strong>
                            </div>
                            <div>
                                <span class="text-gray-600">Tokens Used:</span>
                                <strong x-text="result?.result?.tokens_used"></strong>
                            </div>
                            <div>
                                <span class="text-gray-600">Model:</span>
                                <strong x-text="result?.result?.model"></strong>
                            </div>
                        </div>
                    </div>
                    <div x-show="result?.success === false" class="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm font-medium text-red-800 mb-2">❌ Error</p>
                        <p class="text-sm text-red-700" x-text="result?.error"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Handling Tab -->
        <div x-show="activeTab === 'error'" class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Error Handling Test</h2>
                <p class="text-sm text-gray-600 mb-4">Questo test invia un prompt molto lungo per verificare la gestione degli errori.</p>

                <button @click="testError()" :disabled="loading"
                        class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 disabled:opacity-50">
                    <span x-show="!loading">Trigger Error Test</span>
                    <span x-show="loading">Testing...</span>
                </button>
            </div>

            <div x-show="result" class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Result</h3>
                <div class="space-y-3">
                    <div x-show="result?.error_handled" class="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-sm font-medium text-yellow-800 mb-2">⚠️ Error Handled Correctly</p>
                        <p class="text-sm text-yellow-700" x-text="result?.error"></p>
                    </div>
                    <details class="text-xs">
                        <summary class="cursor-pointer text-gray-600 hover:text-gray-900">Raw Response</summary>
                        <pre class="mt-2 p-3 bg-gray-100 rounded overflow-x-auto" x-text="JSON.stringify(result, null, 2)"></pre>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testApp() {
            return {
                activeTab: 'chat',
                loading: false,
                result: null,

                chatForm: {
                    message: 'Hello! Can you help me with Laravel development?',
                    model: '',
                    use_case: 'default'
                },

                completionForm: {
                    prompt: 'Write a short paragraph about artificial intelligence.',
                    system_message: '',
                    model: ''
                },

                jsonForm: {
                    prompt: 'Generate a JSON object with fields: name (string), age (number), skills (array of strings)'
                },

                embeddingsForm: {
                    text: 'Laravel is a web application framework with expressive, elegant syntax.'
                },

                async testChat() {
                    this.loading = true;
                    this.result = null;

                    try {
                        const response = await fetch('/test-openai/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.chatForm)
                        });

                        this.result = await response.json();
                    } catch (error) {
                        this.result = { success: false, error: error.message };
                    } finally {
                        this.loading = false;
                    }
                },

                async testCompletion() {
                    this.loading = true;
                    this.result = null;

                    try {
                        const response = await fetch('/test-openai/completion', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.completionForm)
                        });

                        this.result = await response.json();
                    } catch (error) {
                        this.result = { success: false, error: error.message };
                    } finally {
                        this.loading = false;
                    }
                },

                async testJSON() {
                    this.loading = true;
                    this.result = null;

                    try {
                        const response = await fetch('/test-openai/json', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.jsonForm)
                        });

                        this.result = await response.json();
                    } catch (error) {
                        this.result = { success: false, error: error.message };
                    } finally {
                        this.loading = false;
                    }
                },

                async testEmbeddings() {
                    this.loading = true;
                    this.result = null;

                    try {
                        const response = await fetch('/test-openai/embeddings', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.embeddingsForm)
                        });

                        this.result = await response.json();
                    } catch (error) {
                        this.result = { success: false, error: error.message };
                    } finally {
                        this.loading = false;
                    }
                },

                async testError() {
                    this.loading = true;
                    this.result = null;

                    try {
                        const response = await fetch('/test-openai/error', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        this.result = await response.json();
                    } catch (error) {
                        this.result = { success: false, error: error.message };
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
