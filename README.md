# 🚀 Ainstein Platform - Multi-Tenant AI SaaS

**Laravel 12.31.1** | **Multi-Tenancy** | **Token-based Billing** | **AI-First Tools**

---

## 📋 Quick Start

### 🚀 Comando Magico per Riprendere il Lavoro

**In una nuova chat, digita semplicemente**:

```
proseguiamo
```

**L'AI leggerà automaticamente la documentazione e proseguirà con lo sviluppo!**

---

### 📖 Per capire il progetto (prima volta)

**👉 INIZIA DA QUI**: [`START-HERE.md`](START-HERE.md)

Poi leggi:
- [`docs/01-project-overview/DEVELOPMENT-ROADMAP.md`](docs/01-project-overview/DEVELOPMENT-ROADMAP.md) - Roadmap ingegneristica 8 settimane
- [`docs/01-project-overview/PROJECT-INDEX.md`](docs/01-project-overview/PROJECT-INDEX.md) - Master index completo

---

## 📚 Documentazione Completa

Tutta la documentazione è organizzata in [`docs/`](docs/):

```
docs/
├── README.md                          📚 Hub documentazione
│
├── 01-project-overview/               🎯 Panoramica progetto
│   ├── PROJECT-INDEX.md              ⭐⭐⭐ MASTER INDEX - Start here!
│   ├── BILLING-INTEGRATION-GUIDE.md   Sistema billing Stripe
│   └── BILLING-CONFIG-ADMIN.md        Config admin panel
│
├── 02-tools-refactoring/              🛠️ Refactoring 6 tool AI
│   ├── TOOL-REFACTORING-PLAN.md      ⭐⭐⭐ Piano 33 giorni
│   ├── REFACTORING-TOOL.md            Template generico
│   ├── AINSTEIN-TOOLS-VISION.md      ⭐⭐ Visione AI-first
│   └── TOOL-*.md                      6 tool specifici
│
├── 03-design-system/                  🎨 UI/UX Guidelines
│   ├── AINSTEIN-UI-UX-DESIGN-SYSTEM.md   Design system completo
│   └── AINSTEIN-ONBOARDING-SYSTEM.md     Sistema tour Shepherd.js
│
└── 04-archive/                        📦 Documenti storici
    └── SITUAZIONE-ATTUALE.md          Storia Next.js → Laravel
```

**👉 Vai a**: [`docs/README.md`](docs/README.md) per navigazione completa

---

## 🎯 Stato Progetto

### ✅ Completato
- ✅ Piattaforma Laravel base (admin panel + tenant dashboard)
- ✅ Sistema multi-tenancy funzionante
- ✅ Autenticazione + autorizzazione (Policies)
- ✅ Token tracking per billing
- ✅ Onboarding system (Shepherd.js)
- ✅ **Documentazione completa 6 tool** (33 giorni implementazione)

### 🔨 In Corso
- ⏸️ **PROSSIMO**: Implementazione tool (Phase 1: Admin API Keys Setup)

### 📅 Roadmap
- **Phase 1** (Week 1-2): Admin API Keys + OpenAI Service base
- **Phase 2** (Week 3): Campaign Generator (ADV)
- **Phase 3** (Week 4-5): Article Generator (COPY)
- **Phase 4** (Week 6): SEO Tools (Keyword Research, Internal Links)
- **Phase 5** (Week 7): Advanced Tools (OAuth Google Ads/GSC)

---

## 🛠️ Tool da Implementare (6 totali)

### ADV (Advertising)
1. **Campaign Generator** - Google Ads asset (RSA/PMax) con AI
2. **Negative Keywords** - Gestione keyword negate + OAuth Google Ads

### COPY (Content)
3. **Article Generator** - Pipeline AI 6-step per articoli automatici

### SEO
4. **Internal Links** - Suggerimenti AI link interni
5. **GSC Tracker** - Google Search Console position tracking
6. **Keyword Research** - Multi-API keyword research + AI generator

---

## 🚀 Setup Locale

### Requisiti
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/SQLite
- Redis (opzionale, per queue)

### Installazione

```bash
# Clone repository
git clone https://github.com/[username]/ainstein-3.git
cd ainstein-3/ainstein-laravel

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed

# Build assets
npm run build

# Serve
php artisan serve
```

### Credenziali Demo
```
Super Admin: admin@ainstein.com / password
Demo Tenant: admin@demo.com / demo123
```

---

## 📖 Lettura Rapida (15 min)

### Per riprendere il progetto in nuova chat:

1. **`docs/01-project-overview/PROJECT-INDEX.md`** (10 min)
   - Status attuale + prossimi step
   - Checklist ripresa lavoro

2. **`docs/02-tools-refactoring/TOOL-REFACTORING-PLAN.md`** (5 min)
   - Piano implementazione 33 giorni
   - Priorità tool

**Poi sei pronto per implementare!** 🚀

---

## 🔑 API Keys Necessarie

### Obbligatorie (MVP)
- ✅ **OpenAI API Key** - Già in `.env` → `OPENAI_API_KEY`
- 🔶 **Google Ads OAuth** - Client ID/Secret (da configurare in admin)
- 🔶 **Google Search Console OAuth** - JSON credentials

### Opzionali (Enhancement)
- 🔶 **SerpAPI** - SERP analysis
- 🔶 **RapidAPI** - Keyword research multi-API

---

## 📊 Tech Stack

### Backend
- **Laravel 12.31.1** - Framework PHP
- **MySQL** - Database principale
- **Redis** - Cache + Queue (opzionale)
- **Spatie Multi-Tenancy** - Isolamento tenant

### Frontend
- **Blade Templates** - Server-side rendering
- **Tailwind CSS** - Utility-first CSS
- **Alpine.js** - Reactivity leggera
- **Shepherd.js** - Onboarding tours

### AI & External APIs
- **OpenAI API** - GPT-4o, GPT-4o-mini, DALL-E 3
- **Google Ads API** - Negative keywords management
- **Google Search Console API** - Position tracking
- **RapidAPI** - SEMrush, SEO tools

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=OpenAIServiceTest
php artisan test --filter=CampaignGeneratorTest

# Feature tests
php artisan test --testsuite=Feature
```

---

## 🆘 Troubleshooting

### OpenAI API errors
→ Check `.env` → `OPENAI_API_KEY` presente
→ Logs: `storage/logs/laravel.log`

### Queue jobs non partono
→ `php artisan queue:work` attivo?
→ Redis connesso?

### DataTables non carica
→ JavaScript console errors
→ API endpoint ritorna JSON?

**Guida completa**: `docs/01-project-overview/PROJECT-INDEX.md` sezione "Troubleshooting"

---

## 📝 Contribuire

### Aggiungere nuovo tool

1. Leggi template: `docs/02-tools-refactoring/REFACTORING-TOOL.md`
2. Crea MD specifico in `docs/02-tools-refactoring/TOOL-[nome].md`
3. Segui design system: `docs/03-design-system/AINSTEIN-UI-UX-DESIGN-SYSTEM.md`
4. Aggiungi onboarding tour: `docs/03-design-system/AINSTEIN-ONBOARDING-SYSTEM.md`

---

## 📞 Links

- **Documentazione Completa**: [`docs/`](docs/)
- **Master Index**: [`docs/01-project-overview/PROJECT-INDEX.md`](docs/01-project-overview/PROJECT-INDEX.md)
- **OpenAI Platform**: https://platform.openai.com/
- **Google Cloud Console**: https://console.cloud.google.com/

---

## 📄 License

Proprietario - Ainstein Platform © 2025

---

**🚀 Ready to build AI-first SaaS tools!**

**Prossimo step**: Leggi [`docs/01-project-overview/PROJECT-INDEX.md`](docs/01-project-overview/PROJECT-INDEX.md)

---

_Last update: 3 Ottobre 2025_
