# AI Article Steps — Specifiche Funzionali
Sviluppo su piattaforma Laravel

---

## 1. Overview
Il sistema consente la generazione automatica di articoli tramite AI e la gestione degli step di ottimizzazione SEO associati, strutturata su workflow orientato a risultati e multi-tenant.

---

## 2. Funzionalità Core

### 2.1 Keyword Management
- Inserimento manuale delle main keyword (textarea, una per riga)
- Salvataggio su tabella `keywords` associata all’utente/tenant
- Modifica/eliminazione

### 2.2 Article Generation
- Selezione keyword, template prompt, parametri (tono, wordcount, istruzioni extra)
- Invio richiesta AI (OpenAI API/Service Layer)
- Stato: pending | completed | failed

### 2.3 Gestione SEO Steps & Internal Links
- Generazione automatica/manuale di step SEO (titoli, meta, H, struttura)
- Proposta automatica di link interni (articoli/pagine della piattaforma)
- Editing step, salvataggio storico

### 2.4 Dashboard & Storico
- Elenco articoli generati, step associati, stato generazione, token consumati, costo stimato
- Filtri, export CSV/PDF
- AB test su varianti contenuto generato

### 2.5 Multi-Tenancy & Permission
- Isolamento dati per tenant
- Audit trail azioni, permission granulari
- Rate limiting, tracking consumo token

### 2.6 Onboarding & Best Practices
- Tour guidato step-by-step
- Sezione “Best Practices” consultabile

---

## 3. Process Workflow
1. Inserimento Keyword
2. Scelta Prompt / Parametri
3. Generazione Articolo via AI
4. Visualizzazione Step SEO & Link Interni
5. Revisione, scelta variante (AB Test), pubblicazione
6. Export (CSV/PDF)

---

## 4. Architettura Laravel
**Modelli principali:**
- Keyword
- ArticleGeneration
- SEOstep
- InternalLink
- PromptTemplate
- Tenant, User

**Service Layer:**
- OpenAIService
- TokenTrackingService
- CMSIntegrationService

**Controller principali:**
- TenantContentController
- TenantPromptController
- TenantPageController

**Frontend:**
- Interfaccia moderna con tab
- Onboarding tour
- Export/analytics dashboard

---
## 5. Roadmap
- Batch generation keyoword
- Scheduling contenuti
- Multi-language
- White-label subtenant/branding
- Public API documentata

---

## 6. Sicurezza
- CSRF/XSS protezione
- Password hashing
- Logging, rate limiting
- Isolamento tenant

---

## 7. Estensione – Modello dati esempio (tabelle principali)

### **keywords**
- id
- tenant_id
- keyword
- created_at

### **articles**
- id
- tenant_id
- keyword_id
- content
- status (pending | completed | failed)
- generated_at
- tokens_used
- prompt_id

### **seo_steps**
- id
- article_id
- step_title
- step_description

### **internal_links**
- id
- article_id
- url

### **prompt_templates**
- id
- tenant_id
- name
- template_text

---

## 8. Best practices UX (da implementare)
- Onboarding guidato con Shepherd.js, tab interattivi per step e stato lavorazione
- Validazioni avanzate sui form di inserimento keyword e prompt
- Autocompletamento e suggerimento KW correlate e link interni via AI
- Esportazione selettiva risultati e step

---