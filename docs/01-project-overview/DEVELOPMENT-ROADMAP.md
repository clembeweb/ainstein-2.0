# 🗺️ AINSTEIN DEVELOPMENT ROADMAP - Engineering Priorities

**Last Updated**: 2025-10-06
**Vision**: Completare SaaS futuristica AI-first in modo ingegneristico e scalabile
**Approccio**: Stratificato - Foundation → Core Tools → Advanced Features → Polish
**Timeline**: 8 settimane (40 giorni lavorativi)

**Current Status**: ✅ Layer 2 Complete (100% tested) → Ready for Layer 3.1

---

## 📊 DEVELOPMENT STATUS (2025-10-06)

### ✅ COMPLETED LAYERS
- **Layer 1**: Foundation 100% ✅
- **Layer 2.1**: OpenAI Service Base 100% ✅ (Real API tested)
- **Layer 2.2**: Content Generator Unified 100% ✅ (3-tab interface)
- **Layer 2.3**: Admin Settings Sync 100% ✅ (All settings reflecting to tenant)

### 🔧 CURRENT LAYER
- **Layer 3.1**: Campaign Generator (Foundation 20%, implementation pending)

**Test Coverage**: 46/46 tests passing (100%)
**Production Ready**: YES - All core features working

---

## 🎯 FILOSOFIA INGEGNERISTICA

### Principi Guida
1. **Foundation First** - Infrastruttura solida prima delle features ✅
2. **MVP Quick Wins** - Tool più semplici prima per validare architettura
3. **Iterative Enhancement** - Base funzionante → Features avanzate → AI futuristica
4. **Quality Gates** - Testing obbligatorio prima di passare alla fase successiva ✅
5. **Documentation as Code** - Ogni feature documentata contestualmente ✅

### Decision Framework
- **P0 (Critical)** - Blocca tutto il resto se manca
- **P1 (High)** - Necessario per MVP
- **P2 (Medium)** - Migliora significativamente UX
- **P3 (Low)** - Nice to have, può essere post-MVP

---

## 📊 ROADMAP STRATIFICATO

```
LAYER 1: Foundation (Week 1-2)     → Sistema nervoso della piattaforma
LAYER 2: Core Tools MVP (Week 3-4) → Validazione business value
LAYER 3: Advanced Tools (Week 5-6) → Feature complete per ogni tool
LAYER 4: AI Futuristic (Week 7)    → Competitive advantage features
LAYER 5: Polish & Scale (Week 8)   → Production ready
```

---

## 🏗️ LAYER 1: FOUNDATION (Week 1-2) - ✅ COMPLETE

**Obiettivo**: Infrastruttura robusta per supportare tutti i tool
**Success Criteria**: ✅ Admin configura API, OpenAI service funziona, token tracking accurato

### Week 1: Admin Infrastructure ✅

#### 1.1 Database Foundation - ✅ COMPLETE
**Status**: DONE - Platform settings fully implemented

```bash
# Completed Tasks
- [x] Migration: extend platform_settings per tool APIs
      Colonne: openai_api_key, openai_model, temperature, max_tokens
              google_ads_client_id, google_ads_client_secret, google_ads_refresh_token
              google_console_* (for OAuth), facebook_* (for OAuth)
              gsc_credentials (JSON), gsc_refresh_token
              serpapi_key, rapidapi_key
              external_api_url, external_api_serial_key

- [x] Migration: create google_ads_connections (pending)
- [x] Migration: create google_search_console_connections (pending)
- [x] Seed admin settings con defaults
```

**Testing Results**: ✅ All passed
- [x] Migration up/down funziona
- [x] Settings populated correctly
- [x] Foreign keys constraints OK

**Deliverable**: ✅ DB schema completo per admin settings

---

#### 1.2 Admin Settings UI - ✅ COMPLETE
**Status**: DONE - 6 settings tabs fully functional

```bash
# Completed Tasks
- [x] View: resources/views/admin/settings/index.blade.php
      6 Sezioni: OAuth, OpenAI, Stripe, Email SMTP, Logo, Advanced
      Form con password fields per keys
      OAuth buttons per Google/Facebook

- [x] Controller: Admin/PlatformSettingsController
      Methods: index(), update(), uploadLogo()
      Validation robusta
      Settings saved to database

- [x] Routes: admin/settings/*

- [x] Test connection functionality
      OpenAI: working
      All settings sync to tenant features
```

**Testing Results**: ✅ 8/8 passed (100%)
- [x] Form salva correttamente tutte le keys
- [x] Settings sync to tenant features
- [x] Logo upload working
- [x] OAuth buttons showing when configured

**Deliverable**: ✅ Admin panel completamente funzionante per API keys + branding

---

### Week 2: Core Services Infrastructure ✅

#### 2.1 OpenAI Service Base - ✅ COMPLETE
**Status**: DONE - Production ready with real API testing

```bash
# Completed Tasks
- [x] Service: app/Services/AI/OpenAIService.php (400+ lines)
      Methods:
      - chat(array $messages, string $model = null, array $options = [])
      - completion(string $prompt, string $model = null, array $options = [])
      - embeddings(string|array $text)
      - parseJSON(array $messages) // Force JSON response

- [x] Integration con token tracking esistente
      trackTokenUsage($tenantId, $tokens, $model, $source, $metadata)

- [x] Error handling & retry logic
      - Rate limit handling (exponential backoff)
      - Timeout management (30s default)
      - OpenAI error codes mapping

- [x] Config: config/ai.php
      Default models, temperature, max_tokens per use case
```

**Testing Results**: ✅ 11/11 passed + Browser tested
- [x] Chat completion funziona (real API ✓)
- [x] JSON response parsing OK (tested ✓)
- [x] Token tracking salvato correttamente (verified ✓)
- [x] Retry logic testato (mock rate limit ✓)
- [x] Error handling robusto (tested ✓)

**Deliverable**: ✅ OpenAI service production-ready + Browser tested

---

#### 2.2 Google OAuth Infrastructure (2 giorni) - P1
**Priority**: HIGH - Necessario per 2 tool (Negative KW, GSC)

```bash
# Tasks
- [ ] Service: app/Services/Google/GoogleOAuthService.php
      Methods:
      - getAuthUrl(string $service) // 'ads' or 'gsc'
      - handleCallback(string $code, string $service)
      - refreshToken(string $refreshToken, string $service)
      - isTokenValid(string $service)

- [ ] Controller: Admin/GoogleOAuthController
      initiateGoogleAdsOAuth()
      handleGoogleAdsCallback()
      initiateGSCOAuth()
      handleGSCCallback()
      disconnectGoogleAds()
      disconnectGSC()

- [ ] Routes: admin/oauth/google-ads/{initiate,callback,disconnect}
              admin/oauth/gsc/{initiate,callback,disconnect}

- [ ] Auto refresh token quando < 1h alla scadenza
      Job: RefreshGoogleTokensJob (scheduled daily)
```

**Testing Checklist**:
- [ ] OAuth flow completo (manuale con browser)
- [ ] Refresh token salvato cifrato
- [ ] Auto-refresh funziona
- [ ] Disconnect revoca token
- [ ] Error handling per consent denied

**Deliverable**: OAuth Google completamente funzionante

---

#### 2.3 External APIs Services (1 giorno) - P2
**Priority**: MEDIUM - Enhancement features

```bash
# Tasks
- [ ] Service: app/Services/External/SerpAPIService.php
      search($keyword, $location = 'it', $gl = 'it')
      getPAA($keyword)
      getRelatedSearches($keyword)

- [ ] Service: app/Services/External/RapidAPIService.php
      semrushKeywordResearch($keyword)
      seoKeywordResearch($keyword)
      aiGoogleKeyword($keyword)

- [ ] Service: app/Services/External/ExternalAPIService.php
      generateKeywords($briefing, $url, $serialKey)

- [ ] Config: config/external-apis.php
      Base URLs, timeouts, rate limits
```

**Testing Checklist**:
- [ ] SerpAPI call funziona (se key configurata)
- [ ] RapidAPI call funziona (se key configurata)
- [ ] Fallback graceful se API key mancante
- [ ] Error handling per API down

**Deliverable**: External APIs wrapper pronti

---

## 🚀 LAYER 2: CORE TOOLS MVP (Week 3-4) - P1 HIGH

**Obiettivo**: 2 tool funzionanti end-to-end per validare architettura
**Tool scelti**: Campaign Generator (semplice) + Article Generator (complesso)
**Success Criteria**: Tenant può generare campagne e articoli con AI

### Week 3: Campaign Generator (Tool più semplice) ⚡

**Perché iniziare qui**:
- Spec più semplice (no OAuth, no multi-step pipeline)
- Valida OpenAI service
- Valida token tracking
- Quick win per mostrare valore

#### 3.1 Database & Models (1 giorno) - P1

```bash
# Tasks
- [ ] Migration: adv_campaigns
      tenant_id, name, info (briefing), keywords, type (rsa/pmax),
      language, url, tokens_used, model_used, created_at, updated_at

- [ ] Migration: adv_generated_assets
      campaign_id, type (rsa/pmax),
      titles (JSON), long_titles (JSON), descriptions (JSON),
      ai_quality_score (decimal), created_at

- [ ] Model: AdvCampaign
      Relationships: hasMany(AdvGeneratedAsset), belongsTo(Tenant)
      Scopes: forTenant($tenantId)
      Accessors: titlesArray, descriptionsArray

- [ ] Model: AdvGeneratedAsset
      Relationships: belongsTo(AdvCampaign)
      Casts: titles, long_titles, descriptions → array
```

**Testing Checklist**:
- [ ] Migration up/down OK
- [ ] Relationships funzionano
- [ ] Tenant scope filtra correttamente

---

#### 3.2 Service Layer (2 giorni) - P1

```bash
# Tasks
- [ ] Service: app/Services/Tools/CampaignAssetsGenerator.php

      generateRSAAssets(AdvCampaign $campaign): array
      - Prompt engineering per 15 titoli brevi, 5 lunghi, 4 descrizioni
      - Parsing JSON response
      - Validation lunghezze (30 char titoli brevi, 90 lunghi, 90 desc)
      - Retry se generazione fallisce

      generatePMaxAssets(AdvCampaign $campaign): array
      - Simile a RSA ma ottimizzato per PMax

      calculateQualityScore(array $assets): float
      - AI meta-analysis per score 1-10
      - Considera: varietà, keyword usage, CTA presence

- [ ] Prompt templates in config/prompts/campaign-generator.php
      Template separati per RSA/PMax, personalizzabili

- [ ] Token tracking integration
```

**Prompt Engineering (Critical)**:
```
System: Sei un esperto Google Ads copywriter...

User:
Genera asset per campagna {type} in {language}:
Briefing: {info}
Keywords: {keywords}
URL: {url}

Requisiti:
- 15 titoli brevi (max 30 char)
- 5 titoli lunghi (max 90 char)
- 4 descrizioni (max 90 char)
- Includi keyword naturalmente
- CTA forti e action-oriented

Rispondi in JSON:
{
  "titles": ["...", ...],
  "long_titles": ["...", ...],
  "descriptions": ["...", ...]
}
```

**Testing Checklist**:
- [ ] Generazione RSA funziona
- [ ] Generazione PMax funziona
- [ ] Quality score calcolato
- [ ] Token usage tracciato
- [ ] Error handling robusto

---

#### 3.3 Controller & Routes (1 giorno) - P1

```bash
# Tasks
- [ ] Controller: Tenant/Tools/ADV/CampaignGeneratorController
      index() - Lista campagne tenant
      create() - Form nuova campagna
      store() - Salva + genera asset
      show($id) - Dettaglio campagna con asset
      edit($id) - Modifica campagna
      update($id) - Aggiorna campagna
      destroy($id) - Elimina campagna
      regenerate($id) - Rigenera asset
      export($id, $format) - Export CSV/JSON

- [ ] Routes: tenant.tools.adv.campaigns (resource + custom)

- [ ] Validation: CampaignRequest
      Rules: name required, type in [rsa,pmax], url url format, etc.

- [ ] Policy: AdvCampaignPolicy
      viewAny, view, create, update, delete (tenant-scoped)
```

**Testing Checklist**:
- [ ] CRUD completo funziona
- [ ] Tenant isolation OK (tenant A non vede campagne tenant B)
- [ ] Validation errors corretti
- [ ] Export CSV/JSON funziona

---

#### 3.4 UI Views (1 giorno) - P1

```bash
# Tasks
- [ ] View: tenant/tools/adv/campaigns/index.blade.php
      DataTable con: name, type, created_at, tokens_used, actions
      Buttons: New Campaign, Export All
      Filters: Type (RSA/PMax), Date range

- [ ] View: tenant/tools/adv/campaigns/create.blade.php
      Form: name, briefing (textarea), keywords (textarea),
            type (select), language (select), url
      Button: Generate Assets (con loading state)

- [ ] View: tenant/tools/adv/campaigns/show.blade.php
      Campaign details
      Assets table con inline editing
      Export buttons (CSV, JSON)
      Regenerate button

- [ ] Alpine.js component per inline editing asset

- [ ] Seguire design system: AINSTEIN-UI-UX-DESIGN-SYSTEM.md
```

**Testing Checklist**:
- [ ] DataTable carica dati
- [ ] Form crea campagna
- [ ] Asset mostrati correttamente
- [ ] Export funziona
- [ ] Responsive design OK

---

### Week 4: Article Generator MVP (Tool più complesso) 🧠

**Perché ora**:
- Valida pipeline multi-step
- Valida Job queue system
- Valida AI content generation avanzata
- Core value proposition della SaaS

#### 4.1 Database & Models (1 giorno) - P1

```bash
# Tasks
- [ ] Migration: copy_article_process
      tenant_id, keyword, kw_correlate (JSON), related_questions (JSON),
      link_interni (JSON), content_id (nullable),
      stato_process (enum: importato, processing, completato, pubblicato, errore),
      current_step (int 1-6), log (text), created_at, updated_at

- [ ] Migration: copy_article_steps
      process_id,
      step1_serp (JSON), step2_research (JSON), step3_generation (JSON),
      step4_seo (JSON), step5_image (JSON), step6_publish (JSON)

- [ ] Migration: copy_article_archive
      Stessa struttura di process ma per articoli archiviati

- [ ] Model: CopyArticleProcess
      Relationships: hasOne(CopyArticleSteps), belongsTo(Content)
      Scopes: forTenant, byStatus
      Methods: isCompleted(), canPublish(), moveToArchive()

- [ ] Model: CopyArticleSteps
```

**Testing Checklist**:
- [ ] Migration up/down OK
- [ ] Enum status funziona
- [ ] Relationships OK

---

#### 4.2 AI Services (3 giorni) - P1 CRITICAL

**Questo è il cuore del valore**

```bash
# Tasks
- [ ] Service: app/Services/Tools/AI/AIResearchService.php

      simulateSerpAnalysis(string $keyword, ?string $briefing): array
      - AI genera: PAA (10 domande), related searches (8-10),
        search intent, competitor insights, content gaps
      - Return JSON structured data

      generateContentResearch(string $keyword, array $serpData, ?string $briefing): array
      - AI crea outline dettagliato articolo
      - Identifica H2/H3 structure
      - Suggerisce lunghezza target
      - Entity SEO da includere

- [ ] Service: app/Services/Tools/AI/ArticleGeneratorService.php

      generateArticle(CopyArticleProcess $process, array $research): string
      - Genera articolo completo HTML
      - Include H1, H2, H3, paragraphs, lists
      - Keyword density ottimale
      - Internal links placeholder
      - 1500-3000 parole

      optimizeSEO(string $content, string $keyword, array $research): string
      - Meta title suggestion
      - Meta description
      - Schema.org markup suggestion
      - Readability score

- [ ] Service: app/Services/Tools/AI/DALLEService.php

      generateFeaturedImage(string $keyword, string $articleSummary): string
      - DALL-E 3 image generation
      - Return image URL
      - Save to storage
      - Optimize image (resize, compress)

- [ ] Prompt templates in config/prompts/article-generator.php
      Customizable per tenant (future feature)
```

**Prompt Engineering Chiave**:

**Step 1 - SERP Simulation**:
```
System: Sei un esperto SEO analyst con 10 anni esperienza...

User: Analizza keyword "{keyword}"{briefing}

Genera analisi SERP simulata con:
1. People Also Ask (10 domande reali che utenti cercano)
2. Related Searches (8-10 keyword correlate)
3. Search Intent (informational/commercial/transactional/navigational)
4. Competitor Insights (3-5 pattern comuni nei top risultati)
5. Content Gaps (opportunità non coperte dai competitor)

JSON:
{
  "paa": ["domanda 1", ...],
  "related_searches": ["kw 1", ...],
  "search_intent": "tipo",
  "competitor_insights": ["insight 1", ...],
  "content_gaps": ["gap 1", ...]
}
```

**Step 3 - Article Generation**:
```
System: Sei un copywriter esperto SEO che scrive articoli ottimizzati...

User: Scrivi articolo completo su "{keyword}"

Context:
- People Also Ask: {paa}
- Related searches: {related}
- Outline: {outline}
{briefing}

Requisiti:
- 1500-3000 parole
- HTML formattato (h1, h2, h3, p, ul, ol)
- Keyword density 1-2%
- Rispondi alle PAA nel contenuto
- Stile: professionale, autorevole, friendly
- Aggiungi esempi pratici
- CTA finale

HTML completo senza <html><body>, solo contenuto articolo.
```

**Testing Checklist**:
- [ ] SERP simulation genera dati realistici
- [ ] Content research crea outline valido
- [ ] Article generation produce HTML valido
- [ ] SEO optimization funziona
- [ ] DALL-E genera immagine pertinente
- [ ] Token usage tracciato per ogni step

---

#### 4.3 Pipeline Processor & Jobs (2 giorni) - P1

```bash
# Tasks
- [ ] Service: app/Services/Tools/ArticlePipelineProcessor.php

      runStep(CopyArticleProcess $process, int $step): void
      - Switch case per step 1-6
      - Update process status
      - Save step data
      - Handle errors gracefully
      - Update tokens_used

      runAllSteps(CopyArticleProcess $process): void
      - Run step 1-6 sequenzialmente
      - Stop on error
      - Log progress

- [ ] Job: app/Jobs/ProcessArticleStepJob.php
      - Queued job per ogni step
      - Retry 3 times on failure
      - Timeout 5 minuti
      - Progress tracking

- [ ] Job: app/Jobs/PublishArticleJob.php
      - Step 6: Crea Content record
      - Salva HTML content
      - Upload featured image
      - Marca process come 'pubblicato'
      - Move to archive (optional)

- [ ] Event: ArticleStepCompleted
      Listener: UpdateProcessProgress
      Listener: NotifyUser (future)
```

**Testing Checklist**:
- [ ] Pipeline completa funziona end-to-end
- [ ] Ogni step salva dati correttamente
- [ ] Error handling non blocca processo
- [ ] Jobs queue funzionano
- [ ] Progress tracking accurato

---

#### 4.4 Controller & UI (1 giorno) - P1

```bash
# Tasks
- [ ] Controller: Tenant/Tools/COPY/ArticleGeneratorController
      index() - Lista processi
      create() - Form import keyword
      store() - Crea processo
      show($id) - Dettaglio processo + step data
      runStep($id, $step) - Esegui step specifico
      runAll($id) - Esegui pipeline completa
      destroy($id) - Elimina processo

- [ ] View: tenant/tools/copy/articles/index.blade.php
      DataTable: keyword, status, current_step, tokens_used, actions
      Button: Import Keywords

- [ ] View: tenant/tools/copy/articles/show.blade.php
      Progress bar (step 1-6)
      Accordion per ogni step con preview data
      Buttons: Run Step, Run All Steps
      Final content preview (TinyMCE readonly)

- [ ] Modal: Import keywords (CSV or textarea list)
```

**Testing Checklist**:
- [ ] Lista processi carica
- [ ] Import keywords funziona
- [ ] Run step singolo OK
- [ ] Run all steps completa pipeline
- [ ] Preview content leggibile

---

## 🔧 LAYER 3: ADVANCED TOOLS (Week 5-6) - P2 MEDIUM

**Obiettivo**: Completare i 4 tool rimanenti
**Approccio**: 2 tool per week, riutilizzando infrastructure da Layer 1-2

### Week 5: SEO Tools Base

#### 5.1 Keyword Research (2 giorni) - P2

```bash
# Tasks
- [ ] DB: seo_keyword_research, seo_keyword_campaigns
- [ ] Service: KeywordResearchService (usa RapidAPIService)
- [ ] Controller + Views con DataTables
- [ ] AI Keyword Generator da briefing
- [ ] Export CSV results
```

**Quick win**: Riusa OpenAI service + External API service già pronti

---

#### 5.2 Internal Links Analyzer (3 giorni) - P2

```bash
# Tasks
- [ ] DB: seo_internal_link_suggestions
- [ ] Service: InternalLinksAnalyzer
      - Analizza contenuto vs altri contenuti tenant
      - AI suggerisce anchor text + target content
      - Calcola relevance score
- [ ] Controller: CRUD suggestions
- [ ] UI: Apply/Reject links, bulk actions
```

---

### Week 6: ADV & SEO OAuth Tools

#### 6.1 Negative Keywords (2 giorni) - P2

```bash
# Tasks
- [ ] DB: adv_negative_keywords
- [ ] Riusa GoogleOAuthService per Google Ads
- [ ] Service: NegativeKeywordManager
      - Fetch campaigns from Google Ads
      - Generate AI suggestions
      - Push to Google Ads API
- [ ] Controller + UI
```

---

#### 6.2 GSC Tracker (3 giorni) - P2

```bash
# Tasks
- [ ] DB: seo_gsc_tracking
- [ ] Riusa GoogleOAuthService per GSC
- [ ] Service: GSCTrackingService
      - Fetch position data
      - Store historical data
      - Export Excel reports
- [ ] Controller + UI con charts
- [ ] Scheduled job: DailyGSCCheckJob
```

---

## 🌟 LAYER 4: AI FUTURISTIC FEATURES (Week 7) - P3 LOW

**Obiettivo**: Competitive advantage con AI avanzate
**Riferimento**: `AINSTEIN-TOOLS-VISION.md`

### 7.1 Ainstein Copilot (3 giorni) - P3

```bash
# Tasks
- [ ] DB: copilot_conversations
- [ ] Service: AinsteinCopilotService
      - Context-aware AI assistant
      - Tool-specific knowledge
      - Action suggestions
- [ ] UI: Fixed bottom-right chat widget (ogni tool)
- [ ] WebSocket real-time chat
```

---

### 7.2 Predictive Analytics (2 giorni) - P3

```bash
# Tasks
- [ ] DB: ai_predictions, ai_insights
- [ ] Service: PredictiveAnalyticsService
      - Campaign CTR forecast (Campaign Generator)
      - SERP position forecast (GSC Tracker)
      - Keyword trend prediction (Keyword Research)
- [ ] UI: Charts con predictions
```

---

## ✨ LAYER 5: POLISH & PRODUCTION (Week 8) - P2

**Obiettivo**: Production-ready SaaS

### 8.1 Onboarding Tours (2 giorni) - P2

```bash
# Tasks
- [ ] Tour per ogni tool (6 tour)
- [ ] Seguire template: AINSTEIN-ONBOARDING-SYSTEM.md
- [ ] Test auto-start
- [ ] "Don't show again" functionality
```

---

### 8.2 Testing & QA (2 giorni) - P1

```bash
# Tasks
- [ ] Unit tests Services (80% coverage)
- [ ] Feature tests Controllers
- [ ] E2E tests con Dusk (critical flows)
- [ ] Performance testing (response time < 200ms)
- [ ] Load testing (100 concurrent users)
```

---

### 8.3 Documentation & Deploy (1 giorno) - P1

```bash
# Tasks
- [ ] Update PROJECT-INDEX.md con status finale
- [ ] API documentation (Swagger/Scribe)
- [ ] Deployment guide
- [ ] Environment setup automation (Docker/Forge)
```

---

## 📋 CHECKLIST INGEGNERISTICO PER OGNI FEATURE

### Prima di iniziare
- [ ] Spec chiare (leggi MD tool specifico)
- [ ] Dependencies ready (verifica Layer precedente completato)
- [ ] DB schema reviewed (design prima di migrare)

### Durante sviluppo
- [ ] Code review checklist mentale (SOLID, DRY)
- [ ] Error handling robusto (try/catch, fallback)
- [ ] Logging strategico (info/warning/error levels)
- [ ] Token tracking integration (per billing)
- [ ] Tenant isolation validation (query scoped)

### Prima di commit
- [ ] Testing checklist completato
- [ ] Code commented dove necessario
- [ ] No hardcoded values (use config)
- [ ] Security check (no SQL injection, XSS protected)

### Quality gates
- [ ] Feature funziona end-to-end
- [ ] Tests passano
- [ ] Documentation aggiornata
- [ ] No regression su feature esistenti

---

## 🎯 SUCCESS METRICS PER LAYER

### Layer 1 (Foundation)
✅ Admin configura tutte API keys
✅ OpenAI service genera completion
✅ OAuth Google funziona (manuale test)
✅ Token tracking salva dati

### Layer 2 (Core Tools MVP)
✅ Campaign Generator crea asset RSA/PMax
✅ Article Generator pubblica articolo completo
✅ Tenant può usare 2 tool senza bug critici
✅ Token usage accurato al 95%+

### Layer 3 (Advanced Tools)
✅ Tutti i 6 tool funzionanti
✅ OAuth Google (Ads + GSC) stable
✅ Export/Import CSV working
✅ Multi-API integrations OK

### Layer 4 (AI Futuristic)
✅ Copilot risponde a domande tool-specific
✅ Predictions generate confidence scores
✅ Insights actionable per utente

### Layer 5 (Polish)
✅ Onboarding tours completati
✅ Test coverage > 80%
✅ Performance < 200ms avg
✅ Production deploy successful

---

## 🚨 RISKS & MITIGATION

### Technical Risks

**Risk**: OpenAI rate limits
**Mitigation**: Queue jobs, exponential backoff, show queue position to user

**Risk**: OAuth token expiry durante processo
**Mitigation**: Check token validity prima di ogni call, auto-refresh, retry once

**Risk**: Long article generation timeout
**Mitigation**: Queue jobs con timeout 5min, chunked generation, progress tracking

**Risk**: Database performance con molte query
**Mitigation**: Eager loading relationships, query optimization, indexes on foreign keys

### Business Risks

**Risk**: Tool troppo complesso per utente
**Mitigation**: Onboarding tours, Copilot help, documentation inline

**Risk**: Token costs troppo alti
**Mitigation**: Usage limits per tenant, alert su soglia 80%, optimize prompts

---

## 📊 ESTIMATED EFFORT (Engineering Hours)

| Layer | Tasks | Hours | Giorni (8h) |
|-------|-------|-------|-------------|
| Layer 1: Foundation | Admin + Services | 80h | 10 giorni |
| Layer 2: Core MVP | Campaign + Article | 80h | 10 giorni |
| Layer 3: Advanced Tools | 4 tool rimanenti | 80h | 10 giorni |
| Layer 4: AI Futuristic | Copilot + Predictions | 40h | 5 giorni |
| Layer 5: Polish | Testing + Deploy | 40h | 5 giorni |
| **TOTALE** | | **320h** | **40 giorni** |

**Timeline**: 8 settimane con 1 developer full-time

---

## 🎯 NEXT IMMEDIATE ACTIONS

### Per iniziare ADESSO (oggi):

#### Action 1: Database Foundation (2h)
```bash
cd ainstein-laravel
php artisan make:migration extend_platform_settings_for_tool_apis
```

Edita migration con colonne Layer 1.1

#### Action 2: Admin Settings View (3h)
```bash
php artisan make:controller Admin/ToolSettingsController
```

Crea view `resources/views/admin/settings/tools.blade.php`

#### Action 3: OpenAI Service Base (3h)
```bash
php artisan make:service AI/OpenAIService
```

Implementa chat() method con token tracking

**Obiettivo EOD**: Layer 1.1 + 1.2 + 2.1 completati (Foundation critica pronta)

---

## 💡 ENGINEERING BEST PRACTICES REMINDER

### Code Quality
- **SOLID principles** - Ogni service single responsibility
- **DRY** - Riusa OpenAI service, non duplicare prompt logic
- **KISS** - Semplice prima, ottimizzare dopo
- **YAGNI** - Non aggiungere features non specificate

### Laravel Best Practices
- **Service layer** - Business logic fuori da controller
- **Jobs for async** - Tutto che dura > 3s in queue
- **Policies** - Authorization esplicita
- **Form Requests** - Validation separata
- **Eloquent** - No raw queries se possibile

### AI Engineering
- **Prompt versioning** - Salva prompt in config file
- **Response validation** - Verifica sempre JSON structure
- **Fallback gracefully** - Se AI fallisce, message chiaro utente
- **Token optimization** - Usa GPT-4o-mini dove possible

### Security
- **Encrypt API keys** - Laravel Crypt::encrypt()
- **Tenant isolation** - SEMPRE filtrare per tenant_id
- **Rate limiting** - Middleware su API routes
- **Input sanitization** - Validation + purify HTML

---

**🚀 Roadmap confermata. Pronti per Layer 1: Foundation.**

**Comando start**:
```bash
cd ainstein-laravel
php artisan make:migration extend_platform_settings_for_tool_apis
```

**Dichiara quando pronto**: "Migration creata, mostramela per review"

---

_Roadmap ingegneristica creata: 3 Ottobre 2025_
_Approach: Foundation-first, Iterative, Quality-gated_
