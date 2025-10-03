# RapidAPI Keyword Research - Refactoring Dettagliato

## 📋 Panoramica

**Nome**: Multi-API Keyword Research
**Categoria**: SEO
**Funzione**: Ricerca keyword da SEMrush, SEO Keyword Research, AI Google Keyword + AI Keyword Generator
**API**: RapidAPI (3 endpoints), OpenAI (per AI generation)
**Token tracking**: ✅ Solo per AI Keyword Generator

---

## 🗄️ Database Schema

```php
Schema::create('seo_keyword_research', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('keyword');
    $table->string('source'); // semrush, seo_keyword, ai_google, ai_generated
    $table->decimal('cpc', 8, 2)->nullable();
    $table->bigInteger('volume')->nullable();
    $table->decimal('competition', 3, 2)->nullable();

    // Campi specifici per fonte
    $table->decimal('cpc_low', 8, 2)->nullable(); // AI Google
    $table->decimal('cpc_high', 8, 2)->nullable(); // AI Google
    $table->string('difficulty')->nullable(); // SEMrush

    $table->json('additional_data')->nullable();

    // Token tracking (solo ai_generated)
    $table->integer('tokens_used')->default(0);
    $table->string('model_used')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'keyword', 'source']);
});

Schema::create('seo_keyword_campaigns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('name');
    $table->text('briefing')->nullable(); // Per AI generation
    $table->string('target_url')->nullable();

    $table->timestamps();
});

Schema::create('seo_keyword_campaign_results', function (Blueprint $table) {
    $table->id();
    $table->foreignId('campaign_id')->constrained('seo_keyword_campaigns')->onDelete('cascade');
    $table->foreignId('keyword_research_id')->constrained('seo_keyword_research')->onDelete('cascade');

    $table->timestamps();
});
```

---

## 📦 Models

```php
class SeoKeywordResearch extends Model
{
    protected $fillable = [
        'tenant_id', 'keyword', 'source', 'cpc', 'volume',
        'competition', 'cpc_low', 'cpc_high', 'difficulty',
        'additional_data', 'tokens_used', 'model_used'
    ];

    protected $casts = [
        'cpc' => 'decimal:2',
        'volume' => 'integer',
        'competition' => 'decimal:2',
        'additional_data' => 'array',
    ];

    public function campaigns()
    {
        return $this->belongsToMany(
            SeoKeywordCampaign::class,
            'seo_keyword_campaign_results',
            'keyword_research_id',
            'campaign_id'
        );
    }
}

class SeoKeywordCampaign extends Model
{
    protected $fillable = ['tenant_id', 'name', 'briefing', 'target_url'];

    public function keywords()
    {
        return $this->belongsToMany(
            SeoKeywordResearch::class,
            'seo_keyword_campaign_results',
            'campaign_id',
            'keyword_research_id'
        );
    }
}
```

---

## 🔧 Services

```php
// app/Services/Tools/KeywordResearch/RapidAPIService.php

class RapidAPIService
{
    protected string $apiKey;

    public function __construct()
    {
        $settings = AdminSetting::first();
        $this->apiKey = $settings->rapidapi_key;
    }

    public function searchSemrush(string $keyword, string $country = 'us'): array
    {
        $response = Http::withHeaders([
            'x-rapidapi-key' => $this->apiKey,
            'x-rapidapi-host' => 'semrush-keyword-magic-tool.p.rapidapi.com',
        ])->get('https://semrush-keyword-magic-tool.p.rapidapi.com/keyword-research', [
            'keyword' => $keyword,
            'country' => $country,
        ]);

        $data = $response->json();

        if (empty($data)) {
            return [];
        }

        $keywordOverview = $data[1]['Keyword Overview'][0] ?? null;

        if (!$keywordOverview) {
            return [];
        }

        return [
            'keyword' => $keywordOverview['keyword'],
            'cpc' => $keywordOverview['High CPC'] ?? 0,
            'volume' => $keywordOverview['avg_monthly_searches'] ?? 0,
            'competition' => $keywordOverview['competition_value'] ?? 0,
            'difficulty' => $keywordOverview['keyword_difficulty'] ?? null,
        ];
    }

    public function searchSEOKeyword(string $keyword, string $country = 'it'): array
    {
        $response = Http::withHeaders([
            'x-rapidapi-key' => $this->apiKey,
            'x-rapidapi-host' => 'seo-keyword-research.p.rapidapi.com',
        ])->get('https://seo-keyword-research.p.rapidapi.com/keynew.php', [
            'keyword' => $keyword,
            'country' => $country,
        ]);

        $data = $response->json();

        return collect($data)->map(fn($item) => [
            'keyword' => $item['text'],
            'cpc' => $item['cpc'],
            'volume' => $item['vol'],
            'competition' => $item['competition'],
        ])->toArray();
    }

    public function searchAIGoogleKeyword(string $keyword, string $country = 'it'): array
    {
        $response = Http::timeout(20)->withHeaders([
            'x-rapidapi-key' => $this->apiKey,
            'x-rapidapi-host' => 'ai-google-keyword-research.p.rapidapi.com',
        ])->get('https://ai-google-keyword-research.p.rapidapi.com/keyword-research', [
            'keyword' => $keyword,
            'country' => $country,
        ]);

        $data = $response->json();

        return collect($data)->map(fn($item) => [
            'keyword' => $item['keyword'] ?? $keyword,
            'cpc_high' => $item['High CPC'] ?? 0,
            'cpc_low' => $item['Low CPC'] ?? 0,
            'volume' => $item['avg_monthly_searches'] ?? 0,
            'competition' => $item['competition_value'] ?? 0,
        ])->toArray();
    }
}

// app/Services/Tools/KeywordResearch/AIKeywordGenerator.php

class AIKeywordGenerator
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    public function generate(string $briefing, string $url, int $tenantId): array
    {
        $prompt = "Crea una lista di keyword rilevanti per portare traffico qualificato alla seguente pagina web:

Briefing e utilizzo: {$briefing}
URL: {$url}

Genera una lista di 10-15 keyword specifiche per SEO e Google Ads della pagina.

Output: restituisci SOLO le keyword separate da virgola, senza elenchi puntati o numerati.";

        $response = $this->openai->chat([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Sei un esperto di SEO e keyword research. Rispondi solo con le keyword separate da virgola.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $keywords = explode(',', $response['choices'][0]['message']['content']);
        $keywords = array_map('trim', $keywords);
        $keywords = array_filter($keywords);

        // Track tokens
        $this->tokenTracker->track(
            $tenantId,
            $response['usage']['total_tokens'],
            $response['model'],
            'ai_keyword_generation'
        );

        return [
            'keywords' => $keywords,
            'tokens_used' => $response['usage']['total_tokens'],
            'model_used' => $response['model'],
        ];
    }
}
```

---

## 🎮 Controller

```php
class KeywordResearchController extends Controller
{
    public function __construct(
        protected RapidAPIService $rapidApi,
        protected AIKeywordGenerator $aiGenerator
    ) {}

    public function index()
    {
        $research = SeoKeywordResearch::forTenant(Auth::user()->tenant_id)
            ->latest()
            ->paginate(50);

        return view('tenant.tools.seo.keyword-research.index', compact('research'));
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string',
            'source' => 'required|in:semrush,seo_keyword,ai_google',
            'country' => 'nullable|string|size:2',
        ]);

        try {
            $results = match ($validated['source']) {
                'semrush' => [$this->rapidApi->searchSemrush($validated['keyword'], $validated['country'] ?? 'us')],
                'seo_keyword' => $this->rapidApi->searchSEOKeyword($validated['keyword'], $validated['country'] ?? 'it'),
                'ai_google' => $this->rapidApi->searchAIGoogleKeyword($validated['keyword'], $validated['country'] ?? 'it'),
            };

            // Salva risultati
            foreach ($results as $result) {
                if (empty($result['keyword'])) continue;

                SeoKeywordResearch::create([
                    'tenant_id' => Auth::user()->tenant_id,
                    'keyword' => $result['keyword'],
                    'source' => $validated['source'],
                    'cpc' => $result['cpc'] ?? null,
                    'volume' => $result['volume'] ?? null,
                    'competition' => $result['competition'] ?? null,
                    'cpc_low' => $result['cpc_low'] ?? null,
                    'cpc_high' => $result['cpc_high'] ?? null,
                    'difficulty' => $result['difficulty'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'results_count' => count($results),
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function aiGenerate(Request $request)
    {
        $validated = $request->validate([
            'briefing' => 'required|string',
            'url' => 'required|url',
            'campaign_name' => 'nullable|string',
        ]);

        $result = $this->aiGenerator->generate(
            $validated['briefing'],
            $validated['url'],
            Auth::user()->tenant_id
        );

        // Crea campagna
        $campaign = SeoKeywordCampaign::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $validated['campaign_name'] ?? 'AI Generated ' . now()->format('Y-m-d'),
            'briefing' => $validated['briefing'],
            'target_url' => $validated['url'],
        ]);

        // Ora esegui ricerca multi-API per ogni keyword generata
        $allResults = [];

        foreach ($result['keywords'] as $keyword) {
            try {
                $aiGoogleResults = $this->rapidApi->searchAIGoogleKeyword($keyword);

                foreach ($aiGoogleResults as $kwData) {
                    $saved = SeoKeywordResearch::create([
                        'tenant_id' => Auth::user()->tenant_id,
                        'keyword' => $kwData['keyword'],
                        'source' => 'ai_google',
                        'cpc_low' => $kwData['cpc_low'],
                        'cpc_high' => $kwData['cpc_high'],
                        'volume' => $kwData['volume'],
                        'competition' => $kwData['competition'],
                        'tokens_used' => $result['tokens_used'], // Solo per keywords AI generated
                        'model_used' => $result['model_used'],
                    ]);

                    // Associa a campagna
                    $campaign->keywords()->attach($saved->id);

                    $allResults[] = $saved;
                }
            } catch (\Exception $e) {
                Log::error('Keyword research failed for: ' . $keyword, ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'campaign_id' => $campaign->id,
            'keywords_generated' => count($result['keywords']),
            'results_count' => count($allResults),
            'tokens_used' => $result['tokens_used'],
        ]);
    }
}
```

---

## 🎨 UI (Sintesi)

```blade
<div class="max-w-7xl mx-auto" x-data="keywordResearch()">
    <h1>🔍 Keyword Research Multi-API</h1>

    {{-- Search Form --}}
    <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Ricerca Keyword</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label>Keyword</label>
                <input type="text" x-model="searchForm.keyword" class="form-input w-full">
            </div>

            <div>
                <label>Fonte</label>
                <select x-model="searchForm.source" class="form-select w-full">
                    <option value="semrush">SEMrush</option>
                    <option value="seo_keyword">SEO Keyword Research</option>
                    <option value="ai_google">AI Google Keyword</option>
                </select>
            </div>

            <div>
                <label>Paese</label>
                <select x-model="searchForm.country" class="form-select w-full">
                    <option value="it">Italia</option>
                    <option value="us">USA</option>
                    <option value="uk">UK</option>
                </select>
            </div>
        </div>

        <button @click="search()" class="btn btn-primary mt-4">
            🔍 Cerca
        </button>
    </div>

    {{-- AI Generator --}}
    <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">🤖 AI Keyword Generator</h3>

        <div class="space-y-4">
            <div>
                <label>Briefing Progetto</label>
                <textarea x-model="aiForm.briefing" rows="3" class="form-textarea w-full"
                          placeholder="Es: Software CRM per PMI italiane..."></textarea>
            </div>

            <div>
                <label>URL Pagina Target</label>
                <input type="url" x-model="aiForm.url" class="form-input w-full">
            </div>

            <button @click="generateAI()" class="btn btn-primary">
                🤖 Genera e Analizza Keywords
            </button>
        </div>
    </div>

    {{-- Results Table (DataTables) --}}
    <div class="bg-white shadow rounded overflow-hidden">
        <table id="keywordTable" class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th>Keyword</th>
                    <th>Fonte</th>
                    <th>CPC</th>
                    <th>Volume</th>
                    <th>Competition</th>
                    <th>Difficulty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($research as $r)
                <tr>
                    <td>{{ $r->keyword }}</td>
                    <td>
                        <span class="px-2 py-1 rounded text-xs
                            @if($r->source === 'semrush') bg-blue-100 text-blue-800
                            @elseif($r->source === 'ai_google') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $r->source }}
                        </span>
                    </td>
                    <td>
                        @if($r->cpc_low && $r->cpc_high)
                            €{{ number_format($r->cpc_low, 2) }} - €{{ number_format($r->cpc_high, 2) }}
                        @else
                            €{{ number_format($r->cpc, 2) }}
                        @endif
                    </td>
                    <td>{{ number_format($r->volume) }}</td>
                    <td>{{ number_format($r->competition * 100, 0) }}%</td>
                    <td>{{ $r->difficulty }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
function keywordResearch() {
    return {
        searchForm: { keyword: '', source: 'semrush', country: 'it' },
        aiForm: { briefing: '', url: '' },

        async search() {
            const response = await fetch('{{ route("tenant.tools.seo.keyword-research.search") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.searchForm)
            });

            const result = await response.json();

            if (result.success) {
                alert(`✅ ${result.results_count} risultati trovati!`);
                location.reload();
            } else {
                alert('Errore: ' + result.error);
            }
        },

        async generateAI() {
            const response = await fetch('{{ route("tenant.tools.seo.keyword-research.ai-generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.aiForm)
            });

            const result = await response.json();

            if (result.success) {
                alert(`✅ ${result.keywords_generated} keywords generate e analizzate!\nToken usati: ${result.tokens_used}`);
                location.reload();
            }
        }
    };
}

$(document).ready(function() {
    $('#keywordTable').DataTable({
        order: [[4, 'desc']], // Ordina per competition
        pageLength: 50,
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json' }
    });
});
</script>
@endpush
```

---

## ✅ Checklist

- [ ] Migration tables
- [ ] Models + relationships
- [ ] RapidAPIService (3 endpoints)
- [ ] AIKeywordGenerator service
- [ ] Controller search + AI generate
- [ ] DataTables UI
- [ ] Token tracking (solo AI)
- [ ] Admin settings RapidAPI key

**Stima Token**: 300-800 token per AI keyword generation (dipende da numero keyword generate)
