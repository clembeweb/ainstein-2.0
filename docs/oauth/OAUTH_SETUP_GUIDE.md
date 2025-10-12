# 🔐 Guida Configurazione OAuth Social Login - AINSTEIN

## 📋 Sommario

Questa guida descrive come configurare il login social (Google e Facebook) per il progetto AINSTEIN, che supporta OAuth multi-tenant dove ogni workspace può avere le proprie credenziali OAuth.

## 🏗️ Architettura Implementata

### Sistema Multi-Tenant OAuth

Il sistema implementato supporta due modalità:

1. **Configurazione Globale**: Credenziali OAuth condivise per tutta la piattaforma (dalla tabella `platform_settings`)
2. **Configurazione Per-Tenant**: Ogni tenant può configurare le proprie OAuth app (dalla tabella `tenant_oauth_providers`)

### Componenti Principali

- **Database**: `tenant_oauth_providers` - Memorizza le configurazioni OAuth per ogni tenant
- **Service**: `TenantOAuthService` - Gestisce la configurazione dinamica di Socialite
- **Controller**: `OAuthSettingsController` - Gestisce l'interfaccia admin per OAuth
- **Encryption**: Client ID e Secret sono criptati nel database usando Laravel Crypt

## 🚀 Configurazione Iniziale

### 1. Prerequisiti

- Laravel Socialite installato (già presente nel progetto)
- Database migrato con `php artisan migrate`
- SSL attivo (HTTPS) per il dominio di produzione

### 2. Variabili d'Ambiente (.env)

```env
# App URL (importante per i callback)
APP_URL=https://tuodominio.com

# Fallback OAuth credentials (opzionale)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
```

## 📱 Configurazione Google OAuth

### Passo 1: Creare un Progetto Google Cloud

1. Vai su [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuovo progetto o seleziona uno esistente
3. Abilita "Google+ API" dalla libreria API

### Passo 2: Creare le Credenziali OAuth 2.0

1. Vai su **APIs & Services** → **Credentials**
2. Clicca **Create Credentials** → **OAuth client ID**
3. Scegli **Web application** come tipo
4. Configura:
   - **Name**: AINSTEIN Social Login
   - **Authorized JavaScript origins**:
     ```
     https://tuodominio.com
     http://localhost:8000 (per sviluppo)
     ```
   - **Authorized redirect URIs**:
     ```
     https://tuodominio.com/auth/google/callback
     http://localhost:8000/auth/google/callback (per sviluppo)
     ```
5. Salva e copia **Client ID** e **Client Secret**

### Passo 3: Configurare nel Dashboard AINSTEIN

1. Accedi come amministratore del tenant
2. Vai su **Dashboard** → **Settings** → **OAuth Settings**
3. Nella sezione Google:
   - Incolla il Client ID
   - Incolla il Client Secret
   - Clicca "Salva Configurazione"
   - Clicca "Test" per verificare
   - Abilita con il checkbox "Abilita login con Google"

## 📘 Configurazione Facebook OAuth

### Passo 1: Creare un'App Facebook

1. Vai su [Facebook Developers](https://developers.facebook.com/)
2. Clicca **My Apps** → **Create App**
3. Scegli **Consumer** come tipo di app
4. Inserisci i dettagli:
   - **App Name**: AINSTEIN Login
   - **App Contact Email**: tua@email.com
   - **App Purpose**: Autenticazione utenti

### Passo 2: Configurare Facebook Login

1. Nella dashboard dell'app, aggiungi il prodotto **Facebook Login**
2. Vai su **Facebook Login** → **Settings**
3. Configura:
   - **Client OAuth Login**: Yes
   - **Web OAuth Login**: Yes
   - **Valid OAuth Redirect URIs**:
     ```
     https://tuodominio.com/auth/facebook/callback
     http://localhost:8000/auth/facebook/callback (per sviluppo)
     ```
   - **Allowed Domains for the JavaScript SDK**: tuodominio.com
4. Salva le modifiche

### Passo 3: Ottenere le Credenziali

1. Vai su **Settings** → **Basic**
2. Copia **App ID** e **App Secret**
3. Assicurati che l'app sia in modalità **Live** (non Development) per produzione

### Passo 4: Configurare nel Dashboard AINSTEIN

1. Accedi come amministratore del tenant
2. Vai su **Dashboard** → **Settings** → **OAuth Settings**
3. Nella sezione Facebook:
   - Incolla l'App ID (come Client ID)
   - Incolla l'App Secret (come Client Secret)
   - Clicca "Salva Configurazione"
   - Clicca "Test" per verificare
   - Abilita con il checkbox "Abilita login con Facebook"

## 🔧 Configurazione Multi-Tenant

### Sistema a Due Livelli

AINSTEIN implementa un sistema di configurazione OAuth a due livelli per massima flessibilità:

#### **Livello 1: Super Admin (Configurazione Globale)**
Il Super Admin può configurare credenziali OAuth globali che servono come fallback per tutti i tenant:

1. Accedi al pannello Super Admin: `/admin`
2. Vai su **Platform Settings** nel menu laterale
3. Seleziona il tab **Social Login** (separato da API Integrations)
4. Configura le credenziali per:
   - **Google Social Login**: `google_social_client_id` e `google_social_client_secret`
   - **Facebook Social Login**: `facebook_social_app_id` e `facebook_social_app_secret`
5. Usa il pulsante di copia per ottenere i callback URLs da configurare nei provider
6. Salva le configurazioni

**Nota**: Queste credenziali sono separate da quelle per le API (Google Ads, Facebook Ads, Search Console) che si trovano nel tab "API Integrations".

#### **Livello 2: Tenant Admin (Override Configurazione)**
Ogni tenant può configurare le proprie credenziali OAuth che hanno priorità su quelle globali:

1. Accedi al dashboard del tenant
2. Vai su **Settings** → **OAuth Settings**
3. Configura Google e/o Facebook con le credenziali specifiche del tenant
4. I callback URL saranno:
   - Google: `https://tuodominio.com/auth/google/callback`
   - Facebook: `https://tuodominio.com/auth/facebook/callback`
5. Testa la configurazione con il pulsante "Test"
6. Abilita il provider con il checkbox

### Ordine di Priorità

Il sistema risolve le credenziali OAuth nel seguente ordine:

1. **Credenziali Tenant** (massima priorità)
   - Se il tenant ha configurato e abilitato il provider
   - Usa le credenziali specifiche del tenant dalla tabella `tenant_oauth_providers`

2. **Credenziali Globali** (fallback)
   - Se il tenant non ha configurazione propria
   - Usa le credenziali del Super Admin dalla tabella `platform_settings`
   - Campi utilizzati: `google_social_*` e `facebook_social_*`

3. **Non Disponibile**
   - Se nessuna configurazione è presente
   - Il provider non viene mostrato nella pagina di login

### Differenza tra Social Login e API Integrations

**Social Login** (per autenticazione utenti):
- Google OAuth: `google_social_client_id`, `google_social_client_secret`
- Facebook OAuth: `facebook_social_app_id`, `facebook_social_app_secret`
- Usato per: Login utenti, registrazione con social

**API Integrations** (per servizi esterni):
- Google Ads: `google_ads_client_id`, `google_ads_client_secret`
- Facebook Ads: `facebook_ads_app_id`, `facebook_ads_app_secret`
- Google Search Console: `google_console_client_id`, `google_console_client_secret`
- Usato per: Accesso API, import dati, automazioni

## 🛡️ Sicurezza

### Best Practices Implementate

1. **Encryption**: Tutti i Client ID e Secret sono criptati nel database
2. **HTTPS Required**: OAuth funziona solo su connessioni sicure in produzione
3. **State Validation**: Laravel Socialite gestisce automaticamente lo state CSRF
4. **Scope Limitation**: Richiediamo solo email e profilo base

### Raccomandazioni

- Non condividere mai Client Secret
- Usa app OAuth separate per sviluppo e produzione
- Ruota periodicamente i Secret
- Monitora i log di accesso OAuth
- Limita i domini autorizzati al minimo necessario

## 🧪 Testing

### Test Manuale

1. Configura le credenziali OAuth nel dashboard
2. Usa il pulsante "Test" per verificare la configurazione
3. Logout e prova il login social dalla pagina di login
4. Verifica che l'utente venga creato/autenticato correttamente

### Test Automatici

Esegui i test con:

```bash
php artisan test --filter=SocialAuth
```

## 🐛 Troubleshooting

### Errore "Invalid Client"

- **Causa**: Client ID o Secret errati
- **Soluzione**: Verifica le credenziali e riconfigurale

### Errore "Redirect URI Mismatch"

- **Causa**: Il callback URL non corrisponde a quello configurato nell'app OAuth
- **Soluzione**:
  1. Copia il callback URL dal dashboard AINSTEIN
  2. Aggiungi esattamente questo URL nelle impostazioni OAuth (Google/Facebook)
  3. Aspetta 5 minuti per la propagazione

### Login Social Non Visibile

- **Causa**: Nessuna configurazione OAuth attiva
- **Soluzione**:
  1. Configura almeno un provider OAuth
  2. Assicurati che sia abilitato (checkbox attivo)
  3. Verifica con il pulsante "Test"

### Errore "App Not Live"

- **Causa**: App Facebook ancora in modalità Development
- **Soluzione**: Vai su Facebook Developers e metti l'app in modalità Live

### Errore di Decryption

- **Causa**: APP_KEY cambiata dopo aver salvato le credenziali
- **Soluzione**: Riconfigura le credenziali OAuth

## 📊 Monitoring

### Log di Sistema

I log OAuth sono salvati in:
- `storage/logs/laravel.log` - Log generali
- Database: `tenant_oauth_providers.test_status` - Stato ultimo test

### Metriche da Monitorare

- Numero di login social riusciti/falliti
- Provider più utilizzato
- Errori di configurazione ricorrenti

## 🔄 Manutenzione

### Rotazione Credenziali

1. Genera nuove credenziali nell'app OAuth (Google/Facebook)
2. Aggiorna nel dashboard AINSTEIN
3. Test della nuova configurazione
4. Rimuovi le vecchie credenziali dall'app OAuth

### Backup

Assicurati di backuppare:
- Tabella `tenant_oauth_providers`
- File `.env` con credenziali di fallback
- Configurazioni delle app OAuth esterne

## 📚 Risorse Utili

- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [Google OAuth 2.0 Guide](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Facebook Login Documentation](https://developers.facebook.com/docs/facebook-login/web)
- [OAuth 2.0 Security Best Practices](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics)

## 🆘 Supporto

Per problemi con la configurazione OAuth:

1. Controlla questa documentazione
2. Verifica i log in `storage/logs/`
3. Testa la configurazione dal dashboard
4. Contatta il supporto tecnico con:
   - Screenshot dell'errore
   - Log correlati
   - Tenant ID e Provider problematico

---

**Ultimo aggiornamento**: 10 Ottobre 2025
**Versione**: 1.1.0
**Modifiche**: Aggiunta configurazione Super Admin a due livelli, separazione Social Login da API Integrations