# AI Campaign Generator - Refactoring Dettagliato

## 📋 Panoramica Tool

**Nome**: AI Campaign Generator
**Categoria**: ADV (Advertising)
**Funzione**: Generazione automatica asset per campagne Google Ads (RSA e Performance Max)
**API utilizzate**: OpenAI (GPT-3.5/4-turbo/4o-mini/4o)
**Token tracking**: ✅ Obbligatorio

---

## 🎯 Funzionalità da Implementare

### Core Features
1. **CRUD Campagne**
   - Creazione campagna con: nome, briefing, keywords, tipo (RSA/PMax), lingua, URL
   - Modifica inline campagne esistenti
   - Eliminazione batch campagne

2. **Generazione Asset AI**
   - **RSA**: 15 titoli (max 30 char) + 4 descrizioni (max 90 char)
   - **PMax**: 15 titoli brevi (30 char) + 5 titoli lunghi (90 char) + 5 descrizioni (90 char)
   - Generazione batch multipla

3. **Import/Export**
   - Import CSV bulk campagne (formato: `language,name,info,keywords,type,url`)
   - Export CSV/JSON asset generati

4. **UI Avanzata**
   - DataTables con sorting/filtering
   - Inline editing campi campagna
   - Modal preview asset generati
   - Character counter real-time

---

## 🗄️ Database Schema

### Migration: `create_adv_campaigns_tables.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabella campagne
        Schema::create('adv_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->text('info')->nullable(); // Briefing prodotto/servizio
            $table->text('keywords')->nullable();
            $table->enum('type', ['rsa', 'pmax'])->default('rsa');
            $table->string('language', 10)->default('it');
            $table->string('url')->nullable(); // Landing page URL

            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index('created_at');
        });

        // Tabella asset generati
        Schema::create('adv_generated_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('adv_campaigns')->onDelete('cascade');

            $table->enum('type', ['rsa', 'pmax']);
            $table->json('titles')->nullable(); // Array titoli brevi
            $table->json('long_titles')->nullable(); // Array titoli lunghi (solo PMax)
            $table->json('descriptions')->nullable(); // Array descrizioni

            // Token tracking
            $table->integer('tokens_used')->default(0);
            $table->string('model_used')->nullable();

            $table->timestamps();

            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adv_generated_assets');
        Schema::dropIfExists('adv_campaigns');
    }
};
```

---

## 📦 Models

### `app/Models/AdvCampaign.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdvCampaign extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'info',
        'keywords',
        'type',
        'language',
        'url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assets(): HasOne
    {
        return $this->hasOne(AdvGeneratedAsset::class, 'campaign_id');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function hasAssets(): bool
    {
        return $this->assets()->exists();
    }

    public function isRSA(): bool
    {
        return $this->type === 'rsa';
    }

    public function isPMax(): bool
    {
        return $this->type === 'pmax';
    }
}
```

### `app/Models/AdvGeneratedAsset.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvGeneratedAsset extends Model
{
    protected $fillable = [
        'campaign_id',
        'type',
        'titles',
        'long_titles',
        'descriptions',
        'tokens_used',
        'model_used',
    ];

    protected $casts = [
        'titles' => 'array',
        'long_titles' => 'array',
        'descriptions' => 'array',
        'tokens_used' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdvCampaign::class, 'campaign_id');
    }

    // Helpers
    public function getTotalAssets(): int
    {
        $count = count($this->titles ?? []);
        $count += count($this->long_titles ?? []);
        $count += count($this->descriptions ?? []);
        return $count;
    }

    public function toExportArray(): array
    {
        $data = [];

        foreach ($this->titles as $title) {
            $data[] = ['type' => 'title', 'content' => $title];
        }

        if ($this->long_titles) {
            foreach ($this->long_titles as $longTitle) {
                $data[] = ['type' => 'long_title', 'content' => $longTitle];
            }
        }

        foreach ($this->descriptions as $description) {
            $data[] = ['type' => 'description', 'content' => $description];
        }

        return $data;
    }
}
```

---

## 🔧 Service Layer

### `app/Services/Tools/CampaignGenerator/CampaignAssetsGenerator.php`

```php
<?php
namespace App\Services\Tools\CampaignGenerator;

use App\Models\AdvCampaign;
use App\Models\AdvGeneratedAsset;
use App\Services\OpenAIService;
use App\Services\TokenTrackingService;
use Illuminate\Support\Facades\Log;

class CampaignAssetsGenerator
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    /**
     * Genera asset per una campagna
     */
    public function generate(AdvCampaign $campaign): array
    {
        try {
            $prompt = $this->buildPrompt($campaign);
            $systemPrompt = $this->getSystemPrompt($campaign);

            $response = $this->openai->chat([
                'model' => config('services.openai.default_model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.9,
            ]);

            // Parse risposta JSON
            $content = $response['choices'][0]['message']['content'];
            $assets = json_decode($content, true);

            if (!$assets) {
                throw new \Exception('Risposta AI non valida: ' . $content);
            }

            // Salva o aggiorna asset
            $generatedAsset = AdvGeneratedAsset::updateOrCreate(
                ['campaign_id' => $campaign->id],
                [
                    'type' => $campaign->type,
                    'titles' => $assets['titoli'] ?? [],
                    'long_titles' => $assets['titoli_lunghi'] ?? [],
                    'descriptions' => $assets['descrizioni'] ?? [],
                    'tokens_used' => $response['usage']['total_tokens'],
                    'model_used' => $response['model'],
                ]
            );

            // Traccia token per billing
            $this->tokenTracker->track(
                tenantId: $campaign->tenant_id,
                tokens: $response['usage']['total_tokens'],
                model: $response['model'],
                source: 'campaign_generator',
                metadata: [
                    'campaign_id' => $campaign->id,
                    'campaign_type' => $campaign->type,
                ]
            );

            return [
                'success' => true,
                'asset_id' => $generatedAsset->id,
                'tokens_used' => $response['usage']['total_tokens'],
                'assets_count' => $generatedAsset->getTotalAssets(),
            ];

        } catch (\Exception $e) {
            Log::error('Campaign asset generation failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Genera asset per batch di campagne
     */
    public function generateBatch(array $campaignIds, int $tenantId): array
    {
        $results = [];

        $campaigns = AdvCampaign::whereIn('id', $campaignIds)
            ->where('tenant_id', $tenantId)
            ->get();

        foreach ($campaigns as $campaign) {
            $results[] = array_merge(
                ['campaign_id' => $campaign->id],
                $this->generate($campaign)
            );
        }

        return $results;
    }

    protected function buildPrompt(AdvCampaign $campaign): string
    {
        $lang = $campaign->language;
        $landing = $campaign->url;
        $campaignName = $campaign->name;
        $info = $campaign->info;
        $kws = $campaign->keywords;

        if ($campaign->isRSA()) {
            return "Genera asset per un annuncio Google Ads RSA in lingua: {$lang}.

Contesto:
- URL della landing page: {$landing}
- Nome campagna: {$campaignName}

Crea:
- 15 titoli (max 30 caratteri ciascuno)
- 4 descrizioni (max 90 caratteri ciascuna)

Requisiti:
- Almeno 5 titoli con alta rilevanza keyword: usa {$kws} ed esplora varianti semantiche
- Almeno 5 titoli focalizzati sui benefici unici del brand/prodotto: {$info}
- Almeno 5 titoli con CTA dirette (es. 'Scopri ora', 'Acquista oggi')

- Almeno 2 descrizioni devono terminare con una CTA forte
- Tutti i contenuti devono essere unici, chiari, pertinenti al contenuto della landing page, coerenti con il nome campagna e ottimizzati per massimizzare le combinazioni performanti

Output richiesto in formato JSON con i campi: titoli[], descrizioni[]";
        }

        return "Genera asset per una campagna Google Ads Performance Max in lingua: {$lang}.

Contesto:
- URL della landing page: {$landing}
- Nome campagna: {$campaignName}

Crea:
- 15 titoli brevi (max 30 caratteri)
- 5 titoli lunghi (max 90 caratteri)
- 5 descrizioni (max 90 caratteri)

Requisiti:
- Tutti gli asset devono essere coerenti con il tono del brand e basati sulle seguenti informazioni: {$info}
- Varia la semantica e lo stile tra asset per migliorare la diversità creativa
- Includi almeno 3 CTA distribuite tra titoli e descrizioni
- I contenuti devono essere coerenti con la landing page ({$landing}) e in linea con gli obiettivi della campagna ({$campaignName})

Output richiesto in formato JSON con i campi: titoli[], titoli_lunghi[], descrizioni[]";
    }

    protected function getSystemPrompt(AdvCampaign $campaign): string
    {
        $lang = $campaign->language;

        return "Agisci come un esperto certificato Google Ads con specializzazione in Responsive Search Ads (RSA) e Performance Max (PMax).

Rispondi esclusivamente in JSON valido, adattato alla lingua: '{$lang}'.

Tutti gli asset devono essere coerenti con le best practice Google, ottimizzati per Ad Strength 'Eccellente', e strutturati per massimizzare la diversità, la rilevanza e l'efficacia delle combinazioni generate da Google AI.

Rispetta RIGOROSAMENTE i limiti di caratteri:
- Titoli brevi: MAX 30 caratteri
- Titoli lunghi: MAX 90 caratteri
- Descrizioni: MAX 90 caratteri

Non superare MAI questi limiti.";
    }
}
```

---

## 🎮 Controller

### `app/Http/Controllers/Tenant/Tools/ADV/CampaignGeneratorController.php`

```php
<?php
namespace App\Http\Controllers\Tenant\Tools\ADV;

use App\Http\Controllers\Controller;
use App\Models\AdvCampaign;
use App\Services\Tools\CampaignGenerator\CampaignAssetsGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CampaignAssetsExport;

class CampaignGeneratorController extends Controller
{
    public function __construct(
        protected CampaignAssetsGenerator $generator
    ) {}

    public function index()
    {
        $campaigns = AdvCampaign::forTenant(Auth::user()->tenant_id)
            ->with('assets')
            ->latest()
            ->get();

        $stats = [
            'total' => $campaigns->count(),
            'with_assets' => $campaigns->filter->hasAssets()->count(),
            'total_tokens' => $campaigns->sum(fn($c) => $c->assets?->tokens_used ?? 0),
        ];

        return view('tenant.tools.adv.campaign-generator.index', compact('campaigns', 'stats'));
    }

    public function create()
    {
        return view('tenant.tools.adv.campaign-generator.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'info' => 'nullable|string',
            'keywords' => 'nullable|string',
            'type' => 'required|in:rsa,pmax',
            'language' => 'required|string|max:10',
            'url' => 'nullable|url',
        ]);

        $campaign = AdvCampaign::create([
            'tenant_id' => Auth::user()->tenant_id,
            ...$validated,
        ]);

        return redirect()
            ->route('tenant.tools.adv.campaign-generator.index')
            ->with('success', 'Campagna creata con successo!');
    }

    public function update(Request $request, AdvCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'info' => 'nullable|string',
            'keywords' => 'nullable|string',
            'type' => 'required|in:rsa,pmax',
            'language' => 'required|string|max:10',
            'url' => 'nullable|url',
        ]);

        $campaign->update($validated);

        return back()->with('success', 'Campagna aggiornata!');
    }

    public function destroy(AdvCampaign $campaign)
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return back()->with('success', 'Campagna eliminata!');
    }

    /**
     * Genera asset per singola campagna
     */
    public function generate(AdvCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        $result = $this->generator->generate($campaign);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => "Asset generati con successo! ({$result['assets_count']} asset, {$result['tokens_used']} token)",
                'asset_id' => $result['asset_id'],
                'tokens_used' => $result['tokens_used'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 422);
    }

    /**
     * Genera asset per batch di campagne
     */
    public function generateBatch(Request $request)
    {
        $validated = $request->validate([
            'campaign_ids' => 'required|array',
            'campaign_ids.*' => 'exists:adv_campaigns,id',
        ]);

        $results = $this->generator->generateBatch(
            $validated['campaign_ids'],
            Auth::user()->tenant_id
        );

        $successCount = collect($results)->where('success', true)->count();
        $totalTokens = collect($results)->sum('tokens_used');

        return response()->json([
            'success' => true,
            'message' => "{$successCount} campagne elaborate con successo. Token totali: {$totalTokens}",
            'results' => $results,
        ]);
    }

    /**
     * Export asset in CSV
     */
    public function exportCsv(AdvCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        if (!$campaign->hasAssets()) {
            return back()->with('error', 'Nessun asset da esportare');
        }

        $filename = "campaign_{$campaign->id}_assets_" . now()->format('Y-m-d') . ".csv";

        return Excel::download(
            new CampaignAssetsExport($campaign->assets),
            $filename
        );
    }

    /**
     * Export asset in JSON
     */
    public function exportJson(AdvCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        if (!$campaign->hasAssets()) {
            return back()->with('error', 'Nessun asset da esportare');
        }

        $data = [
            'campaign' => [
                'name' => $campaign->name,
                'type' => $campaign->type,
                'language' => $campaign->language,
            ],
            'assets' => [
                'titles' => $campaign->assets->titles,
                'long_titles' => $campaign->assets->long_titles,
                'descriptions' => $campaign->assets->descriptions,
            ],
            'metadata' => [
                'tokens_used' => $campaign->assets->tokens_used,
                'model_used' => $campaign->assets->model_used,
                'generated_at' => $campaign->assets->created_at->toISOString(),
            ],
        ];

        $filename = "campaign_{$campaign->id}_assets_" . now()->format('Y-m-d') . ".json";

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Import CSV bulk campagne
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $imported = 0;

        while (($row = fgetcsv($handle, 2000, ',')) !== false) {
            if (count($row) < 6) continue;

            [$language, $name, $info, $keywords, $type, $url] = $row;

            AdvCampaign::create([
                'tenant_id' => Auth::user()->tenant_id,
                'language' => trim($language),
                'name' => trim($name),
                'info' => trim($info),
                'keywords' => trim($keywords),
                'type' => in_array(strtolower(trim($type)), ['rsa', 'pmax']) ? strtolower(trim($type)) : 'rsa',
                'url' => trim($url),
            ]);

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "{$imported} campagne importate con successo!");
    }
}
```

---

## 🎨 UI/UX Views

### `resources/views/tenant/tools/adv/campaign-generator/index.blade.php`

```blade
@extends('layouts.tenant')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="campaignGenerator()">

    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h1 class="text-3xl font-bold text-gray-900">📢 AI Campaign Generator</h1>
            <p class="mt-2 text-sm text-gray-600">Genera asset Google Ads RSA e Performance Max con AI</p>
        </div>
        <div class="mt-4 flex gap-3 md:mt-0 md:ml-4">
            <button @click="showImportModal = true"
                    class="btn btn-secondary">
                📥 Import CSV
            </button>
            <a href="{{ route('tenant.tools.adv.campaign-generator.create') }}"
               class="btn btn-primary">
                ➕ Nuova Campagna
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-gray-500 truncate">Totale Campagne</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-gray-500 truncate">Con Asset Generati</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ $stats['with_assets'] }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dt class="text-sm font-medium text-gray-500 truncate">Token Totali Usati</dt>
                        <dd class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_tokens']) }}</dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Actions --}}
    <div class="bg-white shadow rounded-lg p-4 mb-4" x-show="selectedCampaigns.length > 0">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-700">
                <span x-text="selectedCampaigns.length"></span> campagne selezionate
            </span>
            <div class="flex gap-3">
                <button @click="generateBatch()" class="btn btn-sm btn-primary">
                    ⚡ Genera Asset Batch
                </button>
                <button @click="deleteBatch()" class="btn btn-sm btn-danger">
                    🗑️ Elimina Selezionate
                </button>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table id="campaignsTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" @change="toggleAll($event)" class="form-checkbox">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keywords</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lingua</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($campaigns as $campaign)
                <tr x-data="{ editing: false }">
                    <td class="px-6 py-4">
                        <input type="checkbox"
                               :value="{{ $campaign->id }}"
                               @change="toggleCampaign($event, {{ $campaign->id }})"
                               class="form-checkbox">
                    </td>

                    {{-- Nome (inline edit) --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="text"
                               x-model="editData.name"
                               x-init="editData.name = '{{ $campaign->name }}'"
                               @blur="saveCampaign({{ $campaign->id }})"
                               class="form-input text-sm border-0 focus:ring-2 focus:ring-indigo-500">
                    </td>

                    {{-- Info --}}
                    <td class="px-6 py-4">
                        <textarea x-model="editData.info"
                                  x-init="editData.info = '{{ $campaign->info }}'"
                                  @blur="saveCampaign({{ $campaign->id }})"
                                  rows="2"
                                  class="form-textarea text-sm w-full border-0"></textarea>
                    </td>

                    {{-- Keywords --}}
                    <td class="px-6 py-4">
                        <textarea x-model="editData.keywords"
                                  x-init="editData.keywords = '{{ $campaign->keywords }}'"
                                  @blur="saveCampaign({{ $campaign->id }})"
                                  rows="2"
                                  class="form-textarea text-sm w-full border-0"></textarea>
                    </td>

                    {{-- Tipo --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <select x-model="editData.type"
                                x-init="editData.type = '{{ $campaign->type }}'"
                                @change="saveCampaign({{ $campaign->id }})"
                                class="form-select text-sm border-0">
                            <option value="rsa">RSA</option>
                            <option value="pmax">PMax</option>
                        </select>
                    </td>

                    {{-- Lingua --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="text"
                               x-model="editData.language"
                               x-init="editData.language = '{{ $campaign->language }}'"
                               @blur="saveCampaign({{ $campaign->id }})"
                               class="form-input text-sm w-20 border-0">
                    </td>

                    {{-- Asset Status --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($campaign->hasAssets())
                            <button @click="viewAssets({{ $campaign->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm">
                                ✅ Visualizza ({{ $campaign->assets->getTotalAssets() }})
                            </button>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ number_format($campaign->assets->tokens_used) }} token
                            </div>
                        @else
                            <span class="text-gray-400 text-sm">Nessuno</span>
                        @endif
                    </td>

                    {{-- Azioni --}}
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button @click="generateSingle({{ $campaign->id }})"
                                class="text-green-600 hover:text-green-900 mr-3">
                            ⚡ Genera
                        </button>

                        @if($campaign->hasAssets())
                        <div class="inline-flex gap-2">
                            <a href="{{ route('tenant.tools.adv.campaign-generator.export-csv', $campaign) }}"
                               class="text-blue-600 hover:text-blue-900">CSV</a>
                            <a href="{{ route('tenant.tools.adv.campaign-generator.export-json', $campaign) }}"
                               class="text-blue-600 hover:text-blue-900">JSON</a>
                        </div>
                        @endif

                        <button @click="deleteCampaign({{ $campaign->id }})"
                                class="text-red-600 hover:text-red-900 ml-3">
                            🗑️
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal Import CSV --}}
    <div x-show="showImportModal"
         class="fixed inset-0 z-50 overflow-y-auto"
         x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showImportModal = false"></div>

            <div class="relative bg-white rounded-lg max-w-lg w-full p-6">
                <h3 class="text-lg font-medium mb-4">Import Campagne CSV</h3>

                <form action="{{ route('tenant.tools.adv.campaign-generator.import-csv') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">File CSV</label>
                        <input type="file" name="csv_file" accept=".csv" required class="form-input w-full">
                        <p class="text-xs text-gray-500 mt-2">
                            Formato: <code>language,name,info,keywords,type,url</code>
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button"
                                @click="showImportModal = false"
                                class="btn btn-secondary">
                            Annulla
                        </button>
                        <button type="submit" class="btn btn-primary">
                            📥 Importa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function campaignGenerator() {
    return {
        selectedCampaigns: [],
        editData: {},
        showImportModal: false,

        toggleCampaign(event, id) {
            if (event.target.checked) {
                this.selectedCampaigns.push(id);
            } else {
                this.selectedCampaigns = this.selectedCampaigns.filter(c => c !== id);
            }
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedCampaigns = @json($campaigns->pluck('id'));
            } else {
                this.selectedCampaigns = [];
            }
        },

        async saveCampaign(id) {
            const response = await fetch(`/tenant/tools/adv/campaign-generator/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.editData),
            });

            if (response.ok) {
                // Success feedback
                console.log('Campagna aggiornata');
            }
        },

        async generateSingle(id) {
            const response = await fetch(`/tenant/tools/adv/campaign-generator/${id}/generate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Errore: ' + result.error);
            }
        },

        async generateBatch() {
            if (!confirm(`Generare asset per ${this.selectedCampaigns.length} campagne?`)) return;

            const response = await fetch('/tenant/tools/adv/campaign-generator/generate-batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ campaign_ids: this.selectedCampaigns }),
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                location.reload();
            }
        },

        async deleteCampaign(id) {
            if (!confirm('Eliminare questa campagna?')) return;

            await fetch(`/tenant/tools/adv/campaign-generator/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            location.reload();
        },

        viewAssets(id) {
            window.location.href = `/tenant/tools/adv/campaign-generator/${id}/assets`;
        }
    };
}
</script>
@endpush
@endsection
```

---

## ✅ Checklist Implementazione

### Backend
- [ ] Migration `create_adv_campaigns_tables.php`
- [ ] Model `AdvCampaign` con relationships
- [ ] Model `AdvGeneratedAsset`
- [ ] Service `CampaignAssetsGenerator`
- [ ] Controller `CampaignGeneratorController`
- [ ] Policy `AdvCampaignPolicy`
- [ ] Routes in `tenant.php`
- [ ] Token tracking integration
- [ ] Export `CampaignAssetsExport` class

### Frontend
- [ ] View `index.blade.php` con DataTables
- [ ] Inline editing con Alpine.js
- [ ] Modal import CSV
- [ ] Stats cards real-time
- [ ] Character counter per asset
- [ ] Loading states
- [ ] Toast notifications

### Testing
- [ ] Unit test `CampaignAssetsGenerator`
- [ ] Feature test CRUD campagne
- [ ] Test import CSV
- [ ] Test export CSV/JSON
- [ ] Test batch generation
- [ ] Test token tracking

### UI/UX
- [ ] Responsive design
- [ ] Dark mode support
- [ ] Accessibility (ARIA)
- [ ] Loading spinners
- [ ] Error handling UI

---

## 📊 Stima Token Consumption

- **RSA (15 titoli + 4 descrizioni)**: ~500-800 token per campagna
- **PMax (15+5 titoli + 5 descrizioni)**: ~800-1200 token per campagna
- **Batch 10 campagne**: ~5000-10000 token

**Modello consigliato**: `gpt-4o-mini` per costo/qualità ottimale
