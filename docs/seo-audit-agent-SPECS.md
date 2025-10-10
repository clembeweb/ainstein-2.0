# Specifiche Funzionali - AI SEO Audit Agent

## 1. Panoramica

Sistema di analisi tecnica SEO per siti web che esegue crawling controllato, identifica problemi tecnici, calcola un punteggio di salute del sito e genera report con raccomandazioni AI-powered.

### 1.1 Obiettivi
- Crawl completo di un sito web con controllo granulare dello scope
- Analisi tecnica SEO multi-dimensionale (HTTP, contenuti, struttura, link)
- Identificazione automatica di problemi con classificazione per severità
- Calcolo di un Site Health Score comparabile nel tempo
- Generazione di report actionable con sintesi e raccomandazioni AI
- Multi-tenancy con gestione organizzazioni e progetti
- Storico audit per analisi trend

---

## 2. Entità Principali

### 2.1 Organizzazione
Contenitore multi-tenant che raggruppa utenti e progetti.

**Attributi:**
- Nome organizzazione
- Owner (utente principale)
- Data creazione
- Impostazioni globali (API keys AI, limiti, preferenze)

### 2.2 Utente
Membro di un'organizzazione con ruolo specifico.

**Ruoli:**
- **Owner**: controllo totale organizzazione
- **Admin**: gestione progetti e audit
- **Member**: visualizzazione progetti assegnati

**Attributi:**
- Nome, email, password
- Ruolo nell'organizzazione
- Preferenze notifiche

### 2.3 Progetto
Rappresenta un sito web da analizzare.

**Attributi obbligatori:**
- Nome progetto
- Dominio root (es. `example.com`)
- Organizzazione di appartenenza

**Configurazioni:**
- **Scope**:
  - Includi sottodomini (sì/no)
  - Path di partenza (es. `/it/` per limitare a sezione)
  - Pattern URL da includere (regex/glob)
  - Pattern URL da escludere (regex/glob)

- **Autenticazione**:
  - HTTP Basic/Digest (username/password)
  - Cookie header (per aree autenticate)

- **Parametri URL**:
  - Whitelist parametri (mantieni solo questi)
  - Blacklist parametri (escludi questi, es. `utm_*`, `session`)
  - Normalizzazione ordine parametri

- **Crawl Settings**:
  - User-Agent personalizzato
  - Rispetta robots.txt (sì/no)
  - Max concorrenza richieste
  - Delay tra richieste (ms)
  - Timeout richiesta

- **Limiti**:
  - Max pagine da crawlare
  - Max profondità (click depth)

- **Pianificazione**:
  - Audit ricorrenti (giornaliero/settimanale/mensile)
  - Orario esecuzione

### 2.4 Audit
Esecuzione singola di analisi su un progetto.

**Attributi:**
- Progetto di riferimento
- Data/ora inizio
- Data/ora fine
- Stato: `pending`, `running`, `completed`, `failed`, `cancelled`
- Configurazione snapshot (copia settings progetto al momento esecuzione)
- Metriche aggregate:
  - Pagine totali crawlate
  - Pagine indicizzabili
  - Issues trovati (per severity)
  - Site Health Score
  - Durata crawl
  - Velocità media pagine (ms)

### 2.5 Pagina
Singola URL analizzata durante un audit.

**Attributi base:**
- URL completo (normalizzato)
- Status code HTTP
- Tempo di caricamento (ms)
- Dimensione (bytes)
- Content-Type
- Profondità (click depth da home)
- Data/ora crawl
- Renderizzata con JS (sì/no)
- Hash contenuto HTML

**Contenuti estratti:**
- Title
- Meta description
- Meta robots
- H1, H2 (primi)
- Canonical URL
- Open Graph tags (base)
- Twitter Card tags (base)
- Structured data (tipi rilevati)

**Link & Risorse:**
- Link interni trovati (conteggio)
- Link esterni trovati (conteggio)
- Immagini trovate (conteggio)
- CSS/JS caricati (conteggio)

**Indexability:**
- Indicizzabile (boolean calcolato)
- Motivi se non indicizzabile
- Presente in sitemap (boolean)

### 2.6 Issue
Problema rilevato durante l'analisi.

**Attributi:**
- Audit di riferimento
- Pagina di riferimento (opzionale, alcuni issue sono globali)
- Codice issue (identificativo univoco, vedi §5)
- Severità: `ERROR`, `WARN`, `INFO`
- Messaggio descrittivo
- Evidenze (JSON con dettagli tecnici: URL coinvolti, header, snippet HTML)
- Data prima rilevazione
- Conteggio occorrenze (se issue aggregato)

### 2.7 Link
Relazione tra pagine/risorse.

**Attributi:**
- Audit di riferimento
- Pagina sorgente
- URL destinazione (può essere esterno)
- Tipo: `internal`, `external`, `mailto`, `tel`
- Status code destinazione (se verificato)
- Anchor text
- Rel attributes (nofollow, sponsored, ugc)
- Posizione (navigation, content, footer)

### 2.8 Risorsa
Asset caricato da una pagina (CSS, JS, immagini, font).

**Attributi:**
- Audit di riferimento
- Pagina caricante
- URL risorsa
- Tipo: `css`, `js`, `image`, `font`, `other`
- Status code
- Dimensione (bytes)
- Caricata da header o HTML

### 2.9 Sitemap
Sitemap XML rilevata.

**Attributi:**
- Audit di riferimento
- URL sitemap
- Tipo: `index`, `regular`
- Entry totali
- Entry valide
- Ultimo aggiornamento (da lastmod)
- Errori parsing

### 2.10 AI Report
Report generato da AI per un audit.

**Attributi:**
- Audit di riferimento
- Provider AI utilizzato
- Modello utilizzato
- Template prompt
- Sintesi esecutiva (Markdown)
- Raccomandazioni (Markdown, con priorità)
- Token utilizzati
- Data generazione
- Durata generazione

---

## 3. Funzionalità Principali

### 3.1 Gestione Progetti

**F-PRJ-01: Creazione Progetto**
- Utente inserisce: nome, dominio root
- Opzioni base: includi sottodomini, path scope
- Sistema valida dominio (formato, raggiungibilità base)
- Salva con configurazioni default

**F-PRJ-02: Configurazione Avanzata Progetto**
- Scope patterns (include/exclude URL)
- Gestione parametri URL (whitelist/blacklist)
- Autenticazione (HTTP/cookie)
- Settings crawl (concorrenza, delay, user-agent)
- Limiti (max pagine, profondità)
- Pianificazione ricorrente

**F-PRJ-03: Modifica/Eliminazione Progetto**
- Modifica qualsiasi setting
- Eliminazione: soft delete o hard delete con cascade su audit

**F-PRJ-04: Lista Progetti**
- Visualizza tutti i progetti dell'organizzazione
- Filtri: stato ultimo audit, health score range, data ultima analisi
- Ordinamento per nome, data, score

### 3.2 Esecuzione Audit

**F-AUD-01: Avvio Audit Manuale**
- Da dettaglio progetto → pulsante "Avvia Audit"
- Opzione: usa config progetto o personalizza per questa esecuzione
- Sistema crea record audit in stato `pending`
- Accoda job di crawling
- Feedback immediato: "Audit avviato, ID #123"

**F-AUD-02: Audit Ricorrente**
- Configurato a livello progetto
- Cron system trigger automatico
- Stessa logica audit manuale
- Notifica opzionale a completamento

**F-AUD-03: Monitoraggio Audit in Corso**
- Visualizzazione stato real-time:
  - Pagine crawlate finora
  - Issues rilevati finora
  - Tempo trascorso
  - Stima completamento (se possibile)
- Possibilità di annullare audit

**F-AUD-04: Completamento Audit**
- Sistema finalizza metriche aggregate
- Calcola Site Health Score
- Confronta con audit precedente (delta)
- Opzionalmente genera AI report
- Notifica utente (email/UI)

### 3.3 Crawling e Analisi

**F-CRW-01: Seed Discovery**
- Parte da URL home (dominio root + scope path)
- Scarica e parsifica sitemap.xml (inclusi sitemap index)
- Aggiunge URL sitemap a queue

**F-CRW-02: Rispetto Robots.txt**
- Scarica robots.txt prima del crawl
- Se `obey_robots = true`: rispetta Disallow e Crawl-delay
- Se `obey_robots = false`: avvisa utente, crawl completo

**F-CRW-03: Scope Filtering**
- URL validato contro:
  - Dominio/sottodominio rules
  - Path prefix (scope_path)
  - Include patterns (se definiti, solo match)
  - Exclude patterns (skip match)
- Solo URL in-scope vengono crawlati

**F-CRW-04: Normalizzazione URL**
- Rimozione fragment (#)
- Normalizzazione schema (http→https se configurato)
- Ordinamento parametri query (se `normalize_order = true`)
- Filtro parametri (whitelist/blacklist)
- Deduplicazione URL identici

**F-CRW-05: Politeness**
- Rispetta `Crawl-delay` da robots.txt o usa default config
- Max connessioni concorrenti configurabili
- Retry con backoff esponenziale su 5xx
- Timeout richiesta configurabile

**F-CRW-06: JS Rendering Selettivo**
- Default: crawl HTML statico
- Euristica per attivare rendering JS:
  - Title assente o vuoto
  - Body HTML < soglia bytes
  - Pattern SPA rilevato (es. `<div id="app">` vuoto)
- Se attivato: esegue rendering headless, estrae DOM finale
- Flag pagina come "renderizzata"

**F-CRW-07: Estrazione Contenuti**
Per ogni pagina:
- Status code, headers HTTP
- Tempo risposta
- Title, meta description, meta robots
- Canonical link
- H1, H2 (primi N)
- Tutti i link (`<a href>`) con anchor, rel
- Tutte le immagini (`<img src>`) con alt
- Hreflang alternate
- Open Graph e Twitter Card tags
- Structured data (JSON-LD, Microdata) - tipo e validità base
- Risorse caricate (CSS, JS da `<link>`, `<script>`)

**F-CRW-08: Link Discovery**
- Estrae tutti link interni → aggiunge a queue crawl
- Link esterni: verifica status (opzionale, può rallentare)
- Costruisce grafo interno (from_page → to_page)

**F-CRW-09: Resource Check**
- Verifica status code di:
  - Immagini (per rilevare 404)
  - CSS/JS (per broken resources)
- Registra dimensione e tipo

### 3.4 Rilevamento Issue

Il sistema applica regole automatiche per identificare problemi. Ogni regola controlla specifiche condizioni e genera issue con severità appropriata.

**F-ISS-01: Analisi HTTP**
- `HTTP_4XX`: pagina ritorna 4xx → ERROR
- `HTTP_5XX`: pagina ritorna 5xx → ERROR
- `REDIRECT_CHAIN`: >1 redirect prima pagina finale → WARN
- `REDIRECT_LOOP`: loop rilevato → ERROR
- `SLOW_PAGE`: tempo risposta > soglia → WARN
- `LARGE_PAGE`: dimensione HTML > soglia → INFO

**F-ISS-02: Analisi Meta Tags**
- `TITLE_MISSING`: title assente o vuoto → ERROR
- `TITLE_DUPLICATE`: stesso title su più pagine → WARN
- `TITLE_TOO_SHORT`: title < 30 char → WARN
- `TITLE_TOO_LONG`: title > 65 char → WARN
- `META_DESC_MISSING`: meta description assente → WARN
- `META_DESC_DUPLICATE`: stessa desc su più pagine → WARN
- `META_DESC_TOO_SHORT`: < 70 char → INFO
- `META_DESC_TOO_LONG`: > 160 char → WARN
- `H1_MISSING`: H1 assente → WARN
- `H1_DUPLICATE`: stesso H1 su più pagine → INFO
- `MULTIPLE_H1`: più di un H1 → WARN

**F-ISS-03: Analisi Canonical**
- `CANONICAL_MISSING`: canonical assente su pagina indicizzabile → WARN
- `CANONICAL_MULTIPLE`: più tag canonical → ERROR
- `CANONICAL_CONFLICT`: canonical in HTML ≠ canonical in header HTTP → ERROR
- `CANONICAL_NON_200`: canonical punta a URL non-200 → ERROR
- `CANONICAL_CHAIN`: canonical punta a pagina con altro canonical (no self-referencing) → WARN
- `CANONICAL_EXTERNAL`: canonical punta fuori dominio → INFO

**F-ISS-04: Analisi Indexability**
- `NOINDEX_IN_SITEMAP`: pagina noindex presente in sitemap.xml → ERROR
- `NOINDEX_CONFLICT`: meta robots noindex MA header X-Robots-Tag index → ERROR
- `INDEXABLE_BLOCKED_ROBOTS`: pagina indicizzabile ma bloccata da robots.txt → WARN
- `NOFOLLOW_INTERNAL`: link interno con rel=nofollow (potenziale spreco crawl budget) → INFO

**F-ISS-05: Analisi Link**
- `BROKEN_INTERNAL_LINK`: link interno → 4xx/5xx → ERROR
- `BROKEN_EXTERNAL_LINK`: link esterno → 4xx/5xx → WARN
- `ORPHAN_PAGE`: pagina non in sitemap e zero inlink interni → WARN
- `REDIRECT_INTERNAL`: link interno punta a redirect → INFO
- `DEEP_PAGE`: profondità > soglia (es. >4 clic da home) → INFO

**F-ISS-06: Analisi Immagini**
- `IMG_ALT_MISSING`: img senza alt → WARN
- `IMG_ALT_EMPTY`: img con alt="" → INFO (ok se decorativa)
- `IMG_4XX`: img → 404 → ERROR
- `IMG_TOO_LARGE`: dimensione img > soglia (es. 500KB) → INFO
- `IMG_NO_DIMENSIONS`: img senza width/height (CLS) → INFO

**F-ISS-07: Analisi Hreflang**
- `HREFLANG_INVALID_CODE`: codice lingua non valido (non ISO 639-1) → ERROR
- `HREFLANG_NO_RETURN_TAG`: A punta a B con hreflang, ma B non punta ad A → ERROR
- `HREFLANG_CANONICAL_MISMATCH`: hreflang punta a URL con canonical diverso → WARN
- `HREFLANG_SELF_MISSING`: manca hreflang self-reference → WARN
- `HREFLANG_NON_200`: alternate punta a non-200 → ERROR

**F-ISS-08: Analisi Sicurezza**
- `MIXED_CONTENT`: pagina HTTPS carica risorse HTTP → ERROR
- `NO_HTTPS`: pagina servita solo HTTP (se dominio supporta HTTPS) → WARN

**F-ISS-09: Analisi Robots & Sitemap**
- `ROBOTS_MISSING`: robots.txt non trovato → INFO
- `ROBOTS_SYNTAX_ERROR`: robots.txt con errori parsing → WARN
- `SITEMAP_MISSING`: sitemap.xml non trovato né in robots né standard location → WARN
- `SITEMAP_INVALID`: sitemap con errori XML → ERROR
- `SITEMAP_ENTRY_4XX`: URL in sitemap ritorna 4xx → ERROR

**F-ISS-10: Analisi Struttura**
- `DUPLICATE_CONTENT`: hash HTML identico su URL diversi → WARN
- `THIN_CONTENT`: contenuto testuale < soglia parole → INFO
- `MISSING_SCHEMA`: pagina senza structured data (se previsto per tipo) → INFO

### 3.5 Calcolo Site Health Score

**F-SHT-01: Formula Base**
```
Health Score = 100 - Σ(peso_issue × occorrenze_normalizzate)
```

**F-SHT-02: Pesi Default**
- ERROR: peso = 3.0
- WARN: peso = 1.0
- INFO: peso = 0.5

**F-SHT-03: Normalizzazione**
- Occorrenze normalizzate = (count_issue / totale_pagine) × 100
- Esempio: 10 pagine con TITLE_MISSING su 100 pagine totali = 10%
- Penalità = 3.0 × 10 = 30 punti
- Score = 100 - 30 = 70

**F-SHT-04: Pesi Personalizzabili**
- Organizzazione può personalizzare pesi per issue type
- Salvataggio in settings organizzazione
- Applica ai nuovi audit

**F-SHT-05: Soglie Interpretazione**
- 90-100: Eccellente (verde)
- 70-89: Buono (giallo)
- 50-69: Necessita attenzione (arancione)
- 0-49: Critico (rosso)

### 3.6 Analisi Trend

**F-TRN-01: Confronto Audit**
- Mostra delta tra audit corrente e precedente:
  - Δ Health Score (+/- punti, %)
  - Δ Issues per severity (nuovi, risolti, persistenti)
  - Δ Pagine crawlate
  - Δ Pagine indicizzabili

**F-TRN-02: Grafico Storico**
- Serie temporale Health Score su ultimi N audit
- Evidenzia audit con peggioramenti significativi
- Permette click per drill-down su audit specifico

**F-TRN-03: Issue Evolution**
- Traccia issue specifici nel tempo:
  - Quando apparso la prima volta
  - Quando risolto (se sparisce in audit successivo)
  - Fluttuazioni conteggio occorrenze

### 3.7 AI Report Generation

**F-AI-01: Configurazione Provider**
- A livello organizzazione: settings per provider AI
- Supporto provider multipli: OpenAI, Anthropic, Azure OpenAI, altri
- Campi: API key (criptata), modello, parametri (temperature, max_tokens)

**F-AI-02: Template Prompt**
- Prompt configurabile per generazione report
- Variabili disponibili: `{{domain}}`, `{{audit_id}}`, `{{health_score}}`, `{{top_issues}}`, `{{pages_count}}`
- Default template fornito, personalizzabile

**F-AI-03: Generazione Report**
- Trigger: manuale o automatico a fine audit
- Input: audit metrics + top 10-20 issue + esempi URL
- Output atteso:
  - **Executive Summary**: sintesi per stakeholder non tecnici (150-200 parole)
  - **Prioritized Actions**: lista azioni P0/P1/P2 con motivazione e impatto atteso
  - **Quick Wins**: interventi implementabili in <2h con alto ROI
  - **Risks & Dependencies**: blockers, dipendenze tecniche
- Formato: Markdown strutturato

**F-AI-04: Fallback Non-AI**
- Se API key assente o errore: genera report statico con template
- Include metriche aggregate e top issues senza analisi LLM

**F-AI-05: Gestione Errori**
- Retry con backoff su errori transitori
- Log errori per debug
- Notifica utente se generazione fallisce

### 3.8 Reporting & Export

**F-RPT-01: Visualizzazione Report**
- Sezioni:
  - **Overview**: Health Score, metriche chiave, grafico trend
  - **Issues**: lista filtrata per severity, con conteggio occorrenze
  - **Pages**: tabella pagine con status, issues, metriche
  - **Resources**: CSS/JS/IMG con status code
  - **Sitemaps**: lista sitemap con entry counts
  - **AI Insights**: executive summary + recommendations
- UI navigabile con tab/accordion

**F-RPT-02: Export HTML**
- Genera pagina HTML standalone
- Include CSS inline
- Stampabile
- Scaricabile

**F-RPT-03: Export PDF**
- Genera PDF da template HTML
- Impaginazione corretta (header, footer, page breaks)
- Grafici embedded
- Logo organizzazione (se configurato)

**F-RPT-04: Export CSV Issues**
- Colonne: Issue Code, Severity, Page URL, Message, Evidence (JSON/testo)
- Filtro applicato (se UI filtrata)
- Ordinamento mantenuto

**F-RPT-05: Export CSV Pages**
- Colonne: URL, Status Code, Title, Meta Description, H1, Canonical, Indexable, Load Time, Size, Depth, Issue Count
- Include solo pagine in-scope

**F-RPT-06: Invio Email Report**
- Opzione per inviare report via email a lista destinatari
- Allegati: PDF + CSV
- Corpo email: executive summary

### 3.9 Dashboard & UI

**F-UI-01: Dashboard Organizzazione**
- KPI cards:
  - Health Score medio progetti
  - Totale progetti attivi
  - Audit eseguiti ultimo mese
  - Issue critici aperti
- Lista ultimi audit con status e quick actions
- Job queue status (running/pending)

**F-UI-02: Lista Progetti**
- Tabella progetti con:
  - Nome, dominio
  - Ultimo audit (data, score)
  - Stato (mai auditato, in corso, completato)
  - Actions: Avvia Audit, Visualizza, Configura, Elimina
- Filtri: score range, data ultimo audit
- Ordinamento colonne

**F-UI-03: Dettaglio Progetto**
- Info progetto + configurazioni
- Storia audit (tabella con data, score, pagine, issues)
- Pulsante "Avvia Nuovo Audit"
- Grafici trend
- Link a audit specifici

**F-UI-04: Dettaglio Audit**
- Tab principali:
  - **Overview**: metriche, score, AI summary
  - **Issues**: tabella filtrata con severity, occorrenze, drill-down
  - **Pages**: tabella pagine con search, sort, pagination
  - **Resources**: tabella risorse broken/large
  - **Sitemaps**: lista sitemap analizzate
  - **Comparison**: confronto con audit precedente
- Export actions visibili
- Breadcrumb navigazione

**F-UI-05: Dettaglio Issue**
- Issue info: code, severity, message, guida risoluzione
- Lista occorrenze (tutte le pagine affette)
- Esempi evidence
- Filtri: per pagina, per pattern URL
- Export occorrenze

**F-UI-06: Dettaglio Pagina**
- Metadati completi (HTTP, contenuti, link)
- Lista issue rilevati su questa pagina
- Link grafo: inlink (da dove arriva), outlink (dove punta)
- Screenshot (se disponibile)
- Raw HTML preview (collapsible)

**F-UI-07: Impostazioni Organizzazione**
- Sezione **AI Configuration**:
  - Provider AI (select)
  - API Key (input password, criptata)
  - Modello (input text)
  - Parametri (JSON o form)
  - Test connection button
- Sezione **Crawl Defaults**:
  - User-Agent default
  - Max concurrency
  - Delay default
  - Obey robots (checkbox)
  - Max pages default
  - Max depth default
- Sezione **Issue Weights**:
  - Tabella issue types con slider peso
  - Reset to defaults
- Sezione **Users & Permissions**:
  - Invita utenti (email + ruolo)
  - Lista membri con gestione ruoli
  - Revoca accesso

**F-UI-08: Gestione Utenti**
- Owner può invitare utenti via email
- Assegnazione ruolo (Admin/Member)
- Admin può gestire progetti
- Member vede solo progetti assegnati

### 3.10 Notifiche

**F-NOT-01: Notifiche Audit Completato**
- Email a utente che ha avviato audit
- Contenuto: score, top issues, link diretto report

**F-NOT-02: Notifiche Peggioramento**
- Se Health Score < soglia o calo > X%
- Email a admin organizzazione
- Evidenzia issue nuovi critici

**F-NOT-03: Notifiche Errori**
- Se audit fallisce (errori crawler/timeout)
- Email a owner + admin
- Log allegato o link a dettaglio

**F-NOT-04: Notifiche Job Queue**
- (Opzionale) Alert se queue stalled
- Notifica admin sistema

### 3.11 Pianificazione & Automazione

**F-SCH-01: Audit Ricorrenti**
- Configurazione a livello progetto
- Frequenze: giornaliero, settimanale, mensile
- Orario preferito (fascia oraria per ridurre impatto)
- Cron job trigger automatico

**F-SCH-02: Gestione Coda**
- Job queue per crawl, analisi, AI report
- Priority queue: audit manuali > audit schedulati
- Retry automatico con backoff su fallimenti transienti

**F-SCH-03: Pulizia Dati Vecchi**
- Policy retention: elimina audit > N mesi (configurabile)
- Mantieni sempre ultimo audit per progetto
- Pulizia automatica via scheduled job

---

## 4. Regole di Business

### 4.1 Scope & Filtering

**R-01: Dominio Root**
- Un progetto ha UN dominio root (es. `example.com`)
- Tutti gli URL crawlati devono appartenere a questo dominio o sottodomini (se abilitato)

**R-02: Sottodomini**
- Se `include_subdomains = true`: crawl anche `blog.example.com`, `shop.example.com`
- Se `false`: solo `example.com` e `www.example.com`

**R-03: Scope Path**
- Se impostato (es. `/en/`): crawl solo URL che iniziano con questo path
- Utile per analizzare solo una sezione del sito

**R-04: Pattern Include/Exclude**
- **Include** = whitelist: se definito, solo URL che matchano vengono crawlati
- **Exclude** = blacklist: URL che matchano vengono sempre saltati
- Ordine valutazione: prima exclude (priorità), poi include

**R-05: Parametri URL**
- **Whitelist parametri**: se definita, solo questi parametri vengono mantenuti (es. `page`, `id`)
- **Blacklist parametri**: questi parametri vengono sempre rimossi (es. `utm_*`, `fbclid`)
- **Normalize order**: ordina parametri alfabeticamente per deduplicazione
- Esempio: `?b=2&a=1` normalizzato diventa `?a=1&b=2`

### 4.2 Crawl Behavior

**R-06: Robots.txt**
- Se `obey_robots = true`:
  - Scarica `/robots.txt` prima del crawl
  - Non crawla path con `Disallow`
  - Rispetta `Crawl-delay` (secondi tra richieste per user-agent)
- Se `false`: ignora robots.txt (attenzione: uso responsabile)

**R-07: Politeness**
- Delay minimo tra richieste allo stesso host: configurabile (default 300ms)
- Max connessioni concorrenti: configurabile (default 8)
- Rispetta `Crawl-delay` se > delay configurato

**R-08: Retry**
- Status 5xx o timeout → retry fino a 3 volte con backoff esponenziale
- Status 4xx → no retry, registra come errore

**R-09: Redirect**
- Segue redirect (301, 302, 303, 307, 308) fino a max 5 hop
- Registra catena redirect
- Se loop rilevato → errore, stop follow

**R-10: JS Rendering**
- Default: parsing HTML statico (più veloce)
- Attiva rendering JS se:
  - `<title>` assente o vuoto
  - `<body>` < 500 bytes contenuto
  - Pattern SPA rilevato: `<div id="app"></div>` vuoto + `<script>` grande
- Timeout rendering: 5 secondi
- Se rendering fallisce: usa HTML statico

### 4.3 Link Discovery

**R-11: Link Interni**
- Link verso stesso dominio (+sottodomini se abilitato) → aggiungi a queue crawl
- Rispetta scope path e patterns

**R-12: Link Esterni**
- Registrati ma non crawlati
- Opzionalmente verifica status code (HEAD request)

**R-13: Link Speciali**
- `mailto:`, `tel:`, `javascript:` → registrati ma non seguiti
- `#fragment` → ignora fragment, segui URL base (deduplicato)

**R-14: Nofollow**
- Link con `rel="nofollow"` → registrato come tale, ma comunque seguito per crawl interno
- Issue INFO se nofollow su link interno importante (navigazione)

### 4.4 Indexability

**R-15: Pagina Indicizzabile**
Una pagina è considerata indicizzabile SE:
- Status code = 200
- `robots` meta tag ≠ `noindex`
- Header `X-Robots-Tag` ≠ `noindex`
- Non bloccata da robots.txt (se obey_robots = true)
- Non è redirect/404/5xx

**R-16: Conflitti Indexability**
- Se meta robots dice `noindex` MA header X-Robots-Tag dice `index` → issue ERROR (priorità meta)
- Se pagina ha `noindex` ma è in sitemap.xml → issue ERROR

### 4.5 Canonical

**R-17: Canonical Self-Reference**
- Best practice: ogni pagina indicizzabile dovrebbe avere canonical self-referencing
- Se pagina indicizzabile senza canonical → issue WARN

**R-18: Canonical Chain**
- Se pagina A → canonical B, e B → canonical C: issue WARN (deve puntare direttamente a C)

**R-19: Canonical Conflict**
- Se canonical in HTML ≠ canonical in header HTTP → issue ERROR (priorità header)

**R-20: Canonical Target**
- Canonical deve puntare a URL con status 200
- Se punta a 404/301/altro → issue ERROR

### 4.6 Hreflang

**R-21: Reciprocità**
- Se pagina A (it) punta a pagina B (en) con hreflang, allora B deve puntare ad A
- Se reciprocità manca → issue ERROR

**R-22: Self-reference**
- Ogni pagina con hreflang deve includere se stessa (es. `hreflang="it" href="current_url"`)
- Se manca → issue WARN

**R-23: Coerenza Canonical**
- Se pagina ha hreflang alternate + canonical, canonical deve puntare alla stessa pagina
- Se canonical punta altrove → issue WARN (confusione per motori)

**R-24: Codici Lingua**
- Formato valido: ISO 639-1 (2 lettere) opzionalmente + ISO 3166-1 (2 lettere paese)
- Esempi validi: `en`, `en-US`, `en-GB`
- Invalidi: `eng`, `en_US` → issue ERROR

### 4.7 Sitemap

**R-25: Inclusione Sitemap**
- Solo pagine indicizzabili (200, index) dovrebbero essere in sitemap
- Se pagina noindex in sitemap → issue ERROR

**R-26: Completezza Sitemap**
- Confronta pagine crawlate vs pagine in sitemap
- Pagine non in sitemap E senza inlink interni → issue WARN "pagina orfana"

### 4.8 Issue Severity Logic

**R-27: ERROR = Bloccante**
- Impedisce indicizzazione o causa gravi problemi UX/SEO
- Esempi: 5xx, broken internal link, canonical conflict, hreflang non reciproco

**R-28: WARN = Importante**
- Danneggia performance SEO ma non bloccante
- Esempi: title duplicate, redirect chain, meta description missing

**R-29: INFO = Ottimizzazione**
- Best practice, marginal improvement
- Esempi: H1 duplicate, immagine troppo grande, deep page

### 4.9 Site Health Score

**R-30: Score Range**
- Min = 0, Max = 100
- Non può essere negativo (floor a 0)

**R-31: Normalizzazione**
- Issue count normalizzato rispetto a pagine totali crawlate
- Esempio: se crawl 10 pagine o 1000 pagine, stesso tipo issue ha impatto proporzionale

**R-32: Pesi Modificabili**
- Organizzazione può personalizzare pesi
- Change applicato a nuovi audit, non retroattivo

### 4.10 AI Report

**R-33: Privacy Dati**
- Invia a LLM solo: issue types, conteggi, esempi URL (max 5 per issue), metriche aggregate
- NON invia: contenuto HTML completo, dati utente, API keys

**R-34: Timeout**
- Generazione AI report: timeout max 60 secondi
- Se supera → fallback a report statico

**R-35: Costo**
- Traccia token utilizzati (input + output) per monitoring costi

---

## 5. Issue Types Completo

### Categoria: HTTP

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `HTTP_4XX` | ERROR | Pagina ritorna 4xx | status_code ∈ [400-499] |
| `HTTP_5XX` | ERROR | Pagina ritorna 5xx | status_code ∈ [500-599] |
| `REDIRECT_CHAIN` | WARN | Redirect chain > 1 hop | count(redirects) > 1 |
| `REDIRECT_LOOP` | ERROR | Redirect loop rilevato | redirect target già visitato |
| `SLOW_PAGE` | WARN | Tempo caricamento eccessivo | load_time > soglia (es. 3s) |
| `LARGE_PAGE` | INFO | Dimensione HTML eccessiva | size_bytes > soglia (es. 500KB) |
| `TIMEOUT` | ERROR | Request timeout | timeout exception |

### Categoria: META

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `TITLE_MISSING` | ERROR | Title assente/vuoto | !title \|\| title.length == 0 |
| `TITLE_DUPLICATE` | WARN | Title duplicato | hash(title) già visto su altro URL |
| `TITLE_TOO_SHORT` | WARN | Title troppo corto | title.length < 30 |
| `TITLE_TOO_LONG` | WARN | Title troppo lungo | title.length > 65 |
| `META_DESC_MISSING` | WARN | Meta description assente | !meta[name=description] |
| `META_DESC_DUPLICATE` | WARN | Meta description duplicata | hash(description) già visto |
| `META_DESC_TOO_SHORT` | INFO | Meta description corta | description.length < 70 |
| `META_DESC_TOO_LONG` | WARN | Meta description lunga | description.length > 160 |
| `H1_MISSING` | WARN | H1 assente | !h1 |
| `H1_DUPLICATE` | INFO | H1 duplicato | h1 text già visto su altro URL |
| `MULTIPLE_H1` | WARN | Più di un H1 | count(h1) > 1 |

### Categoria: CANONICAL

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `CANONICAL_MISSING` | WARN | Canonical assente | !canonical && indexable |
| `CANONICAL_MULTIPLE` | ERROR | Più canonical tag | count(canonical) > 1 |
| `CANONICAL_CONFLICT` | ERROR | Canonical HTML ≠ header | canonical_html != canonical_header |
| `CANONICAL_NON_200` | ERROR | Canonical punta a non-200 | check(canonical_url).status != 200 |
| `CANONICAL_CHAIN` | WARN | Canonical chain rilevato | canonical punta a pagina con altro canonical |
| `CANONICAL_EXTERNAL` | INFO | Canonical esterno | canonical domain != page domain |

### Categoria: INDEXABILITY

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `NOINDEX_IN_SITEMAP` | ERROR | Noindex presente in sitemap | noindex && in_sitemap |
| `NOINDEX_CONFLICT` | ERROR | Conflitto noindex meta/header | meta=noindex && header=index |
| `INDEXABLE_BLOCKED_ROBOTS` | WARN | Indicizzabile ma bloccata | indexable && blocked_by_robots |
| `NOFOLLOW_INTERNAL` | INFO | Nofollow su link interno | link.internal && link.nofollow |

### Categoria: LINKS

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `BROKEN_INTERNAL_LINK` | ERROR | Link interno broken | link.internal && target.status ∈ [4xx, 5xx] |
| `BROKEN_EXTERNAL_LINK` | WARN | Link esterno broken | link.external && target.status ∈ [4xx, 5xx] |
| `ORPHAN_PAGE` | WARN | Pagina orfana | inlink_count == 0 && !in_sitemap |
| `REDIRECT_INTERNAL` | INFO | Link interno a redirect | link.internal && target.status ∈ [301,302,307] |
| `DEEP_PAGE` | INFO | Pagina troppo profonda | depth > soglia (es. 4) |

### Categoria: IMAGES

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `IMG_ALT_MISSING` | WARN | Immagine senza alt | img.alt === null |
| `IMG_ALT_EMPTY` | INFO | Immagine con alt vuoto | img.alt === "" |
| `IMG_4XX` | ERROR | Immagine 404 | img.status ∈ [400-499] |
| `IMG_TOO_LARGE` | INFO | Immagine troppo grande | img.size > soglia (es. 500KB) |
| `IMG_NO_DIMENSIONS` | INFO | Img senza width/height | !img.width \|\| !img.height |

### Categoria: HREFLANG

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `HREFLANG_INVALID_CODE` | ERROR | Codice lingua invalido | !match(ISO-639-1) |
| `HREFLANG_NO_RETURN_TAG` | ERROR | Mancanza reciprocità | A→B ma B non→A |
| `HREFLANG_CANONICAL_MISMATCH` | WARN | Hreflang vs canonical conflict | hreflang.url != canonical |
| `HREFLANG_SELF_MISSING` | WARN | Manca self-reference | !hreflang(current_lang, current_url) |
| `HREFLANG_NON_200` | ERROR | Alternate punta a non-200 | check(hreflang_url).status != 200 |

### Categoria: SECURITY

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `MIXED_CONTENT` | ERROR | Mixed content (HTTPS→HTTP) | page.https && resource.http |
| `NO_HTTPS` | WARN | Pagina solo HTTP | !page.https && domain_supports_https |

### Categoria: ROBOTS & SITEMAP

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `ROBOTS_MISSING` | INFO | robots.txt assente | !robots.txt |
| `ROBOTS_SYNTAX_ERROR` | WARN | robots.txt errori parsing | parse_error |
| `SITEMAP_MISSING` | WARN | sitemap.xml non trovato | !sitemap.xml |
| `SITEMAP_INVALID` | ERROR | sitemap.xml XML invalido | xml_parse_error |
| `SITEMAP_ENTRY_4XX` | ERROR | Entry sitemap ritorna 4xx | sitemap_url.status ∈ [4xx] |

### Categoria: CONTENT

| Code | Severity | Descrizione | Rilevamento |
|------|----------|-------------|-------------|
| `DUPLICATE_CONTENT` | WARN | Contenuto HTML duplicato | hash(html) già visto |
| `THIN_CONTENT` | INFO | Contenuto scarso | word_count < soglia (es. 200) |
| `MISSING_SCHEMA` | INFO | Structured data assente | expected_schema && !found |

---

## 6. Metriche & KPI

### 6.1 Metriche per Audit

- **Pagine totali crawlate**: count(pages)
- **Pagine indicizzabili**: count(pages WHERE indexable = true)
- **Pagine non indicizzabili**: count(pages WHERE indexable = false)
- **Pagine orfane**: count(pages WHERE inlink_count = 0 AND NOT in_sitemap)
- **Issues totali**: count(issues)
- **Issues per severity**: count(issues GROUP BY severity)
- **Pagine con errori**: count(DISTINCT pages WHERE issues.severity = ERROR)
- **Link broken interni**: count(links WHERE internal AND target.status 4xx)
- **Immagini broken**: count(images WHERE status 4xx)
- **Tempo medio caricamento**: avg(pages.load_time)
- **Dimensione media pagina**: avg(pages.size_bytes)
- **Profondità media**: avg(pages.depth)
- **Sitemap entries vs crawlate**: sitemap_entries / pages_crawled

### 6.2 Metriche per Organizzazione

- **Health Score medio**: avg(audits.health_score) per progetti attivi
- **Progetti totali**: count(projects)
- **Audit eseguiti (periodo)**: count(audits WHERE created_at > period_start)
- **Issue critici aperti**: sum(audits.latest.issues WHERE severity=ERROR)
- **Trend medio**: avg(delta_health_score) ultimi 3 mesi

### 6.3 Metriche per Dashboard

- **Job queue depth**: count(jobs WHERE status = pending)
- **Audit in corso**: count(audits WHERE status = running)
- **Ultimo completamento**: max(audits.finished_at)

---

## 7. AI Integration - Specifiche

### 7.1 Input per AI

Struttura dati inviata al LLM:

```json
{
  "domain": "example.com",
  "audit_id": 123,
  "audit_date": "2025-10-08",
  "pages_crawled": 450,
  "pages_indexable": 380,
  "health_score": 72,
  "health_score_previous": 85,
  "health_score_delta": -13,
  "issues_summary": {
    "ERROR": 45,
    "WARN": 120,
    "INFO": 89
  },
  "top_issues": [
    {
      "code": "BROKEN_INTERNAL_LINK",
      "severity": "ERROR",
      "count": 25,
      "examples": [
        "https://example.com/page1",
        "https://example.com/page2"
      ],
      "description": "Link interni broken"
    }
    // ... altri issue
  ],
  "metrics": {
    "avg_load_time_ms": 850,
    "orphan_pages": 12,
    "duplicate_titles": 8
  }
}
```

### 7.2 Output Atteso AI

Formato Markdown con sezioni:

```markdown
## Executive Summary
[Sintesi 150-200 parole per stakeholder non tecnici]

## Prioritized Actions

### P0 - Critical (Immediate)
- **[Issue Type]**: [Descrizione azione]
  - Impatto: [alto/medio/basso]
  - Effort: [ore/giorni]
  - Pages affected: [N]

### P1 - High (This Week)
[...]

### P2 - Medium (This Month)
[...]

## Quick Wins
- [Azione implementabile in <2h con ROI alto]
- [...]

## Risks & Dependencies
- **Risk**: [descrizione]
  - Mitigation: [come mitigare]
- **Dependency**: [team/strumento necessario]

## Long-term Recommendations
[Suggerimenti strategici]
```

### 7.3 Prompt Template (Esempio)

```
Sei un Senior Technical SEO Auditor esperto.

Contesto:
- Dominio: {{domain}}
- Data audit: {{audit_date}}
- Pagine crawlate: {{pages_crawled}}
- Pagine indicizzabili: {{pages_indexable}}
- Health Score: {{health_score}} (precedente: {{health_score_previous}}, delta: {{health_score_delta}})

Issue rilevati:
{{top_issues_table}}

Compiti:
1. Genera una sintesi esecutiva (max 200 parole) comprensibile a stakeholder non tecnici.
2. Identifica e prioritizza azioni correttive (P0/P1/P2) con motivazione, impatto atteso, effort stimato.
3. Evidenzia 3-5 "quick wins" implementabili in meno di 2 ore con alto ROI.
4. Indica eventuali rischi e dipendenze (team dev, infra, content).
5. Suggerisci raccomandazioni strategiche a lungo termine.

Output richiesto: Markdown strutturato come da template.
```

### 7.4 Provider Agnostic

Sistema deve supportare:
- **OpenAI**: modelli GPT (gpt-4o, gpt-4o-mini)
- **Anthropic**: modelli Claude (claude-3-5-sonnet, claude-3-5-haiku)
- **Azure OpenAI**: deployment personalizzati
- **Altri**: possibilità di estendere via adapter pattern

Configurazione per provider:
- Endpoint API
- API Key
- Modello/Deployment
- Parametri (temperature, max_tokens, top_p)

---

## 8. Workflow Dettagliati

### 8.1 Workflow: Creazione Progetto

1. Utente naviga a "Nuovi Progetto"
2. Form: Nome, Dominio, Includi sottodomini
3. Sistema valida:
   - Nome non vuoto
   - Dominio formato valido
   - Dominio raggiungibile (ping DNS)
4. Salva progetto con config default
5. Redirect a dettaglio progetto
6. Mostra suggerimento: "Configura impostazioni avanzate" o "Avvia primo audit"

### 8.2 Workflow: Avvio Audit

1. Utente clicca "Avvia Audit" da dettaglio progetto
2. (Opzionale) Modal per override configurazioni per questo audit
3. Sistema:
   - Crea record `audit` con status=`pending`
   - Snapshot configurazione progetto in `audit.config_json`
   - Accoda job crawl con config
   - Mostra feedback: "Audit #456 avviato"
4. Background:
   - Job pickup da queue
   - Inizia crawl (vedi workflow crawl)
   - Emissione eventi per ogni pagina crawlata
5. UI mostra stato real-time (polling o WebSocket)

### 8.3 Workflow: Crawl

1. **Seed Discovery**:
   - Fetch home URL (root_domain + scope_path)
   - Parse sitemap.xml (robots.txt o /sitemap.xml)
   - Aggiungi URL a queue

2. **Loop Crawl** (parallelo, rispetta limiti):
   - Pop URL da queue
   - Verifica scope (dominio, path, patterns)
   - Se già visitato (URL normalizzato): skip
   - Fetch HTML:
     - Tentativo statico
     - Se euristica JS rendering: headless render
   - Parse HTML:
     - Status, headers, timing, size
     - Meta tags (title, desc, robots, canonical)
     - Link (internal/external, anchor, rel)
     - Immagini (src, alt)
     - Hreflang alternates
   - Emit evento `PageCrawled` → API Laravel
   - Estrai link interni → aggiungi a queue (se depth OK)
   - Rispetta delay

3. **Completamento**:
   - Quando queue vuota o limiti raggiunti
   - Emit evento `CrawlFinished`

### 8.4 Workflow: Ingestion & Analysis

1. API Laravel riceve `PageCrawled` evento
2. Job `IngestPageResult`:
   - Upsert `pages` table
   - Upsert `links` table (from page)
   - Upsert `resources` table (CSS/JS/IMG)
3. Job `DetectIssuesForPage`:
   - Applica tutte le regole issue (§5)
   - Insert `issues` table
4. Ripete per ogni pagina

### 8.5 Workflow: Finalize Audit

1. Evento `CrawlFinished` triggera job `FinalizeAudit`
2. Calcoli post-crawl:
   - **Orphan pages**: pagine senza inlink E non in sitemap
   - **Duplicate content**: hash HTML duplicati
   - **Link graph**: costruisci grafo interno per metriche
3. Calcola **Site Health Score** (formula §3.5)
4. Confronta con audit precedente (delta)
5. Aggiorna `audit` record: status=`completed`, metriche aggregate
6. (Opzionale) Trigger job `GenerateAIReport`

### 8.6 Workflow: AI Report Generation

1. Job `GenerateAIReport` pickup
2. Recupera dati audit:
   - Metriche aggregate
   - Top 20 issue con esempi URL
   - Confronto con audit precedente
3. Costruisce payload JSON (§7.1)
4. Chiama API AI provider (configurato in settings):
   - Invia prompt + payload
   - Timeout 60s
5. Parse response Markdown
6. Salva in `ai_reports` table:
   - summary_md
   - recommendations_md
   - metadata (provider, model, token usage)
7. Se errore: fallback a template statico

### 8.7 Workflow: Export PDF

1. Utente clicca "Export PDF" da dettaglio audit
2. Controller prepara dati:
   - Carica audit, metriche, top issues, AI report
   - Render view Blade HTML con layout print-friendly
3. Genera PDF da HTML (libreria PDF)
4. Stream file al browser con header download
5. (Opzionale) Salva PDF su storage per cache

### 8.8 Workflow: Audit Ricorrente

1. Cron job esegue ogni minuto: `schedule:run`
2. Scheduler controlla progetti con `recurring_schedule` configurato
3. Per progetti da schedulare (in fascia oraria):
   - Trigger `StartAuditJob` (stesso flow audit manuale)
   - Log schedulazione
4. Notifica completamento via email

---

## 9. Requisiti UX

### 9.1 Performance UI

- Tabelle con paginazione server-side (max 50 righe)
- Filtri e ricerca: debounce input (300ms)
- Loading states durante azioni async (spinner)
- Feedback immediato su azioni (toast notifications)

### 9.2 Accessibilità

- Contrasto colori: WCAG AA (4.5:1)
- Focus states visibili
- Aria labels su icone
- Navigazione keyboard-friendly
- Screen reader compatible (semantic HTML)

### 9.3 Mobile Responsive

- Dashboard adattiva fino a 320px width
- Tabelle: scrolling orizzontale su mobile
- Touch-friendly buttons (min 44x44px)
- Menu collapsible su mobile

### 9.4 Feedback & Errori

- Messaggi errore chiari e actionable
- Conferma azioni distruttive (elimina progetto/audit)
- Progress bar per operazioni lunghe (crawl)
- Tooltip su campi configurazione complessi

---

## 10. Configurazioni & Settings

### 10.1 Settings Organizzazione

**AI Configuration**:
- Provider (select: OpenAI, Anthropic, Azure)
- API Key (encrypted storage)
- Model name
- Temperature (0-1)
- Max tokens

**Crawl Defaults**:
- User-Agent default
- Max concurrency
- Delay (ms)
- Obey robots.txt (boolean)
- Max pages
- Max depth

**Issue Weights**:
- ERROR weight (default 3.0)
- WARN weight (default 1.0)
- INFO weight (default 0.5)
- Per-issue custom weights (opzionale)

**Notifications**:
- Email notifiche audit completato (boolean)
- Email notifiche peggioramento score (boolean)
- Threshold peggioramento (%)
- Email destinatari (lista)

**Retention**:
- Giorni retention audit (default 90)
- Mantieni sempre ultimo (boolean)

### 10.2 Settings Progetto

Tutte le configurazioni descritte in §2.3 (Progetto)

### 10.3 Settings Globali Sistema

(A livello applicazione, non esposti a utente):
- Redis connection
- Database connection
- Queue driver
- Storage driver
- HMAC secret per eventi crawler
- Log level

---

## 11. Sicurezza

### 11.1 Autenticazione & Autorizzazione

- Login email/password con hash bcrypt
- Session management con cookie httpOnly
- CSRF protection su form
- RBAC: Owner > Admin > Member
- Check permessi su ogni azione (policy)

### 11.2 Protezione Dati

- API keys criptate in DB (encryption at rest)
- Comunicazioni HTTPS obbligatorie
- HMAC signature su eventi crawler → API
- Rate limiting su API endpoint (es. 60 req/min)
- Input validation & sanitization

### 11.3 Crawler Ethics

- User-Agent identificabile con contatto
- Rispetto robots.txt di default
- Rate limiting per non sovraccaricare siti
- Esclusione cartelle sensibili default (`/admin`, `/checkout`, `/cart`, `/account`)
- Log consenso ToS utente

---

## 12. Limiti & Vincoli

### 12.1 Limiti Tecnici

- Max pagine per audit: 100.000 (configurabile)
- Max profondità: 20 livelli
- Max dimensione HTML: 10 MB per pagina
- Max timeout richiesta: 30 secondi
- Max redirect follow: 5 hop
- Max concurrent crawl per audit: 20 connessioni

### 12.2 Limiti Business (Esempio)

Piano Free:
- 1 progetto
- 500 pagine/audit
- 1 audit/giorno
- No AI report

Piano Pro:
- 10 progetti
- 10.000 pagine/audit
- Audit illimitati
- AI report inclusi

Piano Enterprise:
- Progetti illimitati
- 100.000 pagine/audit
- API access
- White-label

### 12.3 Rate Limiting

- Avvio audit: max 5/hour per progetto
- API calls: 60/min per utente
- Export: 10/min per utente

---

## 13. Testing & Quality Assurance

### 13.1 Test Funzionali Chiave

**Test 1: Creazione Progetto**
- Input: nome, dominio valido
- Output: progetto creato, redirect a dettaglio
- Validazione: dominio non raggiungibile → errore

**Test 2: Avvio Audit**
- Input: progetto con config valida
- Output: audit creato, job accodato
- Verifica: status `pending` → `running` → `completed`

**Test 3: Crawl Semplice**
- Mock site con 10 pagine
- Audit completa in <1 minuto
- Tutte pagine registrate in DB
- Link graph corretto

**Test 4: Rilevamento Issue**
- Pagina con title missing → issue `TITLE_MISSING`
- Pagina 404 → issue `HTTP_4XX`
- Canonical conflict → issue `CANONICAL_CONFLICT`
- Verifica: issue salvati con severity corretta

**Test 5: Calcolo Health Score**
- Audit con issue noti
- Verifica formula: score = 100 - Σ(peso × norm)
- Confronto con atteso

**Test 6: AI Report**
- Audit completato con metriche
- Trigger AI report
- Verifica: summary e recommendations presenti
- Fallback se API key assente

**Test 7: Export PDF**
- Audit con dati completi
- Download PDF
- Verifica: file valido, dimensione > 0, contiene metriche

**Test 8: Audit Ricorrente**
- Progetto con schedule giornaliero
- Simula cron trigger
- Verifica: nuovo audit creato automaticamente

### 13.2 Criteri Accettazione

- [ ] Utente può creare progetto e avviare audit
- [ ] Crawl completa senza errori su sito test (es. 100 pagine)
- [ ] Almeno 15 tipi di issue rilevati correttamente
- [ ] Site Health Score calcolato con formula corretta
- [ ] AI report generato (o fallback se no API key)
- [ ] Export PDF e CSV funzionanti
- [ ] Confronto audit (trend) mostra delta corretto
- [ ] Audit ricorrente si attiva automaticamente
- [ ] Pagine orfane identificate correttamente
- [ ] Hreflang reciprocità verificata
- [ ] Settings API key salvate criptate
- [ ] Rate limiting previene abusi

---

## 14. Estensioni Future (Post-MVP)

Funzionalità non nel MVP ma desiderabili:

1. **Lighthouse Integration**
   - Esegui Lighthouse su campione URL
   - Raccogli Performance, Accessibility, Best Practices, SEO scores
   - Aggrega metriche nel report

2. **Core Web Vitals (CrUX)**
   - Integra Chrome UX Report API
   - Mostra LCP, FID, CLS per origine
   - Confronto con industry benchmark

3. **Link Graph Visualization**
   - Grafo interattivo internal link structure
   - Identifica hub pages e bottleneck
   - Export grafo (GraphML, JSON)

4. **Advanced Scheduling**
   - Trigger audit su deploy (webhook)
   - Conditional scheduling (se score < threshold)

5. **Competitor Benchmarking**
   - Confronto multi-sito (competitor analysis)
   - Radar chart comparativo

6. **API Pubblica**
   - RESTful API per trigger audit
   - Webhook callbacks a fine audit
   - Query issue via API

7. **Integrations**
   - Slack notifications
   - Google Search Console data import
   - Google Analytics integration (pageviews su broken pages)

8. **White-label**
   - Custom branding (logo, colori)
   - Custom domain per report

9. **Team Collaboration**
   - Commenti su issue
   - Assegnazione issue a team member
   - Task tracking (fix issue → verifica)

10. **Historical Trend Analysis**
    - Machine learning per predizione trend
    - Alert su anomalie pattern

---

## 15. Glossario

- **Audit**: esecuzione singola di analisi su un progetto
- **Canonical**: URL preferito per versioni duplicate di contenuto
- **Crawl Budget**: risorse che motore ricerca dedica a crawl sito
- **CWV (Core Web Vitals)**: metriche UX Google (LCP, FID, CLS)
- **Hreflang**: attributo per versioni lingua/regione di pagina
- **Indexable**: pagina che motori possono indicizzare
- **Issue**: problema SEO rilevato durante audit
- **Noindex**: direttiva per non indicizzare pagina
- **Nofollow**: attributo link per non passare "link juice"
- **Orphan Page**: pagina senza link interni né in sitemap
- **Robots.txt**: file per controllo crawl motori
- **Scope**: insieme di URL da includere in crawl
- **Site Health Score**: metrica 0-100 salute SEO sito
- **Sitemap.xml**: file XML con lista URL da crawlare
- **User-Agent**: identificativo client HTTP (browser/bot)

---

## 16. Note Implementative Generali

### 16.1 Separazione Concerns

- **Web App**: gestione utenti, UI, orchestrazione
- **Crawler**: esecuzione crawl, estrazione dati
- **Analyzer**: applicazione regole issue
- **AI Service**: integrazione LLM
- **Export Service**: generazione report

Ogni componente è testabile indipendentemente.

### 16.2 Scalabilità

- Crawl parallelizzabile: multiple worker per audit
- Database: indici su URL, audit_id, issue_code
- Queue: Redis/SQS per distribuzione job
- Storage: S3/equivalente per PDF/screenshot

### 16.3 Monitoring

- Log strutturato (JSON) per audit events
- Metriche: crawl duration, page/s, issue detection time
- Alert: queue stall, crawler failures, API errors
- Dashboard ops: job queue depth, worker health

### 16.4 Extensibility

- Issue detection: pattern Strategy per aggiungere regole
- AI providers: pattern Adapter per nuovi LLM
- Export formats: template engine per nuovi formati
- Crawler middlewares: pipeline per custom logic

---

**Fine Specifiche Funzionali**

Versione: 1.0
Data: 2025-10-08
Autore: AI SEO Audit Agent Team