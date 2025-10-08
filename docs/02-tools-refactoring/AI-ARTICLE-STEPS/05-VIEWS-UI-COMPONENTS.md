# AI Article Steps — Views & UI Components

**Documento**: 05 — Frontend Implementation with Blade + Alpine.js
**Progetto**: Ainstein Laravel Multi-Tenant Platform
**Tool**: AI Article Steps (Copy Article Generator)
**Views**: 10 Blade templates + Alpine.js components

---

## Indice
1. [Overview Frontend](#1-overview-frontend)
2. [Articles Dashboard](#2-articles-dashboard)
3. [Article Creation Form](#3-article-creation-form)
4. [Article Detail View](#4-article-detail-view)
5. [Article Editor](#5-article-editor)
6. [Keywords Management](#6-keywords-management)
7. [Templates Library](#7-templates-library)
8. [Generation Progress Modal](#8-generation-progress-modal)
9. [SEO Steps Panel](#9-seo-steps-panel)
10. [Internal Links Panel](#10-internal-links-panel)
11. [AB Testing Dashboard](#11-ab-testing-dashboard)

---

## 1. Overview Frontend

### Tech Stack
- **Blade Templates** (Laravel SSR)
- **Alpine.js** (Interactivity, state management)
- **Tailwind CSS** (Styling)
- **Amber Theme** (Tenant area color scheme)
- **Server-Sent Events** (Real-time progress)

### Views Structure

```
resources/views/tenant/article-steps/
├── articles/
│   ├── index.blade.php           Dashboard con filtri
│   ├── create.blade.php          Form creazione
│   ├── show.blade.php            ⭐ Dettaglio completo
│   └── edit.blade.php            Editor contenuto
├── keywords/
│   ├── index.blade.php           Lista keywords
│   └── create.blade.php          Bulk import
├── templates/
│   ├── index.blade.php           Libreria templates
│   └── create.blade.php          Form template
└── components/
    ├── generation-progress.blade.php    Modal progresso
    ├── seo-steps-panel.blade.php        Panel SEO steps
    ├── internal-links-panel.blade.php   Panel link interni
    └── ab-testing-panel.blade.php       AB test dashboard
```

---

## 2. Articles Dashboard

**File**: `resources/views/tenant/article-steps/articles/index.blade.php`

### Complete Code

```blade
@extends('layouts.tenant')

@section('title', 'AI Article Generator')

@section('content')
<div class="min-h-screen bg-gray-50 py-6" x-data="articlesManager()">

    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">AI Article Generator</h1>
                <p class="mt-2 text-sm text-gray-600">Create SEO-optimized articles with AI assistance</p>
            </div>

            <a href="{{ route('tenant.articles.create') }}"
               class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Generate New Article
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" action="{{ route('tenant.articles.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search articles..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="generating" {{ ($filters['status'] ?? '') === 'generating' ? 'selected' : '' }}>Generating</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <!-- Keyword Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keyword</label>
                    <input type="text"
                           name="keyword_id"
                           value="{{ $filters['keyword_id'] ?? '' }}"
                           placeholder="Filter by keyword..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500">
                </div>

                <!-- Actions -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Apply Filters
                    </button>
                    <a href="{{ route('tenant.articles.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Articles Table -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keyword</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SEO Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Words</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($articles as $article)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($article->isGenerating())
                                    <div class="animate-spin mr-2 h-4 w-4 border-2 border-amber-500 border-t-transparent rounded-full"></div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $article->title ?: 'Untitled' }}
                                    </div>
                                    @if($article->latestGeneration && $article->isGenerating())
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $article->latestGeneration->step_description }}
                                        ({{ $article->latestGeneration->progress_percentage }}%)
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $article->keyword?->keyword ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$article->status" />
                        </td>
                        <td class="px-6 py-4">
                            @if($article->seo_score)
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">{{ $article->seo_score }}/100</div>
                                <div class="ml-2 text-xs text-gray-500">({{ $article->seo_score_level }})</div>
                            </div>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $article->word_count ? number_format($article->word_count) : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $article->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('tenant.articles.show', $article) }}"
                                   class="text-amber-600 hover:text-amber-900">
                                    View
                                </a>

                                @if($article->isCompleted())
                                <a href="{{ route('tenant.articles.edit', $article) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    Edit
                                </a>
                                @endif

                                @if($article->isFailed())
                                <button @click="retryGeneration('{{ $article->id }}')"
                                        class="text-green-600 hover:text-green-900">
                                    Retry
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-lg font-medium">No articles yet</p>
                                <p class="text-sm mt-1">Start generating your first AI article</p>
                                <a href="{{ route('tenant.articles.create') }}"
                                   class="mt-4 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition">
                                    Generate Article
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($articles->hasPages())
            <div class="bg-gray-50 px-6 py-4">
                {{ $articles->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function articlesManager() {
    return {
        retryGeneration(articleId) {
            if (!confirm('Retry article generation?')) return;

            fetch(`/tenant/api/articles/${articleId}/generation/retry`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to retry: ' + data.message);
                }
            });
        }
    }
}
</script>
@endpush
@endsection
```

---

## 3. Article Creation Form

**File**: `resources/views/tenant/article-steps/articles/create.blade.php`

### Complete Code

```blade
@extends('layouts.tenant')

@section('title', 'Generate New Article')

@section('content')
<div class="min-h-screen bg-gray-50 py-6" x-data="articleCreator()">

    <!-- Header -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('tenant.articles.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Generate New Article</h1>
                <p class="mt-1 text-sm text-gray-600">Configure AI parameters and start generation</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('tenant.articles.store') }}" @submit.prevent="handleSubmit">
            @csrf

            <div class="space-y-6">

                <!-- Keyword Selection -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">1. Select Keyword</h2>

                    <div class="space-y-4">
                        <!-- Existing Keyword -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Choose from existing keywords
                            </label>
                            <select name="keyword_id"
                                    x-model="form.keyword_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="">-- Select a keyword --</option>
                                @foreach($keywords as $keyword)
                                <option value="{{ $keyword->id }}">
                                    {{ $keyword->keyword }}
                                    @if($keyword->search_volume)
                                    ({{ number_format($keyword->search_volume) }} vol)
                                    @endif
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- OR Divider -->
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">OR</span>
                            </div>
                        </div>

                        <!-- Custom Keyword -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Enter a custom keyword
                            </label>
                            <input type="text"
                                   name="custom_keyword"
                                   x-model="form.custom_keyword"
                                   placeholder="e.g., best AI tools for marketing"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>

                    @error('keyword_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Template Selection -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">2. Choose Prompt Template</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($templates as $template)
                        <label class="relative flex cursor-pointer rounded-lg border p-4 transition"
                               :class="form.prompt_template_id === '{{ $template->id }}' ? 'border-amber-500 bg-amber-50' : 'border-gray-300 bg-white hover:bg-gray-50'">

                            <input type="radio"
                                   name="prompt_template_id"
                                   value="{{ $template->id }}"
                                   x-model="form.prompt_template_id"
                                   class="sr-only">

                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">{{ $template->name }}</span>
                                    @if($template->is_default)
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Default</span>
                                    @endif
                                </div>

                                <p class="text-sm text-gray-500 mt-1">{{ $template->description }}</p>

                                <div class="flex gap-4 mt-3 text-xs text-gray-600">
                                    <span>{{ $template->tone }}</span>
                                    <span>•</span>
                                    <span>{{ $template->word_count_range }}</span>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Article Parameters -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">3. Customize Parameters</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Word Count Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Word Count (min)</label>
                            <input type="number"
                                   name="word_count_min"
                                   x-model="form.word_count_min"
                                   placeholder="800"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Word Count (max)</label>
                            <input type="number"
                                   name="word_count_max"
                                   x-model="form.word_count_max"
                                   placeholder="1200"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500">
                        </div>

                        <!-- Tone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tone</label>
                            <select name="tone"
                                    x-model="form.tone"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500">
                                <option value="professional">Professional</option>
                                <option value="casual">Casual</option>
                                <option value="friendly">Friendly</option>
                                <option value="authoritative">Authoritative</option>
                                <option value="conversational">Conversational</option>
                            </select>
                        </div>

                        <!-- Style -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Style</label>
                            <select name="style"
                                    x-model="form.style"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500">
                                <option value="blog">Blog Post</option>
                                <option value="tutorial">Tutorial</option>
                                <option value="guide">Guide</option>
                                <option value="listicle">Listicle</option>
                                <option value="news">News Article</option>
                                <option value="review">Review</option>
                            </select>
                        </div>

                        <!-- Language -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                            <select name="language"
                                    x-model="form.language"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500">
                                <option value="en">English</option>
                                <option value="it">Italiano</option>
                                <option value="es">Español</option>
                                <option value="fr">Français</option>
                            </select>
                        </div>
                    </div>

                    <!-- Extra Instructions -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Instructions (Optional)
                        </label>
                        <textarea name="extra_instructions"
                                  x-model="form.extra_instructions"
                                  rows="4"
                                  placeholder="Any specific requirements or instructions for the AI..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-amber-500"></textarea>
                    </div>

                    <!-- Options -->
                    <div class="mt-6 space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="include_internal_links"
                                   x-model="form.include_internal_links"
                                   value="1"
                                   class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Auto-suggest internal links
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <p class="font-medium">Estimated time: 2-5 minutes</p>
                            <p class="mt-1">You'll receive a notification when the article is ready</p>
                        </div>

                        <button type="submit"
                                :disabled="submitting"
                                class="px-6 py-3 bg-amber-600 hover:bg-amber-700 disabled:bg-gray-400 text-white rounded-lg transition font-medium">
                            <span x-show="!submitting">Generate Article</span>
                            <span x-show="submitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Starting Generation...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function articleCreator() {
    return {
        form: {
            keyword_id: '',
            custom_keyword: '',
            prompt_template_id: '{{ $templates->where("is_default", true)->first()?->id ?? "" }}',
            word_count_min: 800,
            word_count_max: 1200,
            tone: 'professional',
            style: 'blog',
            language: 'en',
            extra_instructions: '',
            include_internal_links: true,
        },
        submitting: false,

        handleSubmit(event) {
            if (!this.form.keyword_id && !this.form.custom_keyword) {
                alert('Please select or enter a keyword');
                return;
            }

            this.submitting = true;
            event.target.submit();
        }
    }
}
</script>
@endpush
@endsection
```

---

_Continuazione file troppo lungo, completo con ulteriori 8 sezioni (Article Detail, Editor, Keywords, Templates, Progress Modal, SEO Panel, Links Panel, AB Testing) per totale ~150 KB..._

---

## Implementation Checklist

### Views Created
- [x] Articles Dashboard with filters and real-time status
- [x] Article Creation Form with template selection
- [ ] Article Detail View (show.blade.php)
- [ ] Article Editor (edit.blade.php)
- [ ] Keywords Management (index.blade.php, create.blade.php)
- [ ] Templates Library
- [ ] Generation Progress Modal (Alpine.js + SSE)
- [ ] SEO Steps Panel
- [ ] Internal Links Panel
- [ ] AB Testing Dashboard

### Alpine.js Components
- [x] `articlesManager()` - Dashboard interactions
- [x] `articleCreator()` - Form handling
- [ ] `progressTracker()` - SSE progress tracking
- [ ] `seoStepsManager()` - SEO steps workflow
- [ ] `linksManager()` - Internal links approval
- [ ] `abTestingManager()` - AB test results

### Integration
- [ ] Server-Sent Events for progress
- [ ] Real-time notifications
- [ ] AJAX form submissions
- [ ] Dynamic content loading

---

**Documento parziale**: 2/10 views complete (~40 KB)
**Status**: ⏳ In Progress
**Next**: Completare restanti 8 views + Alpine.js components

---

_AI Article Steps — Ainstein Platform_
_Laravel Multi-Tenant SaaS_
_Generated: October 2025_
