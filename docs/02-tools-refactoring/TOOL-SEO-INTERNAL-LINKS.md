# AI Internal Links - Refactoring Dettagliato

## 📋 Panoramica

**Nome**: AI Internal Links Suggester
**Categoria**: SEO
**Funzione**: Analisi AI per suggerire link interni contestuali tra contenuti
**API**: OpenAI
**Token tracking**: ✅ Sì

---

## 🗄️ Database Schema

```php
Schema::create('seo_internal_link_suggestions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->foreignId('source_content_id')->constrained('contents')->onDelete('cascade');
    $table->foreignId('target_content_id')->constrained('contents')->onDelete('cascade');

    $table->string('anchor_text');
    $table->text('context_snippet')->nullable(); // Frase dove inserire il link
    $table->integer('position_in_text')->nullable();
    $table->decimal('relevance_score', 5, 2)->default(0); // 0-100

    $table->enum('status', ['suggested', 'approved', 'applied', 'rejected'])->default('suggested');

    // Token tracking
    $table->integer('tokens_used')->default(0);
    $table->string('model_used')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'source_content_id', 'status']);
});

Schema::create('seo_internal_links_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->integer('max_suggestions_per_content')->default(5);
    $table->decimal('min_relevance_threshold', 3, 2)->default(0.6);
    $table->boolean('auto_apply')->default(false);

    $table->timestamps();

    $table->unique('tenant_id');
});
```

---

## 📦 Models

```php
class SeoInternalLinkSuggestion extends Model
{
    protected $fillable = [
        'tenant_id', 'source_content_id', 'target_content_id',
        'anchor_text', 'context_snippet', 'position_in_text',
        'relevance_score', 'status', 'tokens_used', 'model_used'
    ];

    public function sourceContent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'source_content_id');
    }

    public function targetContent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'target_content_id');
    }

    public function apply(): bool
    {
        // Inserisci link nel contenuto source
        $content = $this->sourceContent;
        $htmlContent = $content->html_content;

        $linkHtml = "<a href=\"{$this->targetContent->url_path}\">{$this->anchor_text}</a>";

        // Replace anchor text con link nel context
        $htmlContent = str_replace(
            $this->anchor_text,
            $linkHtml,
            $htmlContent
        );

        $content->update(['html_content' => $htmlContent]);

        $this->update(['status' => 'applied']);

        return true;
    }
}
```

---

## 🔧 Service

```php
// app/Services/Tools/InternalLinks/InternalLinksAnalyzer.php

class InternalLinksAnalyzer
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    public function analyzeSingle(Content $sourceContent, int $tenantId): array
    {
        // Ottieni tutti i contenuti target (escluso source)
        $targetContents = Content::where('tenant_id', $tenantId)
            ->where('id', '!=', $sourceContent->id)
            ->where('status', 'active')
            ->limit(50) // Limita per non sovraccaricare
            ->get();

        $sourceText = strip_tags($sourceContent->html_content);

        $targetSummaries = $targetContents->map(fn($c) => [
            'id' => $c->id,
            'url' => $c->url_path,
            'keyword' => $c->keyword,
            'summary' => Str::limit(strip_tags($c->html_content), 200),
        ])->toJson();

        $prompt = "Analizza il seguente contenuto e suggerisci opportunità di internal linking contestuale.

**Contenuto Source:**
{$sourceText}

**Contenuti Target Disponibili:**
{$targetSummaries}

Rispondi in JSON con array di suggerimenti:
[
  {
    \"target_id\": 123,
    \"anchor_text\": \"testo ancora suggerito\",
    \"context_snippet\": \"...frase completa dove inserire il link...\",
    \"relevance_score\": 85
  }
]

Criteri:
- Massimo 5 suggerimenti
- Solo collegamenti altamente rilevanti (score >= 60)
- Anchor text naturale e contestuale
- Evita over-optimization";

        $response = $this->openai->chat([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Sei un esperto SEO specializzato in internal linking e link building.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        $suggestions = json_decode($response['choices'][0]['message']['content'], true)['suggestions'] ?? [];

        // Salva suggerimenti
        foreach ($suggestions as $sugg) {
            SeoInternalLinkSuggestion::create([
                'tenant_id' => $tenantId,
                'source_content_id' => $sourceContent->id,
                'target_content_id' => $sugg['target_id'],
                'anchor_text' => $sugg['anchor_text'],
                'context_snippet' => $sugg['context_snippet'],
                'relevance_score' => $sugg['relevance_score'],
                'tokens_used' => $response['usage']['total_tokens'],
                'model_used' => $response['model'],
            ]);
        }

        // Track tokens
        $this->tokenTracker->track(
            $tenantId,
            $response['usage']['total_tokens'],
            $response['model'],
            'internal_links_analysis'
        );

        return $suggestions;
    }

    public function analyzeBatch(array $contentIds, int $tenantId): array
    {
        $results = [];

        foreach ($contentIds as $contentId) {
            $content = Content::find($contentId);

            if ($content && $content->tenant_id === $tenantId) {
                $results[] = $this->analyzeSingle($content, $tenantId);
            }
        }

        return $results;
    }
}
```

---

## 🎮 Controller

```php
class InternalLinksController extends Controller
{
    public function __construct(
        protected InternalLinksAnalyzer $analyzer
    ) {}

    public function index()
    {
        $suggestions = SeoInternalLinkSuggestion::forTenant(Auth::user()->tenant_id)
            ->with('sourceContent', 'targetContent')
            ->latest()
            ->paginate(50);

        $stats = [
            'total' => $suggestions->total(),
            'pending' => SeoInternalLinkSuggestion::forTenant(Auth::user()->tenant_id)
                ->where('status', 'suggested')
                ->count(),
            'applied' => SeoInternalLinkSuggestion::forTenant(Auth::user()->tenant_id)
                ->where('status', 'applied')
                ->count(),
        ];

        return view('tenant.tools.seo.internal-links.index', compact('suggestions', 'stats'));
    }

    public function analyze(Request $request, Content $content)
    {
        $this->authorize('view', $content);

        $suggestions = $this->analyzer->analyzeSingle($content, Auth::user()->tenant_id);

        return response()->json([
            'success' => true,
            'suggestions_count' => count($suggestions),
            'suggestions' => $suggestions,
        ]);
    }

    public function apply(SeoInternalLinkSuggestion $suggestion)
    {
        $this->authorize('update', $suggestion->sourceContent);

        $suggestion->apply();

        return back()->with('success', 'Link inserito con successo!');
    }

    public function reject(SeoInternalLinkSuggestion $suggestion)
    {
        $suggestion->update(['status' => 'rejected']);

        return back()->with('success', 'Suggerimento rifiutato');
    }

    public function bulkApply(Request $request)
    {
        $validated = $request->validate([
            'suggestion_ids' => 'required|array',
            'suggestion_ids.*' => 'exists:seo_internal_link_suggestions,id',
        ]);

        $applied = 0;

        foreach ($validated['suggestion_ids'] as $id) {
            $suggestion = SeoInternalLinkSuggestion::find($id);

            if ($suggestion && $suggestion->tenant_id === Auth::user()->tenant_id) {
                $suggestion->apply();
                $applied++;
            }
        }

        return back()->with('success', "{$applied} link applicati!");
    }
}
```

---

## 🎨 UI View

```blade
<div class="max-w-7xl mx-auto" x-data="internalLinks()">
    <h1 class="text-3xl font-bold mb-6">🔗 AI Internal Links</h1>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <p class="text-sm text-gray-600">Totale Suggerimenti</p>
            <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <p class="text-sm text-gray-600">In Attesa</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <p class="text-sm text-gray-600">Applicati</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['applied'] }}</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th><input type="checkbox" @change="toggleAll"></th>
                    <th>Contenuto Source</th>
                    <th>→ Target</th>
                    <th>Anchor Text</th>
                    <th>Contesto</th>
                    <th>Rilevanza</th>
                    <th>Status</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach($suggestions as $s)
                <tr>
                    <td><input type="checkbox" :value="{{ $s->id }}" x-model="selectedIds"></td>
                    <td>{{ Str::limit($s->sourceContent->keyword, 40) }}</td>
                    <td>{{ Str::limit($s->targetContent->keyword, 40) }}</td>
                    <td><code class="text-sm">{{ $s->anchor_text }}</code></td>
                    <td class="text-sm text-gray-600">{{ Str::limit($s->context_snippet, 60) }}</td>
                    <td>
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $s->relevance_score }}%"></div>
                            </div>
                            <span class="ml-2 text-xs">{{ $s->relevance_score }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($s->status === 'applied')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Applicato</span>
                        @elseif($s->status === 'rejected')
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Rifiutato</span>
                        @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">In Attesa</span>
                        @endif
                    </td>
                    <td>
                        @if($s->status === 'suggested')
                        <form action="{{ route('tenant.tools.seo.internal-links.apply', $s) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900">✅ Applica</button>
                        </form>
                        <form action="{{ route('tenant.tools.seo.internal-links.reject', $s) }}" method="POST" class="inline ml-2">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-900">❌ Rifiuta</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $suggestions->links() }}
        </div>
    </div>

    {{-- Bulk Actions --}}
    <div class="mt-4" x-show="selectedIds.length > 0">
        <button @click="bulkApply()" class="btn btn-primary">
            ✅ Applica Selezionati (<span x-text="selectedIds.length"></span>)
        </button>
    </div>
</div>

@push('scripts')
<script>
function internalLinks() {
    return {
        selectedIds: [],

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = @json($suggestions->pluck('id'));
            } else {
                this.selectedIds = [];
            }
        },

        async bulkApply() {
            const response = await fetch('{{ route("tenant.tools.seo.internal-links.bulk-apply") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ suggestion_ids: this.selectedIds })
            });

            const result = await response.json();
            alert(result.message || 'Link applicati!');
            location.reload();
        }
    };
}
</script>
@endpush
```

---

## ✅ Checklist

- [ ] Migration tables
- [ ] Models + relationships
- [ ] InternalLinksAnalyzer service
- [ ] Controller con analyze/apply/reject
- [ ] UI con DataTables
- [ ] Bulk apply/reject
- [ ] Token tracking
- [ ] Settings per tenant

**Stima Token**: 500-1500 token per analisi (dipende da numero contenuti)
