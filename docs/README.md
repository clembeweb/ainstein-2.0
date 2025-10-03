# 📚 Ainstein Documentation Hub

**Organizzazione funzionale della documentazione per ripresa rapida del progetto**

---

## 🎯 QUICK START - "Riprendiamo il lavoro"

### Per riprendere il progetto in una nuova chat:

1. **Leggi prima**: `01-project-overview/PROJECT-INDEX.md` ⭐⭐⭐
   - Tempo: 5-10 minuti
   - Contiene: Status attuale, prossimi step, checklist ripresa

2. **Poi**: `02-tools-refactoring/TOOL-REFACTORING-PLAN.md`
   - Tempo: 3-5 minuti
   - Contiene: Piano implementazione 33 giorni, priorità

3. **Se necessario**: Tool specifico da `02-tools-refactoring/TOOL-*.md`
   - Tempo: 5 minuti per tool
   - Leggi solo il tool che stai implementando

**TOTALE TEMPO RIPRESA**: ~15 minuti massimo

---

## 📁 STRUTTURA DOCUMENTAZIONE

```
docs/
├── README.md (questo file)
│
├── 01-project-overview/          ⭐ Documenti di progetto
│   ├── DEVELOPMENT-ROADMAP.md         ⭐⭐⭐ Roadmap 8 settimane stratificata
│   ├── PROJECT-INDEX.md               ⭐⭐⭐ MASTER INDEX
│   ├── ADMIN-SETTINGS-CENTRALIZATION.md ⭐⭐⭐ P0 CRITICAL - Next task
│   ├── ARCHITECTURE.md                ⭐⭐ Enterprise architecture doc
│   ├── DEPLOYMENT-COMPATIBILITY.md    ⭐ Multi-hosting guide
│   ├── ADMIN-COST-ANALYTICS.md        OpenAI cost tracking feature
│   ├── BILLING-INTEGRATION-GUIDE.md   Sistema Stripe billing
│   └── BILLING-CONFIG-ADMIN.md        Config admin panel billing
│
├── 02-tools-refactoring/         🛠️ Tool refactoring WordPress → Laravel
│   ├── TOOL-REFACTORING-PLAN.md  ⭐⭐⭐ Piano completo 6 tool (33 giorni)
│   ├── REFACTORING-TOOL.md       Template generico refactoring
│   ├── AINSTEIN-TOOLS-VISION.md  ⭐⭐ Visione AI-first futuristica
│   │
│   ├── TOOL-ADV-CAMPAIGN-GENERATOR.md     Tool 1: Google Ads asset gen
│   ├── TOOL-ADV-NEGATIVE-KEYWORDS.md      Tool 2: Negative KW + OAuth
│   ├── TOOL-COPY-ARTICLE-GENERATOR.md ⭐  Tool 3: Pipeline articoli AI
│   ├── TOOL-SEO-INTERNAL-LINKS.md         Tool 4: AI link interni
│   ├── TOOL-SEO-GSC-TRACKER.md            Tool 5: GSC position tracking
│   └── TOOL-SEO-KEYWORD-RESEARCH.md       Tool 6: Multi-API keyword research
│
├── 03-design-system/             🎨 UI/UX & Onboarding
│   ├── AINSTEIN-UI-UX-DESIGN-SYSTEM.md ⭐ Design system completo
│   └── AINSTEIN-ONBOARDING-SYSTEM.md   ⭐ Sistema tour Shepherd.js
│
└── 04-archive/                   📦 Documenti storici
    ├── README.md                 Next.js obsoleto
    └── SITUAZIONE-ATTUALE.md     Storia migrazione Next.js → Laravel
```

---

## 📖 GUIDE RAPIDE PER SCENARIO

### Scenario 1: "Ho bisogno di una panoramica veloce"
1. `01-project-overview/DEVELOPMENT-ROADMAP.md` sezione "Roadmap Stratificato"
2. `01-project-overview/PROJECT-INDEX.md` sezione "Quick Start"
3. `.project-status` file in root (stato corrente)

**Tempo**: 5 minuti

---

### Scenario 2: "Devo implementare un nuovo tool"
1. `01-project-overview/PROJECT-INDEX.md` → verifica priorità
2. `02-tools-refactoring/REFACTORING-TOOL.md` → leggi template generico
3. `02-tools-refactoring/TOOL-[nome-tool].md` → spec dettagliate
4. `02-tools-refactoring/AINSTEIN-TOOLS-VISION.md` → features AI avanzate
5. `03-design-system/AINSTEIN-UI-UX-DESIGN-SYSTEM.md` → UI guidelines

**Tempo**: 15-20 minuti

---

### Scenario 3: "Devo creare la UI di un tool"
1. `03-design-system/AINSTEIN-UI-UX-DESIGN-SYSTEM.md` sezione 4-6
   - Color palette
   - Layout standard
   - Component library
2. `03-design-system/AINSTEIN-ONBOARDING-SYSTEM.md` → aggiungi tour

**Tempo**: 10 minuti

---

### Scenario 4: "Devo configurare billing/payment"
1. `01-project-overview/BILLING-INTEGRATION-GUIDE.md`
2. `01-project-overview/BILLING-CONFIG-ADMIN.md`

**Tempo**: 10 minuti

---

### Scenario 5: "Voglio capire la storia del progetto"
1. `04-archive/SITUAZIONE-ATTUALE.md` → Perché Laravel vs Next.js
2. `01-project-overview/PROJECT-INDEX.md` sezione "Database Overview"

**Tempo**: 5 minuti

---

## 🎯 PRIORITÀ DI LETTURA PER IMPLEMENTAZIONE

### Priority Level 1 - MUST READ (prima di codificare)
⭐⭐⭐ `01-project-overview/PROJECT-INDEX.md`
⭐⭐⭐ `02-tools-refactoring/TOOL-REFACTORING-PLAN.md`
⭐⭐⭐ Tool specifico da implementare in `02-tools-refactoring/`

### Priority Level 2 - SHOULD READ (durante implementazione)
⭐⭐ `02-tools-refactoring/AINSTEIN-TOOLS-VISION.md` → Per features avanzate
⭐⭐ `03-design-system/AINSTEIN-UI-UX-DESIGN-SYSTEM.md` → Per UI coerente
⭐⭐ `03-design-system/AINSTEIN-ONBOARDING-SYSTEM.md` → Per tour guidato

### Priority Level 3 - NICE TO READ (opzionale)
⭐ `02-tools-refactoring/REFACTORING-TOOL.md` → Se non chiaro pattern
⭐ `01-project-overview/BILLING-*.md` → Solo se lavori su billing
⭐ `04-archive/*` → Solo per contesto storico

---

## 📊 CONTENUTI CHIAVE PER FILE

### `PROJECT-INDEX.md` (Master Index)
- ✅ Status progetto attuale
- ✅ Piano implementazione Phase 1-5
- ✅ Database overview (33 tabelle)
- ✅ API keys necessarie
- ✅ Token usage estimates
- ✅ Checklist ripresa lavoro
- ✅ Troubleshooting quick reference

### `TOOL-REFACTORING-PLAN.md` (Piano 33 giorni)
- ✅ Analisi 6 tool WordPress esistenti
- ✅ API inventory completo
- ✅ Timeline implementazione (7 settimane)
- ✅ Priorità High/Medium/Low
- ✅ Database schema tutti i tool
- ✅ Services/Controllers structure
- ✅ Miglioramenti vs WordPress

### `AINSTEIN-TOOLS-VISION.md` (Visione AI-first)
- ✅ Features futuristiche per ogni tool:
  - Campaign Generator → Campaign Intelligence (multi-model ensemble)
  - Negative Keywords → Intent Shield (ML clustering)
  - Article Generator → Content Studio (brand voice cloning)
  - Internal Links → Link Intelligence (content graph)
  - GSC Tracker → SERP Intelligence (forecast AI)
  - Keyword Research → Keyword Intelligence (topic clusters)
- ✅ Cross-tool features: Copilot, Auto-pilot, Smart notifications
- ✅ Nuove tabelle globali: `ai_predictions`, `ai_insights`

### Tool-Specific Files (6 file)
- ✅ Descrizione funzionalità
- ✅ Database schema dettagliato
- ✅ Service layer code examples
- ✅ Controller methods
- ✅ UI wireframe/components
- ✅ Token usage estimates
- ✅ API integrations required

### `AINSTEIN-UI-UX-DESIGN-SYSTEM.md`
- ✅ Color palette (CSS variables)
- ✅ Typography system (Inter font)
- ✅ Layout standard tool page (template Blade)
- ✅ Component library completo
- ✅ Responsive breakpoints
- ✅ Accessibility WCAG 2.1 AA

### `AINSTEIN-ONBOARDING-SYSTEM.md`
- ✅ Database schema tracking completion
- ✅ Model methods (hasCompletedToolOnboarding, etc.)
- ✅ Controller endpoints REST API
- ✅ JavaScript template Shepherd.js
- ✅ Custom CSS theme
- ✅ Implementation checklist

---

## 🔍 SEARCH REFERENCE

### Cerchi informazioni su...

**API Keys / External Services**
→ `PROJECT-INDEX.md` sezione "API KEYS NECESSARIE"
→ `TOOL-REFACTORING-PLAN.md` sezione "Inventario API e Credenziali"

**Database Schema**
→ `PROJECT-INDEX.md` sezione "DATABASE OVERVIEW"
→ Tool specifico in `02-tools-refactoring/TOOL-*.md` sezione "Database Schema"

**Token Usage / Pricing**
→ `PROJECT-INDEX.md` sezione "TOKEN USAGE ESTIMATES"
→ Tool specifico sezione "Token Consumption"

**UI Components**
→ `AINSTEIN-UI-UX-DESIGN-SYSTEM.md` sezione 5 "Component Library"

**Onboarding Tour**
→ `AINSTEIN-ONBOARDING-SYSTEM.md` sezione "Template Tour"

**OAuth Google (Ads/GSC)**
→ `TOOL-REFACTORING-PLAN.md` sezione "OAuth Management"
→ Tool specifico (ADV-NEGATIVE-KEYWORDS o SEO-GSC-TRACKER)

**AI Features Avanzate**
→ `AINSTEIN-TOOLS-VISION.md` sezione tool specifico

**Timeline Implementazione**
→ `PROJECT-INDEX.md` sezione "PIANO IMPLEMENTAZIONE"
→ `TOOL-REFACTORING-PLAN.md` sezione "Stima Tempi"

---

## ✅ CHECKLIST NUOVA CHAT

Quando riprendi il lavoro in una nuova sessione:

### Step 1: Orientamento (5 min)
- [ ] Apri `PROJECT-INDEX.md`
- [ ] Leggi sezione "Status Attuale"
- [ ] Identifica "PROSSIMO STEP"

### Step 2: Piano (3 min)
- [ ] Apri `TOOL-REFACTORING-PLAN.md`
- [ ] Verifica fase corrente (Phase 1-6)
- [ ] Leggi task specifici della fase

### Step 3: Tool Focus (5 min) - Solo se implementi tool
- [ ] Apri `TOOL-[nome].md` del tool specifico
- [ ] Leggi sezioni: DB Schema, Service, Controller

### Step 4: Context Loading (2 min)
- [ ] Dichiara fase/tool all'AI
- [ ] Richiedi conferma comprensione context

### Step 5: Go! 🚀
- [ ] Inizia implementazione

**TOTALE**: ~15 minuti

---

## 🆘 TROUBLESHOOTING DOCS

### "Non trovo informazioni su X"

1. **Cerca in** `PROJECT-INDEX.md` sezione "SEARCH REFERENCE"
2. **Se non c'è**: Cerca in tool specifico in `02-tools-refactoring/`
3. **Se ancora non c'è**: Controlla `AINSTEIN-TOOLS-VISION.md` (features future)

### "Docs contraddittori"

**Priorità**:
1. `PROJECT-INDEX.md` (MASTER - sovrascrive tutto)
2. Tool specifico in `02-tools-refactoring/`
3. Design system in `03-design-system/`
4. File generici (`REFACTORING-TOOL.md`)

### "Troppa roba da leggere"

**Minimo vitale** (10 min):
1. `PROJECT-INDEX.md` sezioni: "Quick Start" + "Piano Implementazione"
2. Tool specifico sezioni: "Database Schema" + "Service"

**Poi implementi e consulti resto on-demand**

---

## 📝 MANUTENZIONE DOCS

### Quando aggiornare

**`PROJECT-INDEX.md`** → Quando:
- Cambia status progetto
- Completi una Phase
- Nuova priorità tool
- Nuovi database schema

**Tool-specific** → Quando:
- Modifichi DB schema del tool
- Aggiungi nuove features
- Cambi API integration

**Design system** → Quando:
- Nuovi component UI
- Cambio color palette
- Nuovi pattern layout

---

## 🎓 BEST PRACTICES

### Durante implementazione
✅ Consulta `PROJECT-INDEX.md` come "single source of truth"
✅ Segui design system per UI
✅ Usa template da `REFACTORING-TOOL.md`
✅ Aggiungi onboarding tour ogni tool

### Documentazione
✅ Aggiorna `PROJECT-INDEX.md` status quando completi Phase
✅ Commenta codice complesso
✅ Aggiungi esempi in tool-specific MD se implementi feature custom

---

**🚀 Pronto per riprendere il lavoro!**

**Comando rapido per iniziare**:
```bash
# Leggi master index
cat docs/01-project-overview/PROJECT-INDEX.md

# Poi dichiara fase/tool all'AI
"Ho letto il project index, procediamo con Phase [X] - Tool [Y]"
```

---

_Documentazione organizzata il 3 Ottobre 2025_
_Struttura ottimizzata per ripresa rapida progetto_
