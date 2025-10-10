# Campaign Generator - Browser Testing Plan

**Data:** 2025-10-10
**Feature:** Campaign Generator (Google Ads RSA & PMAX)
**Status:** Implementazione completa - Ready for Manual Testing

---

## Prerequisiti

1. ✅ Server locale attivo: `php artisan serve`
2. ✅ Database migrato: `php artisan migrate:fresh --seed` (se necessario)
3. ✅ User autenticato con tenant valido
4. ✅ Cache pulite: `php artisan config:clear && php artisan view:clear && php artisan route:clear`

---

## Test Scenarios (29 casi)

### 1. AUTHENTICATION & NAVIGATION (3 tests)

#### Test 1.1: Accesso alla lista campaigns
- **URL:** `/dashboard/campaigns`
- **Azione:** Clicca "Campaigns" nel menu laterale
- **Risultato atteso:**
  - ✅ Pagina carica correttamente
  - ✅ Header "Campaign Generator" visibile
  - ✅ Pulsante "Nuova Campaign" presente
  - ✅ Layout usa `layouts.tenant` (sidebar + header)
  - ✅ Colori amber nel design

#### Test 1.2: Redirect se non autenticato
- **Azione:** Logout, poi prova ad accedere a `/dashboard/campaigns`
- **Risultato atteso:**
  - ✅ Redirect a pagina login
  - ✅ Nessun accesso senza autenticazione

#### Test 1.3: Tenant isolation
- **Azione:** Login come User1 (Tenant A), poi come User2 (Tenant B)
- **Risultato atteso:**
  - ✅ User1 vede solo campaigns di Tenant A
  - ✅ User2 vede solo campaigns di Tenant B
  - ✅ Nessuna cross-tenant data leak

---

### 2. CREATE CAMPAIGN (6 tests)

#### Test 2.1: Form creazione visibile
- **URL:** `/dashboard/campaigns/create`
- **Azione:** Clicca "Nuova Campaign"
- **Risultato atteso:**
  - ✅ Form con campi: Nome, Tipo, Descrizione, Keywords, URL
  - ✅ Select "Tipo Campaign" con opzioni RSA e PMAX
  - ✅ Placeholder italiani
  - ✅ Info dinamiche per RSA/PMAX (Alpine.js)

#### Test 2.2: Creare campaign RSA
- **Azione:** Compila form:
  - Nome: "Test RSA Campaign"
  - Tipo: RSA
  - Descrizione: "Vendita orologi di lusso svizzeri"
  - Keywords: "orologi svizzeri, lusso, artigianali"
  - URL: https://example.com
- **Click:** "Genera Campaign"
- **Risultato atteso:**
  - ✅ Loading state visibile (spinner "Generazione in corso...")
  - ✅ Redirect a pagina dettaglio campaign
  - ✅ Flash message "Campaign created successfully"
  - ✅ Asset generati visibili (titoli 3-15, descrizioni 2-4)

#### Test 2.3: Creare campaign PMAX
- **Azione:** Stesso processo, seleziona PMAX
- **Risultato atteso:**
  - ✅ Asset PMAX generati (short titles, long titles, descriptions)
  - ✅ Visualizzazione corretta con 3 sezioni separate

#### Test 2.4: Validation errors
- **Azione:** Invia form vuoto
- **Risultato atteso:**
  - ✅ Errori di validazione mostrati sotto ogni campo
  - ✅ Messaggi in rosso
  - ✅ Form non si invia

#### Test 2.5: Keywords preview (Alpine.js)
- **Azione:** Digita keywords separate da virgole
- **Risultato atteso:**
  - ✅ Chips colorati appaiono real-time sotto il campo
  - ✅ Icone tag visibili
  - ✅ Colore amber per i chips

#### Test 2.6: Campaign type info dinamica
- **Azione:** Cambia select tra RSA e PMAX
- **Risultato atteso:**
  - ✅ Info box cambia colore (blu per RSA, verde per PMAX)
  - ✅ Testo spiega limiti corretti (30 char per RSA titles, etc.)

---

### 3. READ/VIEW CAMPAIGNS (4 tests)

#### Test 3.1: Lista campaigns
- **URL:** `/dashboard/campaigns`
- **Azione:** Visualizza lista dopo aver creato 2-3 campaigns
- **Risultato atteso:**
  - ✅ Tabella mostra: Nome, Tipo, Asset count, Tokens, Data
  - ✅ Badge colorati per tipo (blu RSA, verde PMAX)
  - ✅ Keywords chips visibili (max 3)
  - ✅ Pulsanti "Vedi" e "Elimina"

#### Test 3.2: Dettaglio campaign - Tab Assets
- **URL:** `/dashboard/campaigns/{id}`
- **Azione:** Clicca "Vedi" su una campaign
- **Risultato atteso:**
  - ✅ Tabs funzionanti (Alpine.js): "Asset Generati" e "Dettagli Campaign"
  - ✅ Tab Assets attivo di default
  - ✅ Sezioni colorate per Titles (blu) e Descriptions (verde)
  - ✅ Character counter visibile (es. "28/30 caratteri")
  - ✅ Pulsante copy-to-clipboard su ogni asset
  - ✅ Icona cambia a checkmark quando copiato
  - ✅ Toast verde "Copiato negli appunti!" appare

#### Test 3.3: Dettaglio campaign - Tab Dettagli
- **Azione:** Clicca tab "Dettagli Campaign"
- **Risultato atteso:**
  - ✅ Mostra: Nome, Tipo, Descrizione, URL, Lingua, Date
  - ✅ URL è cliccabile con icona external-link
  - ✅ Badge colorato per tipo campaign

#### Test 3.4: Stats cards
- **Azione:** Osserva le 3 card statistiche in alto
- **Risultato atteso:**
  - ✅ "Asset Generati" con gradiente verde
  - ✅ "Token Utilizzati" con gradiente amber
  - ✅ "Data Creazione" con gradiente blu
  - ✅ Icone FontAwesome corrette

---

### 4. UPDATE CAMPAIGN (3 tests)

#### Test 4.1: Form edit visibile
- **URL:** `/dashboard/campaigns/{id}/edit`
- **Azione:** Clicca "Modifica" dalla pagina dettaglio
- **Risultato atteso:**
  - ✅ Form pre-popolato con dati esistenti
  - ✅ Tipo campaign è READ-ONLY con badge
  - ✅ Message "Il tipo di campaign non può essere modificato"
  - ✅ Keywords preview funzionante

#### Test 4.2: Aggiornare campaign
- **Azione:** Modifica nome e keywords, salva
- **Risultato atteso:**
  - ✅ Redirect a pagina dettaglio
  - ✅ Flash message "Campaign aggiornata con successo!"
  - ✅ Modifiche salvate nel database
  - ✅ Asset NON rigenerati automaticamente

#### Test 4.3: Info box modifica
- **Azione:** Leggi info box blu in fondo
- **Risultato atteso:**
  - ✅ Spiega che asset non vengono rigenerati
  - ✅ Suggerisce uso pulsante "Rigenera Asset"

---

### 5. REGENERATE ASSETS (2 tests)

#### Test 5.1: Modal conferma rigenerazione
- **URL:** `/dashboard/campaigns/{id}`
- **Azione:** Clicca "Rigenera Asset"
- **Risultato atteso:**
  - ✅ Modal overlay appare con sfondo scuro
  - ✅ Icona warning amber visibile
  - ✅ Lista 3 conseguenze:
    - Eliminerà tutti gli asset attuali
    - Consumerà nuovi token AI
    - Creerà nuovi asset
  - ✅ Pulsanti "Annulla" e "Rigenera"

#### Test 5.2: Eseguire rigenerazione
- **Azione:** Clicca "Rigenera" nel modal
- **Risultato atteso:**
  - ✅ Modal si chiude
  - ✅ Page reload
  - ✅ Flash message "Assets rigenerati con successo!"
  - ✅ Vecchi asset eliminati dal database
  - ✅ Nuovi asset generati e visibili
  - ✅ Token counter aggiornato

---

### 6. EXPORT ASSETS (4 tests)

#### Test 6.1: Dropdown export visibile
- **Azione:** Clicca pulsante "Esporta" (verde)
- **Risultato atteso:**
  - ✅ Dropdown menu appare
  - ✅ 2 opzioni:
    - "Esporta CSV" con icona CSV
    - "Google Ads CSV" con icona Google
  - ✅ Descrizioni sotto ogni opzione

#### Test 6.2: Export CSV
- **Azione:** Clicca "Esporta CSV"
- **Risultato atteso:**
  - ✅ File `campaign_{id}_2025-10-10.csv` scaricato
  - ✅ Apri file: contiene header "Type, Content, Character Count"
  - ✅ Rows: Title, [testo], [lunghezza]
  - ✅ Encoding UTF-8 corretto

#### Test 6.3: Export Google Ads format
- **Azione:** Clicca "Google Ads CSV"
- **Risultato atteso:**
  - ✅ File `google_ads_{id}_2025-10-10.csv` scaricato
  - ✅ Apri file: header corretto per Google Ads
  - ✅ Se RSA: Columns Headline 1-15, Description 1-4, Final URL
  - ✅ Se PMAX: Columns Short Headline 1-5, Long Headline 1-5, Description 1-5

#### Test 6.4: Export senza asset
- **Azione:** Prova export su campaign senza asset
- **Risultato atteso:**
  - ✅ Redirect a dettaglio campaign
  - ✅ Flash warning "Nessun asset da esportare"

---

### 7. DELETE CAMPAIGN (2 tests)

#### Test 7.1: Conferma eliminazione
- **Azione:** Clicca "Elimina" dalla lista o dettaglio
- **Risultato atteso:**
  - ✅ Alert JavaScript: "Sei sicuro di voler eliminare questa campaign e tutti i suoi asset?"
  - ✅ Pulsanti OK/Cancel

#### Test 7.2: Eseguire eliminazione
- **Azione:** Conferma eliminazione
- **Risultato atteso:**
  - ✅ Redirect a lista campaigns
  - ✅ Flash message "Campaign eliminata con successo"
  - ✅ Campaign rimossa dal database
  - ✅ Asset associati eliminati (cascade)

---

### 8. FILTERS & SEARCH (2 tests)

#### Test 8.1: Toggle filtri (Alpine.js)
- **URL:** `/dashboard/campaigns`
- **Azione:** Clicca "Mostra filtri"
- **Risultato atteso:**
  - ✅ Sezione filtri slide down (x-transition)
  - ✅ Testo pulsante cambia in "Nascondi filtri"
  - ✅ Select "Tipo Campaign" visibile

#### Test 8.2: Filtrare per tipo
- **Azione:** Seleziona "RSA" dal filtro, clicca "Filtra"
- **Risultato atteso:**
  - ✅ Lista mostra solo campaigns RSA
  - ✅ PMAX campaigns nascoste
  - ✅ Pulsante "Reset" funziona (rimuove filtri)

---

### 9. PAGINATION (1 test)

#### Test 9.1: Paginazione lista
- **Setup:** Crea 25+ campaigns
- **Azione:** Scrolla in fondo alla lista
- **Risultato atteso:**
  - ✅ Links paginazione visibili (1, 2, Next)
  - ✅ Max 20 campaigns per pagina
  - ✅ Click "2" carica pagina successiva

---

### 10. AUTHORIZATION CHECKS (2 tests)

#### Test 10.1: User non può vedere campaigns altro tenant
- **Setup:** Login come User A (Tenant 1)
- **Azione:** Prova URL diretto: `/dashboard/campaigns/{id-tenant-2}`
- **Risultato atteso:**
  - ✅ 404 Not Found
  - ✅ Nessun leak di dati

#### Test 10.2: Policy regenerate check tokens
- **Setup:** Tenant con tokens_used_current >= tokens_monthly_limit
- **Azione:** Prova rigenerare asset
- **Risultato atteso:**
  - ✅ 403 Forbidden o redirect con errore
  - ✅ Message "Token insufficienti"

---

## Checklist UI/UX (da CLAUDE.md)

### Layout Consistency
- [ ] Usa `@extends('layouts.tenant')` ✅
- [ ] Sidebar visibile e corretta ✅
- [ ] Header con tenant name e token counter ✅
- [ ] Colori amber (#F59E0B) usati per primary actions ✅
- [ ] Responsive design (test su mobile/tablet)

### Alpine.js Interactivity
- [ ] Filters toggle smooth ✅
- [ ] Keywords chips real-time ✅
- [ ] Tabs switching senza page reload ✅
- [ ] Modals con overlay scuro ✅
- [ ] Copy-to-clipboard con feedback visivo ✅
- [ ] Toast notifications ✅

### Traduzioni Italiane
- [ ] Tutti i testi UI in italiano ✅
- [ ] Placeholder italiani ✅
- [ ] Flash messages italiani ✅
- [ ] Errors di validazione italiani ✅

### Icons & Visual
- [ ] FontAwesome 6.0 icons caricato ✅
- [ ] Icons corretti (bullhorn, coins, calendar, etc.) ✅
- [ ] Badge colorati per campaign types ✅
- [ ] Gradienti su stat cards ✅

---

## Database Integrity

### Verifica Relationships
```sql
-- Campaigns devono avere tenant_id
SELECT * FROM adv_campaigns WHERE tenant_id IS NULL;
-- Risultato atteso: 0 rows

-- Assets devono avere campaign_id valido
SELECT * FROM adv_generated_assets
WHERE campaign_id NOT IN (SELECT id FROM adv_campaigns);
-- Risultato atteso: 0 rows

-- Cascade delete funziona
DELETE FROM adv_campaigns WHERE id = 'xxx';
-- Verifica: assets associati eliminati automaticamente
```

---

## Performance & Security

### Security Checks
- [ ] CSRF token presente su tutti i form ✅
- [ ] Authorization checks su ogni controller method ✅
- [ ] Tenant scoping su tutte le query ✅
- [ ] No SQL injection (usa Eloquent ORM) ✅

### Performance
- [ ] Lazy loading asset relationship
- [ ] Pagination con `paginate(20)`
- [ ] withCount('assets') invece di eager loading completo
- [ ] Indexes su tenant_id, type, created_at

---

## Bugs/Issues Report

Durante il testing, documentare:
1. **Bug trovato:** [Descrizione]
2. **Steps to reproduce:** [1, 2, 3...]
3. **Risultato atteso:** [...]
4. **Risultato effettivo:** [...]
5. **Screenshot:** (se applicabile)

---

## Test Completion Criteria

✅ **PASS** se:
- Tutti i 29 scenari funzionano correttamente
- UI coerente con design tenant dashboard
- Nessun errore JavaScript console
- Nessun errore Laravel log
- Tenant isolation verificato
- Database integrity confermata

⚠️ **CONDITIONAL PASS** se:
- 1-3 bug minori UI/UX (es. spacing, colori)
- Performance accettabile ma non ottimizzata
- → Creare issue per fixing post-release

❌ **FAIL** se:
- Errori critici (500, crash)
- Data leak cross-tenant
- Authorization bypass
- Asset generation fallisce
- → Richiedere fix immediato

---

## Esecuzione Test

**Tester:** [Nome]
**Data:** [gg/mm/aaaa]
**Browser:** [Chrome/Firefox/Safari + versione]
**OS:** [Windows/Mac/Linux]

### Summary
- **Tests Passed:** ___ / 29
- **Tests Failed:** ___
- **Bugs Found:** ___
- **Status:** ✅ PASS / ⚠️ CONDITIONAL / ❌ FAIL

---

**Fine del Test Plan**
