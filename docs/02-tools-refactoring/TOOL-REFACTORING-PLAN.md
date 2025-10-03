# Piano di Refactoring Tool WordPress → Laravel

## 📊 Analisi Tool Esistenti

### Tool Analizzati

#### 🎯 **ADV (Advertising)**

1. **AI Campaign Generator** (`ai-campaign-generator.php` - 622 righe)
   - **Funzionalità**: Genera asset per campagne Google Ads (RSA e PMax) usando OpenAI
   - **Features**:
     - Creazione campagne con briefing, keyword, tipo (RSA/PMax), lingua, URL
     - Generazione automatica titoli (15 brevi + 5 lunghi), descrizioni (4-5)
     - Tabelle DB custom: `ai_campaigns`, `ai_generated_assets`
     - Export CSV/JSON degli asset
     - Import CSV bulk di campagne
     - DataTables per visualizzazione
   - **API**: OpenAI (GPT-3.5/4-turbo/4o-mini/4o)
   - **API Key**: Hardcoded in settings (`aicg_openai_api_key`)

2. **AI Negative KW Tool** (`ai-negative-kw-tool.php` - 56 righe, file principale)
   - **Funzionalità**: Gestione keyword negate per Google Ads
   - **Features**:
     - OAuth Google Ads (vendor/autoload.php - Composer)
     - File include separati: database.php, shortcodes.php, api-handler.php, oauth-handler.php, campaign-processor.php
   - **API**: Google Ads API (OAuth 2.0)
   - **Credenziali**: Client ID, Client Secret Google Ads

#### ✍️ **COPY (Content Generation)**

3. **AI Article Generator** (`ai-article-generator.php` - 1785 righe)
   - **Funzionalità**: Generazione articoli con pipeline in 7 step
   - **Features**:
     - Step 1: SERP Analysis (SerpAPI) → estrae PAA + related searches
     - Step 2: Scraping URL top risultati
     - Step 3: Aggregazione (commentato)
     - Step 4: Generazione articolo AI
     - Step 5: Controllo grammaticale (commentato)
     - Step 6: Ottimizzazione SEO (commentato)
     - Step 7: Pubblicazione + Featured Image generata
     - Tabelle DB: `ai_plugin_editor_process`, `ai_article_steps`, `ai_article_archive`
     - Endpoint REST: `/wp-json/ai-article/v1/run-batch` (batch processing)
     - Custom prompts personalizzabili per step 4/6
     - Featured image default se mancante
   - **API**:
     - OpenAI API (generazione, controllo, SEO)
     - SerpAPI (keyword research)
     - Scraping API custom (`https://api.clementeteodonno.it`)
     - DALL-E (featured image generation)
   - **Credenziali**: API Key OpenAI, SerpAPI Key, endpoint esterni

#### 🔍 **SEO**

4. **AI Internal Links** (`ai-internal-links.php` - 495 righe)
   - **Funzionalità**: Suggerisce e inserisce link interni automatici
   - **Features**:
     - Analizza contenuto post vs altri post del blog
     - Suggerimenti AI di linking contestuale
     - Tabella DB: `wp_ail_suggestions`
     - Settings: API key, model, temperature, max suggestions
   - **API**: OpenAI (gpt-3.5-turbo/gpt-4o-mini/gpt-4o)
   - **API Key**: `ail_openai_api_key`

5. **Article Drafter / GSC Position Tracker** (`article-drafter.php` - 928 righe)
   - **Funzionalità**: Tracking posizioni Google Search Console
   - **Features**:
     - OAuth Google Search Console
     - Tracking posizioni URL/query nel tempo
     - Comparazione date
     - Export Excel
     - Tabella DB: `wp_ai_gsc_tracking`
   - **API**: Google Search Console API (OAuth 2.0)
   - **Credenziali**: `client_credentials.json` (OAuth), Google refresh token

6. **RapidAPI Search Plugin** (`rapidapi-search-plugin.php` - 374 righe)
   - **Funzionalità**: Keyword research multi-API
   - **Features**:
     - 3 API: SEMrush, SEO Keyword Research, AI Google Keyword
     - AI Keyword Generator: genera KW da briefing + URL
     - DataTables visualizzazione risultati
   - **API**:
     - RapidAPI (SEMrush, SEO Keyword, AI Google)
     - API esterna WordPress (`https://api.clementeteodonno.it/wp-json/myplugin/v1/generate-keywords`)
   - **Credenziali**:
     - RapidAPI Key (hardcoded): `c75ed69077msh61e1bf40043fd3ep186401jsna25ce4d03e50`
     - Serial Key per endpoint esterno

---

## 🔑 Inventario API e Credenziali

### API Esterne Necessarie

| API/Servizio | Tipo Credenziale | Tool Utilizzatori | Note |
|--------------|------------------|-------------------|------|
| **OpenAI API** | API Key | ADV (Campaign Gen), COPY (Article Gen), SEO (Internal Links) | Multipli modelli: gpt-3.5-turbo, gpt-4o-mini, gpt-4o, gpt-4-turbo |
| **Google Ads API** | OAuth 2.0 (Client ID/Secret) | ADV (Negative KW) | Richiede consent screen + refresh token |
| **Google Search Console API** | OAuth 2.0 (client_credentials.json) | SEO (Article Drafter) | File JSON upload + refresh token |
| **SerpAPI** | API Key | COPY (Article Gen) | Analisi SERP e PAA |
| **RapidAPI** | API Key | SEO (RapidAPI Search) | Endpoints: SEMrush, SEO Keyword, AI Google Keyword |
| **API WordPress Esterna** | Serial Key / API Key | SEO (RapidAPI), COPY (Article Gen) | `https://api.clementeteodonno.it` |
| **DALL-E (OpenAI)** | API Key (stessa OpenAI) | COPY (Article Gen) | Featured image generation |

### Struttura Admin Settings (Estensione)

```php
// Nuova tabella: admin_tool_settings
Schema::create('admin_tool_settings', function (Blueprint $table) {
    $table->id();

    // OpenAI
    $table->string('openai_api_key')->nullable();
    $table->enum('openai_default_model', ['gpt-3.5-turbo', 'gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo'])->default('gpt-4o-mini');
    $table->decimal('openai_temperature', 3, 2)->default(0.7);
    $table->integer('openai_max_tokens')->default(2048);

    // Google Ads
    $table->string('google_ads_client_id')->nullable();
    $table->string('google_ads_client_secret')->nullable();
    $table->text('google_ads_refresh_token')->nullable();

    // Google Search Console
    $table->json('gsc_credentials')->nullable(); // client_credentials.json content
    $table->text('gsc_access_token')->nullable();
    $table->text('gsc_refresh_token')->nullable();

    // SerpAPI
    $table->string('serpapi_key')->nullable();

    // RapidAPI
    $table->string('rapidapi_key')->nullable();

    // External WordPress API
    $table->string('external_api_url')->default('https://api.clementeteodonno.it');
    $table->string('external_api_serial_key')->nullable();

    $table->timestamps();
});
```

---

## 🏗️ Architettura Laravel Proposta

### 1. Database Schema

#### Tool ADV

```php
// Campaigns (Google Ads)
Schema::create('adv_campaigns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('info')->nullable();
    $table->text('keywords')->nullable();
    $table->enum('type', ['rsa', 'pmax']);
    $table->string('language', 10)->default('it');
    $table->string('url')->nullable();
    $table->timestamps();
});

Schema::create('adv_generated_assets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('campaign_id')->constrained('adv_campaigns')->onDelete('cascade');
    $table->enum('type', ['rsa', 'pmax']);
    $table->json('titles')->nullable();
    $table->json('long_titles')->nullable();
    $table->json('descriptions')->nullable();
    $table->timestamps();
});

// Negative Keywords
Schema::create('adv_negative_keywords', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('campaign_id')->nullable(); // Google Ads Campaign ID
    $table->text('keywords'); // JSON array
    $table->enum('match_type', ['exact', 'phrase', 'broad'])->default('phrase');
    $table->timestamps();
});
```

#### Tool COPY

```php
// Article Generation Process
Schema::create('copy_article_process', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('keyword');
    $table->json('kw_correlate')->nullable();
    $table->json('related_questions')->nullable();
    $table->json('link_interni')->nullable();
    $table->foreignId('post_id')->nullable(); // ID del contenuto pubblicato
    $table->enum('stato_process', ['importato', 'processing', 'completato', 'pubblicato', 'errore'])->default('importato');
    $table->text('log')->nullable();
    $table->timestamps();
});

Schema::create('copy_article_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('process_id')->constrained('copy_article_process')->onDelete('cascade');
    $table->json('step1_data')->nullable(); // SERP
    $table->json('step2_data')->nullable(); // Scraping
    $table->json('step3_data')->nullable(); // Aggregazione
    $table->json('step4_data')->nullable(); // Generazione
    $table->json('step5_data')->nullable(); // Controllo
    $table->json('step6_data')->nullable(); // SEO
    $table->json('step7_data')->nullable(); // Pubblicazione
    $table->timestamps();
});

Schema::create('copy_article_archive', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('origin_id'); // ID processo originale
    $table->string('keyword');
    $table->json('kw_correlate')->nullable();
    $table->json('related_questions')->nullable();
    $table->json('link_interni')->nullable();
    $table->foreignId('post_id')->nullable();
    $table->enum('stato_process', ['pubblicato', 'archiviato']);
    $table->text('log')->nullable();
    $table->timestamps();
});

Schema::create('copy_custom_prompts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->integer('step'); // 4 o 6
    $table->text('prompt');
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});
```

#### Tool SEO

```php
// Internal Links Suggestions
Schema::create('seo_internal_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('content_id')->constrained('contents')->onDelete('cascade');
    $table->foreignId('target_content_id')->constrained('contents');
    $table->string('anchor_text');
    $table->text('context')->nullable();
    $table->enum('status', ['suggested', 'applied', 'rejected'])->default('suggested');
    $table->timestamps();
});

// GSC Position Tracking
Schema::create('seo_gsc_tracking', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('site_url');
    $table->string('page_url');
    $table->string('query');
    $table->decimal('position', 5, 2);
    $table->integer('clicks')->default(0);
    $table->integer('impressions')->default(0);
    $table->decimal('ctr', 5, 2)->default(0);
    $table->date('check_date');
    $table->timestamps();
});

// Keyword Research Results
Schema::create('seo_keyword_research', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('keyword');
    $table->string('source'); // semrush, seo_keyword, ai_google
    $table->decimal('cpc', 8, 2)->nullable();
    $table->integer('volume')->nullable();
    $table->decimal('competition', 3, 2)->nullable();
    $table->json('additional_data')->nullable();
    $table->timestamps();
});
```

### 2. Controllers Structure

```
app/Http/Controllers/Tenant/Tools/
├── ADV/
│   ├── CampaignGeneratorController.php
│   └── NegativeKeywordController.php
├── COPY/
│   ├── ArticleGeneratorController.php
│   └── ArticleArchiveController.php
└── SEO/
    ├── InternalLinksController.php
    ├── GSCTrackingController.php
    └── KeywordResearchController.php

app/Http/Controllers/Admin/
└── ToolSettingsController.php
```

### 3. Services Structure

```
app/Services/Tools/
├── OpenAI/
│   ├── OpenAIService.php
│   ├── CampaignAssetsGenerator.php
│   ├── ArticleGenerator.php
│   └── InternalLinksAnalyzer.php
├── Google/
│   ├── GoogleAdsService.php
│   └── GoogleSearchConsoleService.php
├── External/
│   ├── SerpAPIService.php
│   ├── RapidAPIService.php
│   └── ExternalAPIService.php
└── Processors/
    ├── ArticlePipelineProcessor.php
    ├── CampaignProcessor.php
    └── KeywordResearchProcessor.php
```

### 4. Routes

```php
// routes/tenant.php

Route::prefix('tools')->name('tools.')->group(function () {

    // ADV Tools
    Route::prefix('adv')->name('adv.')->group(function () {
        Route::resource('campaigns', CampaignGeneratorController::class);
        Route::post('campaigns/{campaign}/generate', [CampaignGeneratorController::class, 'generate'])->name('campaigns.generate');
        Route::get('campaigns/{campaign}/export/{format}', [CampaignGeneratorController::class, 'export'])->name('campaigns.export');
        Route::post('campaigns/import-csv', [CampaignGeneratorController::class, 'importCsv'])->name('campaigns.import');

        Route::resource('negative-keywords', NegativeKeywordController::class);
        Route::post('google-ads/oauth', [NegativeKeywordController::class, 'initiateOAuth'])->name('google-ads.oauth');
        Route::get('google-ads/callback', [NegativeKeywordController::class, 'handleOAuthCallback'])->name('google-ads.callback');
    });

    // COPY Tools
    Route::prefix('copy')->name('copy.')->group(function () {
        Route::resource('articles', ArticleGeneratorController::class);
        Route::post('articles/import-keywords', [ArticleGeneratorController::class, 'importKeywords'])->name('articles.import');
        Route::post('articles/process-batch', [ArticleGeneratorController::class, 'processBatch'])->name('articles.batch');
        Route::post('articles/{article}/step/{step}', [ArticleGeneratorController::class, 'runStep'])->name('articles.step');
        Route::post('articles/run-remaining', [ArticleGeneratorController::class, 'runRemainingSteps'])->name('articles.run-remaining');

        Route::get('archive', [ArticleArchiveController::class, 'index'])->name('archive.index');
        Route::resource('prompts', CustomPromptController::class);
    });

    // SEO Tools
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::resource('internal-links', InternalLinksController::class);
        Route::post('internal-links/{content}/analyze', [InternalLinksController::class, 'analyze'])->name('internal-links.analyze');
        Route::post('internal-links/{link}/apply', [InternalLinksController::class, 'apply'])->name('internal-links.apply');

        Route::resource('gsc-tracking', GSCTrackingController::class);
        Route::post('gsc/oauth', [GSCTrackingController::class, 'initiateOAuth'])->name('gsc.oauth');
        Route::get('gsc/callback', [GSCTrackingController::class, 'handleOAuthCallback'])->name('gsc.callback');
        Route::get('gsc/check-positions', [GSCTrackingController::class, 'checkPositions'])->name('gsc.check');
        Route::get('gsc/export', [GSCTrackingController::class, 'export'])->name('gsc.export');

        Route::resource('keyword-research', KeywordResearchController::class);
        Route::post('keyword-research/search', [KeywordResearchController::class, 'search'])->name('keyword-research.search');
        Route::post('keyword-research/ai-generate', [KeywordResearchController::class, 'aiGenerate'])->name('keyword-research.ai-generate');
    });
});
```

### 5. Admin Settings UI Extension

**File**: `ainstein-laravel/resources/views/admin/settings/tools.blade.php`

```blade
<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">Tool API Settings</h1>

    <form method="POST" action="{{ route('admin.settings.tools.update') }}">
        @csrf

        {{-- OpenAI Settings --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">🤖 OpenAI API</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                    <input type="password" name="openai_api_key" value="{{ old('openai_api_key', $settings->openai_api_key) }}" class="form-input w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Model</label>
                    <select name="openai_default_model" class="form-select w-full">
                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                        <option value="gpt-4o-mini" selected>GPT-4o Mini</option>
                        <option value="gpt-4o">GPT-4o</option>
                        <option value="gpt-4-turbo">GPT-4 Turbo</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Temperature (0-2)</label>
                    <input type="number" step="0.1" min="0" max="2" name="openai_temperature" value="{{ old('openai_temperature', $settings->openai_temperature ?? 0.7) }}" class="form-input w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Tokens</label>
                    <input type="number" name="openai_max_tokens" value="{{ old('openai_max_tokens', $settings->openai_max_tokens ?? 2048) }}" class="form-input w-full" />
                </div>
            </div>
        </div>

        {{-- Google Ads Settings --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">📢 Google Ads API (OAuth 2.0)</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                    <input type="text" name="google_ads_client_id" value="{{ old('google_ads_client_id', $settings->google_ads_client_id) }}" class="form-input w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Client Secret</label>
                    <input type="password" name="google_ads_client_secret" value="{{ old('google_ads_client_secret', $settings->google_ads_client_secret) }}" class="form-input w-full" />
                </div>
            </div>

            @if($settings->google_ads_refresh_token)
            <div class="mt-4 p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-green-700">✅ Account Google Ads connesso</p>
            </div>
            @else
            <div class="mt-4">
                <a href="{{ route('admin.tools.google-ads.oauth') }}" class="btn btn-primary">Connetti Google Ads</a>
            </div>
            @endif
        </div>

        {{-- Google Search Console Settings --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">🔍 Google Search Console API</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Client Credentials JSON</label>
                <textarea name="gsc_credentials" rows="6" class="form-textarea w-full font-mono text-xs">{{ old('gsc_credentials', $settings->gsc_credentials) }}</textarea>
                <p class="text-sm text-gray-500 mt-1">Incolla il contenuto del file client_credentials.json da Google Cloud Console</p>
            </div>

            @if($settings->gsc_refresh_token)
            <div class="mt-4 p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-green-700">✅ Google Search Console connesso</p>
            </div>
            @else
            <div class="mt-4">
                <a href="{{ route('admin.tools.gsc.oauth') }}" class="btn btn-primary">Connetti GSC</a>
            </div>
            @endif
        </div>

        {{-- SerpAPI Settings --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">🐍 SerpAPI</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                <input type="password" name="serpapi_key" value="{{ old('serpapi_key', $settings->serpapi_key) }}" class="form-input w-full" />
            </div>
        </div>

        {{-- RapidAPI Settings --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">⚡ RapidAPI</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                <input type="password" name="rapidapi_key" value="{{ old('rapidapi_key', $settings->rapidapi_key) }}" class="form-input w-full" />
                <p class="text-sm text-gray-500 mt-1">Utilizzato per SEMrush, SEO Keyword Research, AI Google Keyword</p>
            </div>
        </div>

        {{-- External WordPress API --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">🔗 API Esterna WordPress</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Base URL</label>
                    <input type="url" name="external_api_url" value="{{ old('external_api_url', $settings->external_api_url ?? 'https://api.clementeteodonno.it') }}" class="form-input w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Serial Key</label>
                    <input type="password" name="external_api_serial_key" value="{{ old('external_api_serial_key', $settings->external_api_serial_key) }}" class="form-input w-full" />
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary">Salva Impostazioni</button>
        </div>
    </form>
</div>
```

---

## 🚀 Piano di Implementazione

### Phase 1: Setup Base (Week 1)

1. **Migrations & Models**
   - Creare tutte le migrations per tool ADV, COPY, SEO
   - Creare Models Eloquent con relationships
   - Estendere `admin_settings` con colonne tool API

2. **Admin Settings UI**
   - View `admin/settings/tools.blade.php`
   - Controller `Admin/ToolSettingsController.php`
   - Form handling per tutte le API keys
   - OAuth flow setup (Google Ads, GSC)

### Phase 2: Services Layer (Week 2)

3. **OpenAI Service**
   - `OpenAIService.php` base class
   - `CampaignAssetsGenerator.php` (ADV)
   - `ArticleGenerator.php` (COPY)
   - `InternalLinksAnalyzer.php` (SEO)
   - Token tracking integration

4. **Google Services**
   - `GoogleAdsService.php` con OAuth handling
   - `GoogleSearchConsoleService.php` con OAuth
   - Token refresh automatico
   - Error handling e logging

5. **External APIs**
   - `SerpAPIService.php`
   - `RapidAPIService.php` (3 endpoints)
   - `ExternalAPIService.php` (clementeteodonno.it)

### Phase 3: Tool ADV (Week 3)

6. **Campaign Generator**
   - Controller CRUD campagne
   - View Blade con DataTables
   - Form creazione/modifica campagne
   - Generazione asset AI (batch)
   - Export CSV/JSON
   - Import CSV bulk

7. **Negative Keywords**
   - OAuth Google Ads flow completo
   - CRUD keyword negate
   - Sincronizzazione con campagne Google

### Phase 4: Tool COPY (Week 4-5)

8. **Article Generator - Core**
   - Controller principale
   - View tabella processi (DataTables)
   - Import keywords
   - Step 1-7 implementation:
     - Step 1: SERP + PAA + related searches
     - Step 2: Scraping URL
     - Step 4: Generazione articolo
     - Step 7: Pubblicazione + featured image
   - Modal view step data (TinyMCE)

9. **Article Generator - Advanced**
   - Custom prompts CRUD
   - Batch processing endpoint
   - Archivio articoli pubblicati
   - Featured image DALL-E integration
   - Progress tracking real-time (Jobs + Broadcasting)

### Phase 5: Tool SEO (Week 6)

10. **Internal Links**
    - Analisi AI suggerimenti linking
    - CRUD suggestions
    - Apply/Reject links
    - Integrazione con contenuti esistenti

11. **GSC Tracking**
    - OAuth Google Search Console
    - Import posizioni URL/query
    - Tracking storico
    - Comparazione date
    - Export Excel/CSV

12. **Keyword Research**
    - Form search keyword
    - Multi-API results (SEMrush, SEO Keyword, AI Google)
    - AI Keyword Generator da briefing
    - Salvataggio risultati
    - DataTables visualizzazione

### Phase 6: Testing & Refinement (Week 7)

13. **Testing**
    - Unit tests per Services
    - Feature tests per Controllers
    - OAuth flow testing
    - API integration tests

14. **UI/UX Polish**
    - Responsive design
    - Loading states
    - Error handling UI
    - Success feedback
    - DataTables Italian localization

---

## ✅ Miglioramenti vs WordPress

### 1. **Sicurezza**
- ❌ WP: API keys hardcoded nel codice
- ✅ Laravel: API keys cifrate in DB, gestite da admin settings
- ❌ WP: Nonce validation limitata
- ✅ Laravel: CSRF protection, middleware auth, policies

### 2. **Multi-Tenancy**
- ❌ WP: Nessuna isolazione dati
- ✅ Laravel: Completo isolamento tenant con `tenant_id` foreign keys
- ❌ WP: User singolo admin
- ✅ Laravel: Ogni tenant gestisce i propri tool

### 3. **Performance**
- ❌ WP: Query DB non ottimizzate, no eager loading
- ✅ Laravel: Eloquent relationships, eager loading, query builder ottimizzato
- ❌ WP: No caching
- ✅ Laravel: Redis caching per API responses

### 4. **API Integration**
- ❌ WP: `wp_remote_post/get` basic
- ✅ Laravel: HTTP client con retry logic, timeout management, error handling
- ❌ WP: No rate limiting
- ✅ Laravel: Rate limiting middleware per API esterne

### 5. **Background Processing**
- ❌ WP: WP-Cron unreliable
- ✅ Laravel: Queue system (Redis/Database) con retry, timeout, job chaining
- ❌ WP: No progress tracking
- ✅ Laravel: Real-time progress con Broadcasting/Websockets

### 6. **OAuth Management**
- ❌ WP: Token refresh manuale, file JSON upload pericoloso
- ✅ Laravel: Socialite/Passport, automatic token refresh, secure storage

### 7. **Export/Import**
- ❌ WP: Export CSV custom implementation
- ✅ Laravel: Laravel Excel package, queue export, multiple formats

### 8. **UI/UX**
- ❌ WP: jQuery spaghetti, inline scripts
- ✅ Laravel: Alpine.js/Livewire componenti reattivi
- ❌ WP: Admin UI limitato
- ✅ Laravel: Tailwind UI moderna, responsive, dark mode

### 9. **Error Handling**
- ❌ WP: Error nascosti, no logging strutturato
- ✅ Laravel: Exception handling, structured logging (Monolog), Sentry integration

### 10. **Testing**
- ❌ WP: No test suite
- ✅ Laravel: PHPUnit test completi, Feature/Unit tests, CI/CD integration

---

## 📋 Checklist Implementazione

### Setup Iniziale
- [ ] Creare migrations per tutte le tabelle tool
- [ ] Estendere `admin_settings` con colonne API
- [ ] Creare Models Eloquent con relationships
- [ ] Setup seeders per dati di test

### Admin Panel
- [ ] View `admin/settings/tools.blade.php`
- [ ] Controller `Admin/ToolSettingsController.php`
- [ ] OAuth flow Google Ads
- [ ] OAuth flow Google Search Console
- [ ] Form validation e sanitization
- [ ] Success/error notifications

### Services
- [ ] `OpenAIService.php` base + token tracking
- [ ] `CampaignAssetsGenerator.php`
- [ ] `ArticleGenerator.php`
- [ ] `InternalLinksAnalyzer.php`
- [ ] `GoogleAdsService.php`
- [ ] `GoogleSearchConsoleService.php`
- [ ] `SerpAPIService.php`
- [ ] `RapidAPIService.php`
- [ ] `ExternalAPIService.php`

### Tool ADV
- [ ] Campaign Generator CRUD
- [ ] Generazione asset AI
- [ ] Export CSV/JSON
- [ ] Import CSV bulk
- [ ] Negative Keywords CRUD
- [ ] Sync con Google Ads

### Tool COPY
- [ ] Article Generator UI principale
- [ ] Import keywords
- [ ] Step 1-7 implementation
- [ ] Custom prompts CRUD
- [ ] Batch processing
- [ ] Archivio articoli
- [ ] Featured image generation

### Tool SEO
- [ ] Internal Links analyzer
- [ ] GSC OAuth + tracking
- [ ] Export Excel posizioni
- [ ] Keyword Research multi-API
- [ ] AI Keyword Generator

### Testing
- [ ] Unit tests Services
- [ ] Feature tests Controllers
- [ ] OAuth flow tests
- [ ] API integration tests
- [ ] E2E tests con Dusk

### Deployment
- [ ] Environment variables per API keys
- [ ] Queue workers setup
- [ ] Cron jobs per batch processing
- [ ] Monitoraggio errori Sentry
- [ ] Documentation completa

---

## 🎯 Priorità di Sviluppo

### 🔥 High Priority (MVP)
1. Admin Settings UI + API keys management
2. OpenAI Service base (usato da 80% tool)
3. Campaign Generator (ADV) - tool più completo
4. Article Generator core (COPY) - pipeline articoli

### 🟡 Medium Priority
5. Keyword Research (SEO) - utile per tutti i flussi
6. Internal Links (SEO) - complementare ad Article Gen
7. Negative Keywords (ADV) - richiede OAuth Google Ads

### 🔵 Low Priority (Nice to Have)
8. GSC Tracking (SEO) - richiede OAuth GSC, meno urgente
9. Custom Prompts avanzati
10. Export Excel avanzato

---

## 📊 Stima Tempi

| Fase | Attività | Giorni | Note |
|------|----------|--------|------|
| 1 | Setup Base (DB, Models, Admin) | 5 | Foundation critica |
| 2 | Services Layer | 5 | OpenAI, Google, External APIs |
| 3 | Tool ADV | 5 | Campaign Gen + Negative KW |
| 4 | Tool COPY | 8 | Article Gen pipeline completa |
| 5 | Tool SEO | 5 | Internal Links + KW Research + GSC |
| 6 | Testing & Polish | 5 | QA, bug fixing, UX refinement |
| **TOTALE** | | **33 giorni** | ~7 settimane lavorative |

---

## 🔐 Note Sicurezza

1. **API Keys Storage**
   - Tutte le chiavi cifrate in DB con Laravel Crypt
   - No hardcoding in codice
   - `.env` per credenziali sensibili solo in dev

2. **OAuth Tokens**
   - Refresh token cifrati
   - Auto-refresh prima della scadenza
   - Revoke tokens on user logout

3. **Multi-Tenant Isolation**
   - Middleware `TenantScope` globale
   - Nessun accesso cross-tenant
   - Policy authorization per ogni risorsa

4. **Rate Limiting**
   - API esterne: max 60 req/min
   - OpenAI: track tokens giornalieri per tenant
   - Queue throttling per batch processing

5. **Input Sanitization**
   - Form validation Laravel
   - XSS protection su output
   - SQL injection prevention (Eloquent ORM)

---

## 📝 Conclusioni

Il refactoring dei tool WordPress verso Laravel porterà:

✅ **Sicurezza**: Gestione centralizzata API keys, OAuth 2.0 sicuro, multi-tenancy
✅ **Performance**: Queue system, caching, DB ottimizzato
✅ **Scalabilità**: Background jobs, horizontal scaling ready
✅ **Manutenibilità**: Codice pulito, testabile, documented
✅ **UX**: UI moderna, real-time feedback, responsive

**Timeline**: 7 settimane di sviluppo full-time
**Team consigliato**: 1 Senior Laravel Developer + 1 Frontend Developer
**ROI**: Piattaforma tool professionale multi-tenant production-ready
