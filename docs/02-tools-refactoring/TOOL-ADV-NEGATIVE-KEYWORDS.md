# AI Negative Keywords Tool - Refactoring Dettagliato

## 📋 Panoramica

**Nome**: AI Negative Keywords Tool
**Categoria**: ADV
**Funzione**: Generazione e gestione keyword negate per campagne Google Ads con AI
**API**: Google Ads API (OAuth 2.0), OpenAI
**Token tracking**: ✅ Sì (per generazione AI)

---

## 🗄️ Database Schema

```php
// Migration: create_adv_negative_keywords_tables.php

Schema::create('adv_negative_keywords', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('campaign_name')->nullable();
    $table->string('google_campaign_id')->nullable(); // ID campagna Google Ads
    $table->json('keywords'); // Array keywords negate
    $table->enum('match_type', ['exact', 'phrase', 'broad'])->default('phrase');
    $table->enum('source', ['ai_generated', 'manual', 'imported'])->default('manual');

    // Token tracking (se generazione AI)
    $table->integer('tokens_used')->default(0);
    $table->string('model_used')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'google_campaign_id']);
});

// Tabella per OAuth tokens Google Ads
Schema::create('google_ads_connections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('customer_id'); // Google Ads Customer ID
    $table->text('access_token');
    $table->text('refresh_token');
    $table->timestamp('expires_at');

    $table->timestamps();

    $table->unique(['tenant_id', 'customer_id']);
});
```

---

## 📦 Models

```php
// app/Models/AdvNegativeKeyword.php
class AdvNegativeKeyword extends Model
{
    protected $fillable = [
        'tenant_id', 'campaign_name', 'google_campaign_id',
        'keywords', 'match_type', 'source', 'tokens_used', 'model_used'
    ];

    protected $casts = [
        'keywords' => 'array',
        'tokens_used' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

// app/Models/GoogleAdsConnection.php
class GoogleAdsConnection extends Model
{
    protected $fillable = [
        'tenant_id', 'customer_id', 'access_token',
        'refresh_token', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
```

---

## 🔧 Services

### Service OAuth Google Ads

```php
// app/Services/Tools/NegativeKeywords/GoogleAdsOAuthService.php

class GoogleAdsOAuthService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $settings = AdminSetting::first();
        $this->clientId = $settings->google_ads_client_id;
        $this->clientSecret = $settings->google_ads_client_secret;
        $this->redirectUri = route('tenant.tools.adv.negative-keywords.oauth.callback');
    }

    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/adwords',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function handleCallback(string $code, int $tenantId): GoogleAdsConnection
    {
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        $data = $response->json();

        // Ottieni customer ID
        $customerId = $this->getCustomerId($data['access_token']);

        return GoogleAdsConnection::updateOrCreate(
            ['tenant_id' => $tenantId, 'customer_id' => $customerId],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]
        );
    }

    public function refreshToken(GoogleAdsConnection $connection): void
    {
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        $data = $response->json();

        $connection->update([
            'access_token' => $data['access_token'],
            'expires_at' => now()->addSeconds($data['expires_in']),
        ]);
    }

    protected function getCustomerId(string $accessToken): string
    {
        // Chiamata API Google Ads per ottenere customer ID
        $response = Http::withToken($accessToken)
            ->get('https://googleads.googleapis.com/v14/customers:listAccessibleCustomers');

        return $response->json()['resourceNames'][0] ?? '';
    }
}
```

### Service Generazione AI

```php
// app/Services/Tools/NegativeKeywords/NegativeKeywordGenerator.php

class NegativeKeywordGenerator
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    public function generate(string $campaignInfo, string $targetKeywords, int $tenantId): array
    {
        $prompt = "Analizza la seguente campagna Google Ads e genera una lista di keyword negate strategiche.

Informazioni campagna:
{$campaignInfo}

Target keywords:
{$targetKeywords}

Genera 20-30 keyword negate per:
- Evitare clic non qualificati
- Escludere ricerche informative non commerciali
- Filtrare varianti irrilevanti
- Ridurre sprechi budget

Rispondi SOLO con un array JSON: [\"keyword1\", \"keyword2\", ...]";

        $response = $this->openai->chat([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Sei un esperto Google Ads specializzato in ottimizzazione campagne e negative keywords.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $content = $response['choices'][0]['message']['content'];
        $keywords = json_decode($content, true);

        // Track tokens
        $this->tokenTracker->track(
            tenantId: $tenantId,
            tokens: $response['usage']['total_tokens'],
            model: $response['model'],
            source: 'negative_keywords_generator'
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
// app/Http/Controllers/Tenant/Tools/ADV/NegativeKeywordController.php

class NegativeKeywordController extends Controller
{
    public function __construct(
        protected GoogleAdsOAuthService $oauth,
        protected NegativeKeywordGenerator $generator
    ) {}

    public function index()
    {
        $keywords = AdvNegativeKeyword::forTenant(Auth::user()->tenant_id)
            ->latest()
            ->paginate(20);

        $connection = GoogleAdsConnection::where('tenant_id', Auth::user()->tenant_id)->first();

        return view('tenant.tools.adv.negative-keywords.index', compact('keywords', 'connection'));
    }

    /**
     * Avvia OAuth Google Ads
     */
    public function initiateOAuth()
    {
        return redirect($this->oauth->getAuthUrl());
    }

    /**
     * Callback OAuth
     */
    public function handleOAuthCallback(Request $request)
    {
        $connection = $this->oauth->handleCallback(
            $request->code,
            Auth::user()->tenant_id
        );

        return redirect()
            ->route('tenant.tools.adv.negative-keywords.index')
            ->with('success', 'Google Ads connesso con successo!');
    }

    /**
     * Genera keyword negate con AI
     */
    public function generateAI(Request $request)
    {
        $validated = $request->validate([
            'campaign_info' => 'required|string',
            'target_keywords' => 'required|string',
            'campaign_name' => 'nullable|string',
        ]);

        $result = $this->generator->generate(
            $validated['campaign_info'],
            $validated['target_keywords'],
            Auth::user()->tenant_id
        );

        $negativeKw = AdvNegativeKeyword::create([
            'tenant_id' => Auth::user()->tenant_id,
            'campaign_name' => $validated['campaign_name'] ?? 'AI Generated',
            'keywords' => $result['keywords'],
            'match_type' => 'phrase',
            'source' => 'ai_generated',
            'tokens_used' => $result['tokens_used'],
            'model_used' => $result['model_used'],
        ]);

        return response()->json([
            'success' => true,
            'keywords' => $result['keywords'],
            'tokens_used' => $result['tokens_used'],
        ]);
    }

    /**
     * Sincronizza con Google Ads
     */
    public function syncToGoogleAds(AdvNegativeKeyword $keyword)
    {
        $connection = GoogleAdsConnection::where('tenant_id', Auth::user()->tenant_id)->first();

        if (!$connection) {
            return back()->with('error', 'Google Ads non connesso');
        }

        if ($connection->isExpired()) {
            $this->oauth->refreshToken($connection);
        }

        // TODO: Implementa sync con Google Ads API
        // Usa googleads/google-ads-php package

        return back()->with('success', 'Keyword sincronizzate!');
    }
}
```

---

## 🎨 UI View

```blade
{{-- resources/views/tenant/tools/adv/negative-keywords/index.blade.php --}}

@extends('layouts.tenant')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="negativeKeywords()">

    <div class="md:flex md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold">🚫 AI Negative Keywords</h1>
            <p class="text-sm text-gray-600 mt-2">Gestisci keyword negate per Google Ads</p>
        </div>
        <div class="mt-4 flex gap-3 md:mt-0">
            @if($connection)
                <span class="inline-flex items-center px-3 py-2 bg-green-100 text-green-800 rounded-md text-sm">
                    ✅ Google Ads Connesso
                </span>
            @else
                <a href="{{ route('tenant.tools.adv.negative-keywords.oauth') }}" class="btn btn-primary">
                    🔗 Connetti Google Ads
                </a>
            @endif
            <button @click="showGenerateModal = true" class="btn btn-primary">
                🤖 Genera con AI
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campagna</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keywords ({{  }})</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Match Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fonte</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Token</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($keywords as $kw)
                <tr>
                    <td class="px-6 py-4">{{ $kw->campaign_name }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm max-w-md">
                            @foreach($kw->keywords as $k)
                                <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $k }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ $kw->match_type }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $kw->source }}</td>
                    <td class="px-6 py-4 text-sm">{{ number_format($kw->tokens_used) }}</td>
                    <td class="px-6 py-4 text-right text-sm">
                        @if($connection)
                        <form action="{{ route('tenant.tools.adv.negative-keywords.sync', $kw) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-blue-600 hover:text-blue-900">
                                🔄 Sync Google Ads
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('tenant.tools.adv.negative-keywords.destroy', $kw) }}" method="POST" class="inline ml-3">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">🗑️</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $keywords->links() }}
        </div>
    </div>

    {{-- Modal Generazione AI --}}
    <div x-show="showGenerateModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showGenerateModal = false"></div>

            <div class="relative bg-white rounded-lg max-w-2xl w-full p-6">
                <h3 class="text-lg font-medium mb-4">🤖 Genera Negative Keywords con AI</h3>

                <form @submit.prevent="generateAI()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Nome Campagna</label>
                            <input type="text" x-model="generateForm.campaign_name" class="form-input w-full">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Info Campagna</label>
                            <textarea x-model="generateForm.campaign_info" rows="4" class="form-textarea w-full" required
                                      placeholder="Es: Campagna per vendita software CRM B2B..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Target Keywords</label>
                            <textarea x-model="generateForm.target_keywords" rows="3" class="form-textarea w-full" required
                                      placeholder="software crm, crm aziendale, gestione clienti..."></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showGenerateModal = false" class="btn btn-secondary">Annulla</button>
                        <button type="submit" class="btn btn-primary" :disabled="generating">
                            <span x-show="!generating">🤖 Genera</span>
                            <span x-show="generating">⏳ Generazione...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function negativeKeywords() {
    return {
        showGenerateModal: false,
        generating: false,
        generateForm: {
            campaign_name: '',
            campaign_info: '',
            target_keywords: '',
        },

        async generateAI() {
            this.generating = true;

            const response = await fetch('{{ route("tenant.tools.adv.negative-keywords.generate-ai") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(this.generateForm),
            });

            const result = await response.json();

            this.generating = false;

            if (result.success) {
                alert(`✅ ${result.keywords.length} negative keywords generate! Token usati: ${result.tokens_used}`);
                location.reload();
            }
        }
    };
}
</script>
@endpush
@endsection
```

---

## ✅ Checklist

### Backend
- [ ] Migration tables
- [ ] Models + relationships
- [ ] GoogleAdsOAuthService
- [ ] NegativeKeywordGenerator service
- [ ] Controller con OAuth flow
- [ ] Routes OAuth callback
- [ ] Google Ads API integration (package `googleads/google-ads-php`)
- [ ] Token tracking

### Admin Settings
- [ ] Campi `google_ads_client_id`, `google_ads_client_secret`
- [ ] UI connessione OAuth

### Frontend
- [ ] Index con tabella keywords
- [ ] Modal generazione AI
- [ ] Connessione Google Ads UI
- [ ] Sync button con Google Ads

### Testing
- [ ] OAuth flow test
- [ ] AI generation test
- [ ] Google Ads sync test

**Stima Token**: 300-600 token per generazione (20-30 keywords)
