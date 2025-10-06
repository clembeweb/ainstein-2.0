# 🚀 AINSTEIN PROJECT - Master Index

**Ultimo aggiornamento**: 2025-10-06
**Stato progetto**: ✅ Layer 2 Complete (100% tested) → Production Ready
**Platform**: Laravel 12.31.1 Multi-Tenant SaaS + Token-based Billing

---

## 📋 QUICK START - "Riprendiamo il lavoro"

### Status Attuale (2025-10-06)
✅ **Layer 1**: Foundation complete (Admin settings + Database)
✅ **Layer 2.1**: OpenAI Service Base complete (Real API tested)
✅ **Layer 2.2**: Content Generator Unified complete (3-tab interface)
✅ **Layer 2.3**: Admin Settings Sync complete (All settings reflecting)
✅ **Test Coverage**: 46/46 tests passing (100%)
✅ **Production Ready**: YES - All core features working
⏸️ **NEXT STEP**: Layer 3.1 - Campaign Generator implementation

### File da Leggere per Ripresa Rapida (in ordine)
1. **Questo file** (`PROJECT-INDEX.md`) - Panoramica generale
2. `PROJECT-STATUS-2025-10-06.md` - Status completo con test results
3. `DEVELOPMENT-ROADMAP.md` - Roadmap aggiornata (Layer 2 ✅)
4. `TOOL-ADV-CAMPAIGN-GENERATOR.md` - Next tool to implement

---

## 📁 STRUTTURA DOCUMENTAZIONE

### 🎯 Core Documentation

#### 1. Project Overview
- **`README.md`** - Next.js originale (obsoleto, ignorare)
- **`SITUAZIONE-ATTUALE.md`** - Storia migrazione Next.js → Laravel (background)
- **`PROJECT-INDEX.md`** ⭐ - **QUESTO FILE** (master index)

#### 2. Billing System (Implementato ✅)
- **`BILLING-INTEGRATION-GUIDE.md`** - Guida completa integrazione Stripe
- **`BILLING-CONFIG-ADMIN.md`** - Configurazione admin panel billing

---

### 🛠️ Tool Refactoring Documentation

#### Generic Guidelines
- **`REFACTORING-TOOL.md`** ⭐ - Template generico refactoring WordPress → Laravel
  - Quando usarlo: Per ogni nuovo tool da refactorare
  - Contiene: DB schema patterns, Service layer, Controllers, Routes, Policy

#### Master Plan
- **`TOOL-REFACTORING-PLAN.md`** ⭐⭐⭐ - **Piano completo 6 tool**
  - Timeline: 33 giorni (7 settimane)
  - Priorità implementazione (Phase 1-6)
  - API inventory completo
  - Checklist tasks

---

### 📦 Tool-Specific Documentation (6 Tool)

#### ADV (Advertising) - 2 Tool
1. **`TOOL-ADV-CAMPAIGN-GENERATOR.md`**
   - Google Ads asset generator (RSA/PMax)
   - DB: `adv_campaigns`, `adv_generated_assets`
   - API: OpenAI (GPT-4o)
   - Token usage: 500-1200 per campagna
   - Priority: 🔥 **High** (Phase 1)

2. **`TOOL-ADV-NEGATIVE-KEYWORDS.md`**
   - Google Ads negative keywords con OAuth
   - DB: `adv_negative_keywords`, `google_ads_connections`
   - API: Google Ads API (OAuth 2.0)
   - Priority: 🟡 Medium (Phase 2)

#### COPY (Content Generation) - 1 Tool
3. **`TOOL-COPY-ARTICLE-GENERATOR.md`** ⭐
   - Pipeline articoli AI (6 step) - **RIVISTO: 100% AI interno**
   - DB: `copy_article_process`, `copy_article_steps`, `copy_article_archive`
   - API: Solo OpenAI + DALL-E (no scraping API esterni)
   - Token usage: ~9500 per articolo (~$0.10)
   - Features: AI SERP Simulation, AI Content Research, Auto-publish
   - Priority: 🔥 **High** (Phase 1)

#### SEO - 3 Tool
4. **`TOOL-SEO-INTERNAL-LINKS.md`**
   - Suggerimenti AI link interni
   - DB: `seo_internal_link_suggestions`
   - API: OpenAI (GPT-4o-mini)
   - Token usage: 500-1500 per analisi
   - Priority: 🟡 Medium (Phase 2)

5. **`TOOL-SEO-GSC-TRACKER.md`**
   - Google Search Console position tracking
   - DB: `seo_gsc_tracking`, `google_search_console_connections`
   - API: Google Search Console API (OAuth 2.0)
   - Token usage: 0 (no AI)
   - Priority: 🔵 Low (Phase 3)

6. **`TOOL-SEO-KEYWORD-RESEARCH.md`**
   - Multi-API keyword research
   - DB: `seo_keyword_research`, `seo_keyword_campaigns`
   - API: RapidAPI (SEMrush, SEO Keyword, AI Google)
   - Token usage: 300-800 per AI generation
   - Priority: 🟡 Medium (Phase 2)

---

### 🌟 Advanced Features Documentation

#### AI-First Vision
- **`AINSTEIN-TOOLS-VISION.md`** ⭐⭐⭐ - **Visione futuristica**
  - Quando leggerlo: Prima di implementare qualsiasi tool
  - Contiene: Features rivoluzionarie AI per ogni tool
  - Esempi: Multi-model ensemble, Predictive analytics, Auto-pilot mode
  - New tables: `ai_predictions`, `ai_insights`, `copilot_conversations`

#### Design System
- **`AINSTEIN-UI-UX-DESIGN-SYSTEM.md`** ⭐⭐
  - Quando usarlo: Per ogni pagina/component UI
  - Contiene:
    - Color palette (Indigo primary, semantic colors)
    - Typography system (Inter font)
    - Standard tool page layout template
    - Component library (buttons, badges, cards, forms)
    - Responsive guidelines

#### Onboarding System
- **`AINSTEIN-ONBOARDING-SYSTEM.md`** ⭐⭐
  - Quando usarlo: Per aggiungere tour guidato a nuovo tool
  - Contiene:
    - Database schema tracking
    - Controller endpoints
    - JavaScript template (Shepherd.js)
    - Custom CSS theme
    - Step-by-step implementation guide

---

## 🗺️ PIANO IMPLEMENTAZIONE CONSIGLIATO

### Phase 1: AI Core & Admin (Week 1-2) - **START HERE** 🎯

#### Setup Admin API Keys (5 giorni)
- [ ] Estendere tabella `admin_settings` con colonne tool API
- [ ] Admin UI `settings/tools.blade.php` (tutti i form API keys)
- [ ] OAuth flow Google Ads (client ID/secret + callback)
- [ ] OAuth flow Google Search Console (JSON upload + callback)
- [ ] Testing configurazione API keys

**Deliverable**: Admin può configurare tutte le API keys da dashboard

#### OpenAI Service Base (3 giorni)
- [ ] Service `app/Services/OpenAIService.php` base
- [ ] Token tracking integration
- [ ] Error handling + retry logic
- [ ] Testing con modelli GPT-4o, GPT-4o-mini

**Deliverable**: OpenAI service funzionante e testato

---

### Phase 2: First Tool - Campaign Generator (Week 3) - **Quick Win** 🚀

**Perché iniziare da qui**: Tool più semplice, spec completa, nessun OAuth richiesto

- [ ] Migration `adv_campaigns` + `adv_generated_assets`
- [ ] Models: `AdvCampaign`, `AdvGeneratedAsset`
- [ ] Service: `CampaignAssetsGenerator` (GPT-4o prompt engineering)
- [ ] Controller: CRUD + generate() method
- [ ] View: DataTables + form creazione + export CSV/JSON
- [ ] Testing: Generazione RSA/PMax funzionante

**Deliverable**: Tool Campaign Generator 100% funzionante

---

### Phase 3: Article Generator Core (Week 4-5) - **Most Complex** 🧠

**Nota**: Seguire TOOL-COPY-ARTICLE-GENERATOR.md (versione RIVISTA - no API esterne)

#### Pipeline 6-Step Implementation
- [ ] Migration tabelle: `copy_article_process`, `copy_article_steps`, `copy_article_archive`
- [ ] Service: `AIResearchService` (Step 1-2: AI SERP Simulation + Content Research)
- [ ] Service: `ArticleGeneratorService` (Step 3-4: Generation + SEO)
- [ ] Service: `DALLEService` (Step 5: Featured image)
- [ ] Job: `PublishArticleJob` (Step 6: Auto-publish)
- [ ] Controller: ArticleGeneratorController (CRUD + run steps)
- [ ] View: Tabella processi + modal step data (TinyMCE preview)

**Deliverable**: Pipeline articoli funzionante end-to-end

---

### Phase 4: SEO Tools (Week 6) - **Support Tools** 🔍

#### Keyword Research (Priority 🟡)
- [ ] Migration `seo_keyword_research`, `seo_keyword_campaigns`
- [ ] Service: `RapidAPIService` (3 endpoints)
- [ ] AI Keyword Generator da briefing
- [ ] Controller + View DataTables

#### Internal Links (Priority 🟡)
- [ ] Migration `seo_internal_link_suggestions`
- [ ] Service: `InternalLinksAnalyzer` (AI suggestions)
- [ ] Apply/Reject links functionality

**Deliverable**: 2 tool SEO funzionanti

---

### Phase 5: Advanced Tools (Week 7) - **Polish** ✨

#### Negative Keywords (OAuth Google Ads)
- [ ] OAuth flow completo
- [ ] Sync con Google Ads campaigns

#### GSC Tracker (OAuth GSC)
- [ ] OAuth flow completo
- [ ] Import posizioni + export Excel

**Deliverable**: Tool con OAuth funzionanti

---

## 🔑 API KEYS NECESSARIE

### Obbligatorie (MVP)
- ✅ **OpenAI API Key** - Già configurata (`OPENAI_API_KEY` in .env)
- 🔶 **Google Ads OAuth** - Client ID + Secret (console.cloud.google.com)
- 🔶 **Google Search Console OAuth** - JSON credentials

### Opzionali (Enhancement)
- 🔶 **SerpAPI Key** - Per SERP analysis (se non usi AI simulation)
- 🔶 **RapidAPI Key** - Per keyword research multi-API

---

## 🗃️ DATABASE OVERVIEW

### Tabelle Esistenti (Platform)
```
tenants
users
contents (pagine)
content_generations
prompts
platform_settings
user_usage_stats
```

### Tabelle Tool da Creare (33 totali)

#### ADV (4 tabelle)
- `adv_campaigns`
- `adv_generated_assets`
- `adv_negative_keywords`
- `google_ads_connections`

#### COPY (4 tabelle)
- `copy_article_process`
- `copy_article_steps`
- `copy_article_archive`
- `copy_custom_prompts`

#### SEO (6 tabelle)
- `seo_internal_link_suggestions`
- `seo_gsc_tracking`
- `google_search_console_connections`
- `seo_keyword_research`
- `seo_keyword_campaigns`

#### AI Advanced (3 tabelle - Optional Phase)
- `ai_predictions`
- `ai_insights`
- `copilot_conversations`

---

## 🎨 UI/UX STANDARDS

### Layout Tool Standardizzato
Ogni tool page deve avere:
1. **Header** - Titolo + descrizione + stats cards
2. **Controls Bar** - Filters, search, actions
3. **Data Table** - DataTables con pagination
4. **Modals** - Create/Edit forms

### Component Library (Tailwind)
- Buttons: `.btn-primary`, `.btn-secondary`, `.btn-ai-gradient`
- Badges: `.badge-success`, `.badge-warning`, `.badge-error`
- Cards: `.card`, `.stat-card`
- Forms: Standard form classes con validazione

**Riferimento**: `AINSTEIN-UI-UX-DESIGN-SYSTEM.md` sezioni 4-6

---

## 🧪 TESTING STRATEGY

### Unit Tests (Services)
```bash
php artisan test --filter=OpenAIServiceTest
php artisan test --filter=CampaignAssetsGeneratorTest
php artisan test --filter=ArticleGeneratorTest
```

### Feature Tests (Controllers)
```bash
php artisan test --filter=CampaignGeneratorControllerTest
php artisan test --filter=ArticleGeneratorControllerTest
```

### OAuth Testing
- Google Ads OAuth flow
- Google Search Console OAuth flow
- Token refresh automatico

---

## 📊 TOKEN USAGE ESTIMATES

| Tool | Token/Operation | Costo/Op | Note |
|------|-----------------|----------|------|
| Campaign Generator | 500-1200 | $0.005-0.012 | RSA 15+5 titoli + 4 desc |
| Article Generator | ~9500 | ~$0.10 | Pipeline 6-step completa |
| Internal Links | 500-1500 | $0.005-0.015 | Per analisi contenuto |
| Keyword Research (AI) | 300-800 | $0.003-0.008 | AI generation da briefing |
| Negative Keywords | 300-600 | $0.003-0.006 | AI suggestions |
| GSC Tracker | 0 | $0 | No AI, solo API Google |

**Totale estimate**: ~$0.13 per utilizzo completo tutti i tool

---

## 🚀 DEPLOYMENT CHECKLIST

### Environment Setup
- [ ] `.env` production con tutte le API keys
- [ ] Queue workers attivi (Supervisor)
- [ ] Redis cache configurato
- [ ] Cron jobs per batch processing

### Monitoring
- [ ] Sentry error tracking
- [ ] Laravel Telescope (dev)
- [ ] Token usage dashboard
- [ ] API rate limiting configurato

---

## 🎯 SUCCESS CRITERIA

### MVP Ready When
✅ Admin può configurare API keys da dashboard
✅ Campaign Generator genera asset RSA/PMax
✅ Article Generator pubblica articoli automaticamente
✅ Keyword Research multi-API funziona
✅ Internal Links suggerisce link AI
✅ Token tracking accurato per billing
✅ Onboarding tour funzionante per ogni tool

---

## 📝 SVILUPPO FUTURO (Post-MVP)

### AI Advanced Features (da AINSTEIN-TOOLS-VISION.md)
- [ ] Ainstein Copilot (AI assistant globale)
- [ ] Multi-model ensemble (GPT-4o + Claude + Gemini)
- [ ] Predictive analytics (forecast CTR, posizioni SERP)
- [ ] Auto-pilot mode per ogni tool
- [ ] Cross-tool intelligence

### Nuovi Tool
- [ ] AI Image Generator (DALL-E 3)
- [ ] Social Media Content Generator
- [ ] Email Marketing AI
- [ ] Competitor Analysis AI

---

## 🆘 TROUBLESHOOTING QUICK REFERENCE

### Se qualcosa non funziona

**OpenAI API errors**
- Check: `.env` → `OPENAI_API_KEY` presente
- Check: Admin settings → API key salvata
- Logs: `storage/logs/laravel.log`

**OAuth Google fallisce**
- Verifica redirect URI in Google Console
- Check refresh token salvato in DB
- Test token refresh manualmente

**Queue jobs non partono**
- `php artisan queue:work` attivo?
- Redis connesso?
- Check `failed_jobs` table

**DataTables non carica**
- JavaScript console errors?
- API endpoint ritorna JSON corretto?
- CSRF token presente?

---

## 📞 CONTATTI & RISORSE

### Repository
- **GitHub**: https://github.com/clembeweb/ainstein-2.0.git (Next.js - reference)
- **Current**: `C:\laragon\www\ainstein-3`

### Credentials Demo
```
Super Admin: admin@ainstein.com / password
Demo Tenant: admin@demo.com / demo123
```

### External APIs
- OpenAI: https://platform.openai.com/api-keys
- Google Cloud Console: https://console.cloud.google.com/
- SerpAPI: https://serpapi.com/
- RapidAPI: https://rapidapi.com/

---

## ✅ CHECKLIST RIPRESA LAVORO

Quando riprendi in nuova chat, segui questo flow:

1. **Lettura rapida** (5 min)
   - [ ] Leggi questo file (`PROJECT-INDEX.md`)
   - [ ] Verifica sezione "Status Attuale"

2. **Contestualizzazione** (3 min)
   - [ ] Leggi `TOOL-REFACTORING-PLAN.md` sezione "Piano Implementazione"
   - [ ] Identifica fase corrente (Phase 1-6)

3. **Tool-specific focus** (2 min)
   - [ ] Leggi MD del tool specifico da implementare
   - [ ] Verifica sezione DB schema + Service

4. **AI Vision** (opzionale, 3 min)
   - [ ] Leggi `AINSTEIN-TOOLS-VISION.md` per ispirazione features avanzate

5. **Go!** 🚀
   - [ ] Dichiari: "Ho letto il project index, procediamo con Phase X - Tool Y"

**Tempo totale ripresa**: ~10-15 minuti massimo

---

## 🎓 BEST PRACTICES REMINDER

### Durante implementazione
- ✅ Seguire design system `AINSTEIN-UI-UX-DESIGN-SYSTEM.md`
- ✅ Usare template da `REFACTORING-TOOL.md`
- ✅ Aggiungere onboarding tour (vedi `AINSTEIN-ONBOARDING-SYSTEM.md`)
- ✅ Token tracking sempre attivo
- ✅ Multi-tenancy isolation (`tenant_id` in tutte le query)

### Testing continuo
- ✅ Test ogni service dopo creazione
- ✅ Test OAuth flow manualmente prima di integrare
- ✅ Verifica token usage accurato

### Documentation
- ✅ Aggiornare questo file quando cambia priorità
- ✅ Commentare codice complesso (AI prompts, OAuth flow)

---

**🚀 Ready to code! Il prossimo step è Phase 1: Admin API Keys Setup.**

**Comando per iniziare**:
```bash
cd ainstein-laravel
php artisan make:migration extend_platform_settings_for_tool_apis
```

---

_Ultimo aggiornamento: 3 Ottobre 2025 - Documentazione completa ✅_
