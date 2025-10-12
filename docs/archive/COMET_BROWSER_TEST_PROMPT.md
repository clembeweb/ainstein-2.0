# Comet Browser - Test Completo AINSTEIN Application

## Obiettivo
Testare completamente l'applicazione AINSTEIN su https://ainstein.it come un utente reale, documentando ogni funzionalità e segnalando eventuali errori.

---

## Credenziali di Accesso
- **URL**: https://ainstein.it
- **Email**: admin@ainstein.com
- **Password**: password123

---

## FASE 1: AUTENTICAZIONE E DASHBOARD

### Test 1.1: Login
1. Vai su https://ainstein.it
2. Verifica che la homepage si carica correttamente
3. Click sul pulsante "Login" o vai direttamente su https://ainstein.it/login
4. Inserisci email: admin@ainstein.com
5. Inserisci password: password123
6. Click su "Login" o "Accedi"
7. **Verifica**: Dovresti essere reindirizzato a /dashboard
8. **Documenta**: Se appare un errore, fai screenshot e copia il messaggio

### Test 1.2: Dashboard Overview
1. Una volta dentro, verifica che la dashboard si carica
2. **Controlla questi elementi**:
   - [ ] Header con nome utente visibile
   - [ ] Nome del tenant visualizzato
   - [ ] Token usage bar (con percentuale)
   - [ ] Card "Content Generator" con statistiche
   - [ ] Card "Campaign Generator" con statistiche
   - [ ] Card "SEO Tools" (probabilmente con "Coming Soon")
   - [ ] Menu di navigazione laterale o top
3. **Screenshot**: Fai uno screenshot della dashboard completa
4. **Documenta**: Eventuali sezioni mancanti o errori visibili

### Test 1.3: Navigation Menu
1. Verifica che il menu di navigazione contenga queste voci:
   - [ ] Dashboard (home)
   - [ ] Campaigns
   - [ ] Pages o Content
   - [ ] API Keys
   - [ ] Crews (potrebbe essere nascosto o in sviluppo)
2. **Testa**: Click su ogni voce del menu e verifica che la pagina si carica
3. **Documenta**: Link non funzionanti o pagine 404

---

## FASE 2: CAMPAIGN GENERATOR (FEATURE PRINCIPALE)

### Test 2.1: Lista Campaigns
1. Dal menu, vai su "Campaigns" o visita https://ainstein.it/dashboard/campaigns
2. **Verifica**:
   - [ ] La pagina si carica senza errori
   - [ ] Se ci sono campaigns, vengono visualizzate in una tabella/lista
   - [ ] Se NON ci sono campaigns, appare un messaggio "Nessuna campaign" o "Empty state"
   - [ ] C'è un pulsante "Nuova Campaign" o "Create Campaign"
3. **Screenshot**: Pagina campaigns

### Test 2.2: Crea Campaign RSA
1. Click su "Nuova Campaign" o "Create Campaign"
2. **Compila il form con questi dati**:
   - **Nome Campaign**: Test Campaign RSA Comet
   - **Tipo Campaign**: Seleziona "RSA" (Responsive Search Ads)
   - **Business Description**: "E-commerce di scarpe sportive specializzato in running e fitness. Vendiamo scarpe Nike, Adidas, Asics per corridori professionisti e amatori."
   - **Target Keywords**: scarpe running, sneakers, calzature sport, scarpe fitness
   - **URL Finale**: https://example.com/scarpe-running
   - **Lingua**: Italiano (o English se disponibile)
3. **Verifica validazione**:
   - [ ] Prova a submit con campi vuoti → dovrebbe apparire errore
   - [ ] Prova a inserire caratteri speciali nel nome → verifica validazione
4. **Submit il form**:
   - Click su "Genera" o "Crea Campaign"
5. **Attendi generazione AI** (potrebbe richiedere 10-30 secondi)
   - [ ] Appare un loading spinner o progress bar?
   - [ ] Vedi un messaggio "Generazione in corso..."?
6. **IMPORTANTE**: Se appare un errore tipo "OpenAI API key non configurata" o "Insufficient tokens":
   - **Documenta**: Fai screenshot e copia esattamente l'errore
   - **Salta** i prossimi test di generazione AI
   - **Continua** con i test delle altre sezioni

### Test 2.3: Visualizza Campaign Generata (se generazione OK)
1. Dopo la generazione, dovresti vedere:
   - [ ] 15 Titles (Headlines) - max 30 caratteri ciascuno
   - [ ] 4 Descriptions - max 90 caratteri ciascuna
   - [ ] Quality Score (da 1 a 10)
   - [ ] Token usage per questa generazione
   - [ ] Pulsanti: Edit, Regenerate, Export, Delete
2. **Screenshot**: Pagina detail campaign con assets generati
3. **Verifica assets**:
   - [ ] Ogni title ha indicatore lunghezza caratteri
   - [ ] Ogni description ha indicatore lunghezza
   - [ ] Keywords target appaiono nei testi generati

### Test 2.4: Export Campaign
1. Click su pulsante "Export" o dropdown export
2. **Testa Export CSV**:
   - Click "Export CSV"
   - Verifica che il download parte
   - Apri il file CSV e controlla che contenga i dati
3. **Testa Export Google Ads** (se disponibile):
   - Click "Export Google Ads"
   - Verifica formato file
4. **Screenshot**: Dropdown export e file scaricato

### Test 2.5: Edit Campaign
1. Click su "Edit" o "Modifica"
2. Cambia il nome campaign: "Test Campaign RSA Comet - EDITED"
3. Salva le modifiche
4. **Verifica**: Il nuovo nome appare nella lista campaigns

### Test 2.6: Regenerate Assets (se hai token)
1. Torna al detail della campaign
2. Click su "Regenerate" o "Rigenera Assets"
3. **Verifica**:
   - [ ] Appare conferma: "Sei sicuro? Consumerà X tokens"
   - [ ] Click conferma
   - [ ] Nuova generazione parte
   - [ ] Assets vengono sostituiti con nuovi
4. **Documenta**: Se fallisce per mancanza token, è normale

### Test 2.7: Delete Campaign
1. Click su "Delete" o "Elimina"
2. **Verifica**:
   - [ ] Appare modale di conferma "Sei sicuro?"
   - [ ] Click "Conferma"
   - [ ] Campaign viene eliminata
   - [ ] Redirect alla lista campaigns
3. **Screenshot**: Modale conferma delete

### Test 2.8: Crea Campaign PMAX (se possibile)
1. Ripeti i test 2.2-2.7 ma selezionando tipo "PMAX" (Performance Max)
2. **PMAX richiede**:
   - 3-5 Short Titles (max 30 chars)
   - 1-5 Long Titles (max 90 chars)
   - 1-5 Descriptions (max 90 chars)
3. **Documenta**: Differenze rispetto a RSA

---

## FASE 3: CONTENT GENERATOR

### Test 3.1: Content Overview
1. Dal menu, vai su "Content" o "Pages" (https://ainstein.it/dashboard/content)
2. **Verifica tabs**:
   - [ ] Tab "Pages"
   - [ ] Tab "Prompts"
   - [ ] Tab "Generations" (potrebbe essere "Content Generations")
3. **Screenshot**: Pagina content con tabs

### Test 3.2: Pages Management
1. Click sul tab "Pages"
2. **Verifica**:
   - [ ] Lista pages (o empty state)
   - [ ] Pulsante "Nuova Page" o "Create Page"
   - [ ] Filtri (status, search)
   - [ ] Paginazione (se ci sono molte page)

### Test 3.3: Crea Page
1. Click "Nuova Page"
2. **Compila form**:
   - **Title**: Test Page Comet Browser
   - **URL**: https://example.com/test-page
   - **Content Type**: Blog Post (o altro disponibile)
   - **Status**: Draft
   - **Meta Description** (se presente): "Pagina di test creata da Comet Browser"
3. **Submit** e verifica creazione
4. **Screenshot**: Form create page

### Test 3.4: Edit Page
1. Trova la page appena creata nella lista
2. Click "Edit" o icona matita
3. Cambia il title: "Test Page Comet Browser - EDITED"
4. Salva
5. **Verifica**: Modifiche salvate correttamente

### Test 3.5: Delete Page
1. Click "Delete" o icona cestino
2. Conferma eliminazione
3. **Verifica**: Page rimossa dalla lista

### Test 3.6: Prompts Management
1. Click sul tab "Prompts"
2. **Verifica**:
   - [ ] Lista prompts
   - [ ] Pulsante "Nuovo Prompt"
   - [ ] Ogni prompt mostra: nome, categoria, variabili

### Test 3.7: Crea Prompt
1. Click "Nuovo Prompt"
2. **Compila form**:
   - **Nome**: Test Prompt Comet
   - **Categoria**: SEO (o altra disponibile)
   - **Template**: "Genera un articolo SEO su {{topic}} includendo {{keywords}}"
   - **Variabili**: topic, keywords (dovrebbero auto-detect da {{variabile}})
3. **Submit** e verifica creazione
4. **Screenshot**: Form create prompt con template

### Test 3.8: Test Prompt (se disponibile)
1. Trova il prompt creato
2. Click "Test" o "Prova"
3. **Compila variabili**:
   - topic: Scarpe Running
   - keywords: Nike, Adidas, performance
4. Click "Genera"
5. **Verifica**: Output generato (se OpenAI configurato)

### Test 3.9: Duplicate Prompt
1. Trova un prompt nella lista
2. Click "Duplicate" o icona copia
3. **Verifica**: Nuovo prompt creato con "(copia)" nel nome

### Test 3.10: Content Generations
1. Click sul tab "Generations"
2. **Verifica**:
   - [ ] Lista generazioni passate
   - [ ] Filtri per status (completed, failed, pending)
   - [ ] Ogni generation mostra: prompt usato, status, data, token usage

### Test 3.11: Visualizza Generation Detail
1. Click su una generation nella lista
2. **Verifica**:
   - [ ] Content generato visualizzato
   - [ ] Informazioni: prompt, variabili, tokens, quality score
   - [ ] Pulsanti: Copy, Regenerate, Save
3. **Screenshot**: Generation detail

---

## FASE 4: API KEYS MANAGEMENT

### Test 4.1: API Keys Lista
1. Dal menu, vai su "API Keys" (https://ainstein.it/dashboard/api-keys)
2. **Verifica**:
   - [ ] Lista API keys esistenti (o empty state)
   - [ ] Pulsante "Genera Nuova Key" o "New API Key"
   - [ ] Ogni key mostra: nome, partial key (es. ak_...xyz), status, created_at

### Test 4.2: Genera Nuova API Key
1. Click "Genera Nuova Key"
2. **Compila form**:
   - **Nome**: Test API Key Comet
   - **Descrizione**: Key di test per Comet Browser
   - **Permissions** (se disponibile): Seleziona tutte
3. Click "Genera"
4. **IMPORTANTE**: Appare un modale con la key completa
   - [ ] **COPIA LA KEY** (viene mostrata solo una volta!)
   - [ ] Salva la key in un file di testo
   - [ ] Verifica che la key abbia formato tipo: "ak_..." o simile
5. **Screenshot**: Modale con API key visibile (CENSURA la key nello screenshot!)

### Test 4.3: Visualizza API Key Detail
1. Trova la key appena creata nella lista
2. Click sul nome o "View"
3. **Verifica**:
   - [ ] Key parziale visualizzata (es. ak_...xyz)
   - [ ] Nome e descrizione
   - [ ] Status: Active
   - [ ] Usage statistics (requests, tokens)
   - [ ] Created date
   - [ ] Last used date

### Test 4.4: Revoke API Key
1. Click "Revoke" o "Revoca"
2. Conferma revoca
3. **Verifica**:
   - [ ] Status cambia a "Revoked"
   - [ ] Badge rosso o grigio
   - [ ] Key non più utilizzabile

### Test 4.5: Activate API Key (se disponibile)
1. Sulla key revocata, click "Activate"
2. **Verifica**: Status torna a "Active"

### Test 4.6: Delete API Key
1. Click "Delete"
2. Conferma eliminazione
3. **Verifica**: Key rimossa dalla lista

---

## FASE 5: CREWAI MULTI-AGENT SYSTEM (se disponibile)

### Test 5.1: Crews Lista
1. Dal menu, cerca "Crews" o vai su https://ainstein.it/dashboard/crews
2. **Se la pagina non esiste**:
   - **Documenta**: "Crews feature non presente nel menu o URL 404"
   - **Salta** al test successivo (Fase 6)
3. **Se la pagina esiste**:
   - [ ] Lista crews (o empty state)
   - [ ] Pulsante "Nuova Crew" o "Create Crew"

### Test 5.2: Crea Crew
1. Click "Nuova Crew"
2. **Compila form**:
   - **Nome**: Test Crew Comet
   - **Descrizione**: Crew di test per generazione articoli SEO
   - **Process Type**: Sequential (o altro disponibile)
   - **Verbose**: True (se disponibile)
3. **Submit** e verifica creazione
4. **Screenshot**: Form create crew

### Test 5.3: Aggiungi Agents
1. Nella crew appena creata, cerca sezione "Agents"
2. Click "Add Agent" o "+  Agent"
3. **Compila form agent**:
   - **Role**: SEO Specialist
   - **Goal**: "Creare contenuti ottimizzati per SEO"
   - **Backstory**: "Esperto SEO con 10 anni di esperienza"
   - **Tools**: Seleziona tools disponibili (es. web_search, calculator)
4. **Submit** e verifica agent aggiunto
5. **Ripeti**: Aggiungi un secondo agent con role "Content Writer"

### Test 5.4: Aggiungi Tasks
1. Nella crew, cerca sezione "Tasks"
2. Click "Add Task" o "+ Task"
3. **Compila form task**:
   - **Description**: "Ricerca keywords per articolo su scarpe running"
   - **Assign To**: SEO Specialist
   - **Expected Output**: "Lista di 10 keywords rilevanti"
4. **Submit** e verifica task aggiunto
5. **Ripeti**: Aggiungi secondo task "Scrivi articolo 500 parole" assegnato a Content Writer

### Test 5.5: Execute Crew
1. Click su "Execute" o "Esegui Crew"
2. **Verifica**:
   - [ ] Appare conferma "Eseguire crew?"
   - [ ] Click conferma
   - [ ] Viene creata una "Execution" e redirect a execution detail
   - [ ] Status: "Pending" o "Running"

### Test 5.6: Monitora Execution
1. Nella pagina execution detail:
   - [ ] Status viene aggiornato (Pending → Running → Completed/Failed)
   - [ ] Logs in tempo reale (se disponibile)
   - [ ] Task-by-task progress
2. **Attendi** il completamento (può richiedere 1-5 minuti)
3. **Screenshot**: Execution logs

### Test 5.7: View Execution Results
1. Quando execution è "Completed":
   - [ ] Visualizza output di ogni task
   - [ ] Verifica quality e coerenza risultati
2. **Documenta**: Se execution "Failed", copia errore

### Test 5.8: Crew Templates (se disponibile)
1. Cerca menu "Crew Templates" o vai su /dashboard/crew-templates
2. **Verifica**:
   - [ ] Lista templates predefiniti
   - [ ] Opzione "Use Template" per creare crew da template
   - [ ] Opzione "Save as Template" per salvare crew come template

---

## FASE 6: ADMIN PANEL (solo Superadmin)

### Test 6.1: Accesso Admin
1. Vai su https://ainstein.it/admin
2. **Verifica**:
   - [ ] Accesso consentito (sei superadmin)
   - [ ] Dashboard admin si carica

### Test 6.2: Tenants Management
1. Vai su "Tenants" nel menu admin
2. **Verifica**:
   - [ ] Lista tutti i tenants nel sistema
   - [ ] Vedi almeno 1 tenant (il tuo: "Super Admin Tenant")
   - [ ] Opzione "Create New Tenant"

### Test 6.3: Crea Tenant (test)
1. Click "Create New Tenant"
2. **Compila form**:
   - **Nome**: Test Tenant Comet
   - **Subdomain**: test-comet (se richiesto)
   - **Plan Type**: Free
   - **Token Limit**: 100000
3. **Submit** e verifica creazione
4. **Screenshot**: Form create tenant

### Test 6.4: Users Management
1. Vai su "Users" nel menu admin
2. **Verifica**:
   - [ ] Lista tutti gli users del sistema
   - [ ] Vedi almeno l'admin (te stesso)
   - [ ] Opzione "Create New User"

### Test 6.5: Platform Settings
1. Vai su "Settings" nel menu admin
2. **Esplora tutte le sezioni**:
   - [ ] **General Settings**: Logo, Platform Name
   - [ ] **OpenAI Settings**: API Key configuration
   - [ ] **OAuth Settings**: Google Client ID/Secret
   - [ ] **Stripe Settings**: API Keys per pagamenti
   - [ ] **Email Settings**: SMTP configuration
   - [ ] **Advanced Settings**: Debug mode, etc.

### Test 6.6: Configura OpenAI (CRITICO)
1. Nella sezione "OpenAI Settings":
2. **SE NON È CONFIGURATO**:
   - Inserisci una API key OpenAI valida (richiedi all'utente)
   - Click "Save"
   - Click "Test Connection" (se disponibile)
   - **Verifica**: Messaggio "Connection successful"
3. **Screenshot**: Sezione OpenAI settings (CENSURA la API key)

### Test 6.7: System Prompts (Filament)
1. Cerca menu "System Prompts" o "Prompts" nell'admin
2. **Verifica**:
   - [ ] Lista prompts di sistema predefiniti
   - [ ] Possibilità di modificare prompt templates

---

## FASE 7: UI/UX & RESPONSIVE

### Test 7.1: Navigation & Layout
1. **Verifica elementi header**:
   - [ ] Logo aziendale visibile
   - [ ] Nome utente nel dropdown
   - [ ] Plan type badge (Free/Pro/Enterprise)
   - [ ] Token usage indicator con percentuale
2. **Test navigation menu**:
   - [ ] Menu laterale o top bar
   - [ ] Active item evidenziato
   - [ ] Hover effects su menu items
3. **Screenshot**: Layout completo desktop

### Test 7.2: Notifications & Feedback
1. **Esegui azioni che triggano notifiche**:
   - Create campaign → Verifica toast "Campaign creata con successo"
   - Delete item → Verifica toast "Eliminato con successo"
   - Form con errori → Verifica error messages
2. **Verifica toast notifications**:
   - [ ] Success: sfondo verde
   - [ ] Error: sfondo rosso
   - [ ] Warning: sfondo giallo/amber
   - [ ] Info: sfondo blu
   - [ ] Auto-dismiss dopo 3-5 secondi

### Test 7.3: Modals & Popups
1. Testa tutte le modals incontrate:
   - [ ] Delete confirmation modal
   - [ ] API key reveal modal
   - [ ] Onboarding tour (se appare)
2. **Verifica**:
   - [ ] Modal overlay scuro
   - [ ] Close button funzionante
   - [ ] Click fuori modal → chiude (se previsto)
   - [ ] Esc key → chiude

### Test 7.4: Forms Validation
1. **Testa validazione su tutti i form**:
   - Campaign create form
   - Page create form
   - Prompt create form
   - API key generate form
2. **Verifica**:
   - [ ] Campi required marcati con asterisco
   - [ ] Submit con campi vuoti → errori visualizzati
   - [ ] Error messages in italiano (o lingua corretta)
   - [ ] Errori in-line sotto i campi
   - [ ] Focus automatico sul primo campo con errore

### Test 7.5: Empty States
1. **Vai su sezioni vuote** (senza dati):
   - Campaigns senza campaigns
   - Pages senza pages
   - API Keys senza keys
2. **Verifica empty state**:
   - [ ] Illustrazione o icona
   - [ ] Messaggio "Nessun elemento trovato" o simile
   - [ ] CTA button "Crea il primo..." ben visibile

### Test 7.6: Loading States
1. Durante operazioni asincrone (AI generation):
   - [ ] Spinner o loading animation
   - [ ] Disabilitazione pulsanti durante loading
   - [ ] Messaggio "Generazione in corso..."
   - [ ] Impossibile submit form multipli simultaneamente

### Test 7.7: Responsive Design - Mobile
1. **Riduci finestra browser** a dimensioni mobile (375px width)
2. **Verifica**:
   - [ ] Menu hamburger appare
   - [ ] Menu hamburger si apre/chiude correttamente
   - [ ] Tabelle diventano scrollable o card
   - [ ] Form fields stack verticalmente
   - [ ] Buttons full-width su mobile
   - [ ] Testi leggibili (non troppo piccoli)
   - [ ] Token usage bar responsivo
3. **Screenshot**: Layout mobile

### Test 7.8: Responsive Design - Tablet
1. **Riduci finestra** a dimensioni tablet (768px width)
2. **Verifica**:
   - [ ] Layout si adatta correttamente
   - [ ] 2 colonne per card (invece di 3 desktop)
   - [ ] Menu visibile o hamburger
3. **Screenshot**: Layout tablet

---

## FASE 8: FORMS & EDGE CASES

### Test 8.1: Character Counters
1. Nei form con limiti di caratteri (es. Campaign titles):
2. **Verifica**:
   - [ ] Counter visualizzato: "15/30"
   - [ ] Counter diventa rosso quando superi limite
   - [ ] Form non permette submit se superi limite

### Test 8.2: Keyword Preview
1. Nel form Campaign create:
2. Inserisci keywords: "test1, test2, test3"
3. **Verifica**:
   - [ ] Preview keywords come badges
   - [ ] Possibilità di rimuovere keyword (click su X)
   - [ ] Keywords separate correttamente da virgola

### Test 8.3: CSRF Token
1. **Testa sicurezza CSRF**:
   - Apri DevTools → Network tab
   - Submit un form qualsiasi
   - Verifica header: "X-CSRF-TOKEN" presente
2. **Documenta**: Se manca, è un security issue

### Test 8.4: Session Management
1. **Lascia tab aperta** per 2+ ore (se possibile)
2. Prova a submit un form
3. **Verifica**:
   - [ ] Se session scaduta → redirect a login
   - [ ] Messaggio "Session scaduta"
   - [ ] Dopo re-login → redirect a pagina originale

### Test 8.5: Concurrent Actions
1. **Apri 2 tab** dello stesso browser
2. In entrambe vai alla stessa campaign
3. **Tab 1**: Edit campaign name
4. **Tab 2**: Edit campaign name (diverso)
5. Submit entrambi
6. **Verifica**: Come viene gestito il conflitto?

### Test 8.6: SQL Injection Prevention
1. **Testa input malevoli** (l'app DOVREBBE bloccarli):
   - Nel nome campaign: `'; DROP TABLE users; --`
   - Nella search: `' OR '1'='1`
2. **Verifica**:
   - [ ] Input sanitized correttamente
   - [ ] Nessun errore SQL
   - [ ] Input salvato come string normale

### Test 8.7: XSS Prevention
1. **Testa script injection**:
   - Nel nome campaign: `<script>alert('XSS')</script>`
   - Nella description: `<img src=x onerror=alert('XSS')>`
2. **Verifica**:
   - [ ] Script NON eseguito
   - [ ] HTML escaped: visualizzi letteralmente `<script>...`

---

## FASE 9: PERFORMANCE & CONSOLE

### Test 9.1: Page Load Time
1. **Apri DevTools → Network tab**
2. Ricarica diverse pagine:
   - Dashboard home
   - Campaigns list
   - Campaign create
3. **Verifica**:
   - [ ] Load time < 2 secondi
   - [ ] DOMContentLoaded < 1 secondo
   - [ ] Risorse ottimizzate (CSS/JS minificati)

### Test 9.2: JavaScript Console Errors
1. **Apri DevTools → Console tab**
2. Naviga per tutta l'applicazione
3. **Documenta**:
   - Ogni errore JavaScript trovato (screenshot + messaggio)
   - Warnings (meno critici)
4. **Verifica**: Console pulita senza errori

### Test 9.3: Network Requests
1. **Apri DevTools → Network tab**
2. Esegui azioni (create, update, delete)
3. **Verifica**:
   - [ ] Richieste API hanno status 200 (success)
   - [ ] Nessun 404 Not Found
   - [ ] Nessun 500 Internal Server Error
   - [ ] Nessun 401 Unauthorized (tranne logout)

### Test 9.4: Database Queries
1. Se hai accesso SSH al server:
   - Tail Laravel log: `tail -f storage/logs/laravel.log`
   - Esegui azioni nell'app
   - Verifica log in tempo reale
2. **Documenta**: Errori PHP o query SQL fallite

---

## FASE 10: SECURITY & AUTHORIZATION

### Test 10.1: Tenant Isolation
1. **IMPORTANTE**: Questo test richiede 2 tenant
2. **Se hai solo 1 tenant**:
   - Vai in admin panel
   - Crea secondo tenant "Test Tenant 2"
   - Crea user per questo tenant
   - Logout
   - Login con nuovo user
3. **Test isolation**:
   - User Tenant B NON deve vedere dati Tenant A
   - Prova accesso diretto URL campaign di Tenant A
   - **Verifica**: 403 Forbidden o redirect

### Test 10.2: Role-Based Access
1. Se hai user con ruoli diversi (owner, admin, member):
   - Login come "member" (se disponibile)
   - **Verifica**: Azioni limitate (non può delete, non può manage users, etc.)
2. **Documenta**: Quali azioni sono bloccate per quale ruolo

### Test 10.3: Direct URL Access
1. **Prova accesso diretto** a URL senza permission:
   - https://ainstein.it/admin (se non sei superadmin)
   - URL campaigns di altri tenant (se hai 2 tenant)
2. **Verifica**:
   - [ ] 403 Forbidden
   - [ ] Redirect a dashboard con messaggio errore
   - [ ] NON visualizzi dati non autorizzati

### Test 10.4: Logout
1. Click su dropdown user
2. Click "Logout" o "Esci"
3. **Verifica**:
   - [ ] Session invalidata
   - [ ] Redirect a login o homepage
   - [ ] Tentativo accesso /dashboard → redirect a login

---

## FASE 11: GOOGLE OAUTH (se configurato)

### Test 11.1: Login con Google
1. Vai su https://ainstein.it/login
2. **Cerca pulsante**: "Continua con Google" o "Login with Google"
3. **Se pulsante NON esiste**:
   - **Documenta**: "Google OAuth non configurato nel frontend"
   - **Salta** questa sezione
4. **Se pulsante esiste**:
   - Click "Continua con Google"
   - **Verifica**: Redirect a Google login
   - Seleziona account Google
   - Approva permessi
   - **Verifica**: Redirect a dashboard AINSTEIN

### Test 11.2: Account Creation via OAuth
1. Usa un account Google che NON ha email già registrata
2. Login con Google
3. **Verifica**:
   - [ ] Nuovo account creato automaticamente
   - [ ] Email da Google salvato in AINSTEIN
   - [ ] Avatar Google importato (se implementato)
   - [ ] Tenant assegnato correttamente

### Test 11.3: Account Linking
1. Crea account con email+password: test@example.com
2. Logout
3. Login con Google usando STESSA email: test@example.com
4. **Verifica**:
   - [ ] Accounts collegati
   - [ ] Google ID salvato su user esistente
   - [ ] Prossimi login: sia email+password che Google funzionano

---

## FASE 12: ONBOARDING TOUR (Shepherd.js)

### Test 12.1: First-Time User Tour
1. **Simula nuovo utente**:
   - Crea nuovo tenant in admin
   - Crea nuovo user per questo tenant
   - Logout
   - Login con nuovo user
2. **Verifica tour**:
   - [ ] Al primo login, parte tour automaticamente
   - [ ] Tooltips Shepherd.js guidano l'utente
   - [ ] Step sequenziali: "Next", "Previous", "Skip"
   - [ ] Tour spiega: Dashboard, Campaigns, Content, API Keys

### Test 12.2: Tool-Specific Tours
1. Vai su Campaign Generator per la prima volta
2. **Verifica**:
   - [ ] Tour specifico per Campaigns parte
   - [ ] Spiega: form fields, come compilare, cosa aspettarsi
3. **Ripeti** per Content Generator

### Test 12.3: Skip & Reset Tour
1. Durante tour, click "Skip" o "Salta"
2. **Verifica**: Tour si chiude
3. Vai su Settings o Profile
4. **Cerca opzione**: "Reset Onboarding" o "Mostra tour di nuovo"
5. **Verifica**: Tour riparte se reset

---

## DOCUMENTAZIONE ERRORI

### Template Issue Report
Per ogni errore trovato, documenta così:

```
ISSUE #[numero]
--------------
Severity: [Critical/High/Medium/Low]
Category: [UI/Backend/Security/Performance]
Page: [URL esatto]

Descrizione:
[Cosa è successo]

Steps to Reproduce:
1. [Step 1]
2. [Step 2]
3. [Step 3]

Expected:
[Cosa dovrebbe succedere]

Actual:
[Cosa succede realmente]

Screenshot:
[Allega screenshot]

Console Errors:
[Copia errori da console browser]

Laravel Log Errors:
[Copia errori da storage/logs/laravel.log se disponibili]

Environment:
- Browser: Chrome 118
- OS: Windows 11
- User: admin@ainstein.com (superadmin)
- Timestamp: 2025-10-10 15:30:00
```

---

## CHECKLIST FINALE

### Features Testate:
- [ ] Login & Authentication
- [ ] Dashboard Overview
- [ ] Campaign Generator (RSA)
- [ ] Campaign Generator (PMAX)
- [ ] Content Generator (Pages)
- [ ] Content Generator (Prompts)
- [ ] Content Generator (Generations)
- [ ] API Keys Management
- [ ] CrewAI (Crews, Agents, Tasks, Executions)
- [ ] Admin Panel (Tenants, Users, Settings)
- [ ] UI/UX Elements
- [ ] Forms Validation
- [ ] Responsive Design (Mobile, Tablet)
- [ ] Security (Tenant Isolation, CSRF, XSS)
- [ ] Performance (Load Times, Console)
- [ ] Google OAuth
- [ ] Onboarding Tours

### Totale Issues Found: [numero]
- Critical: [numero]
- High: [numero]
- Medium: [numero]
- Low: [numero]

### Features Funzionanti al 100%: [lista]
### Features Parzialmente Funzionanti: [lista]
### Features Non Funzionanti: [lista]
### Features Non Implementate: [lista]

---

## EXPORT REPORT

**Dopo aver completato tutti i test**, crea un file report finale:

### File: `COMET_BROWSER_TEST_RESULTS.md`

Contenuto:
1. Summary esecutivo
2. Lista completa issues trovati (con screenshot)
3. Features status (funzionante/parziale/non funzionante)
4. Raccomandazioni immediate per fix
5. Performance metrics
6. Security findings
7. UX/UI observations

---

## NOTE FINALI PER COMET

- **Pazienza**: Alcune operazioni AI richiedono 10-30 secondi
- **Screenshot**: Fai screenshot liberamente, aiutano il debug
- **Console**: Tieni DevTools sempre aperto per catturare errori
- **Documenta tutto**: Meglio troppa informazione che troppo poca
- **Se blocchi**: Documenta l'errore e vai avanti con altri test
- **OpenAI**: Se non configurato, molti test AI falliranno (è normale, documentalo)

**Fine Prompt Test Completo**
