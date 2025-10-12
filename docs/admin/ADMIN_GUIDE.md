# 📘 Guida Super Admin - AINSTEIN Platform

**Versione**: 1.0.0
**Data**: 10 Ottobre 2025
**Target**: Super Amministratori della piattaforma AINSTEIN

---

## 📋 Indice

1. [Overview](#overview)
2. [Accesso Panel Admin](#accesso-panel-admin)
3. [Configurazione Social Login](#configurazione-social-login)
4. [Configurazione API Integrations](#configurazione-api-integrations)
5. [Gestione Tenant](#gestione-tenant)
6. [Monitoring e Analytics](#monitoring-e-analytics)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)

---

## 🎯 Overview

Come Super Admin di AINSTEIN, hai accesso completo alla gestione della piattaforma multi-tenant. Le tue responsabilità includono:

- Configurazione delle impostazioni globali della piattaforma
- Gestione delle credenziali OAuth per Social Login e API
- Creazione e gestione dei tenant
- Monitoring delle performance e utilizzo
- Configurazione dei limiti e delle policy

## 🔐 Accesso Panel Admin

### URL di Accesso
```
https://tuodominio.com/admin
```

### Credenziali Default
- **Email**: admin@ainstein.com
- **Password**: password

> ⚠️ **IMPORTANTE**: Cambia immediatamente la password di default dopo il primo accesso!

### Dashboard Admin
Una volta effettuato l'accesso, avrai accesso a:
- **Dashboard**: Overview generale della piattaforma
- **Tenants**: Gestione dei workspace
- **Users**: Gestione utenti globale
- **Platform Settings**: Configurazioni globali
- **Analytics**: Statistiche e metriche

## 🌐 Configurazione Social Login

La configurazione del Social Login è separata dalle API Integrations per maggiore chiarezza e sicurezza.

### Accesso alla Configurazione

1. Dal menu laterale, clicca su **Platform Settings**
2. Seleziona il tab **Social Login** (primo tab)
3. Vedrai due sezioni: Google e Facebook

### Configurazione Google Social Login

#### Step 1: Creazione App Google
1. Vai su [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuovo progetto o seleziona uno esistente
3. Abilita **Google+ API**
4. Vai su **APIs & Services** → **Credentials**
5. Crea **OAuth 2.0 Client ID** di tipo "Web application"

#### Step 2: Configurazione Callback URLs
Aggiungi questi URLs nelle impostazioni Google:

**Authorized JavaScript origins:**
```
https://tuodominio.com
http://localhost:8000
```

**Authorized redirect URIs:**
```
https://tuodominio.com/auth/google/callback
http://localhost:8000/auth/google/callback
```

#### Step 3: Inserimento Credenziali in AINSTEIN
1. Nel tab **Social Login** di Platform Settings
2. Sezione **Google OAuth Configuration**:
   - **Google Social Client ID**: [incolla il Client ID]
   - **Google Social Client Secret**: [incolla il Client Secret]
3. Clicca **Save Google Settings**

#### Step 4: Copia Callback URL
Usa il pulsante 📋 accanto al campo "Callback URL" per copiare l'URL esatto da configurare in Google.

### Configurazione Facebook Social Login

#### Step 1: Creazione App Facebook
1. Vai su [Facebook Developers](https://developers.facebook.com/)
2. Crea una nuova app di tipo "Consumer"
3. Aggiungi il prodotto **Facebook Login**

#### Step 2: Configurazione Facebook Login Settings
Nel pannello Facebook Developers:
- **Valid OAuth Redirect URIs:**
  ```
  https://tuodominio.com/auth/facebook/callback
  http://localhost:8000/auth/facebook/callback
  ```
- **Client OAuth Login**: Yes
- **Web OAuth Login**: Yes

#### Step 3: Inserimento Credenziali in AINSTEIN
1. Nel tab **Social Login** di Platform Settings
2. Sezione **Facebook OAuth Configuration**:
   - **Facebook Social App ID**: [incolla l'App ID]
   - **Facebook Social App Secret**: [incolla l'App Secret]
3. Clicca **Save Facebook Settings**

### Verifica Configurazione

Dopo aver salvato le credenziali:
1. Logout dal pannello admin
2. Vai alla pagina di login
3. Dovresti vedere i pulsanti "Login con Google" e/o "Login con Facebook"
4. Testa il login con un account di prova

## 🔌 Configurazione API Integrations

Le API Integrations sono separate dal Social Login e si trovano nel secondo tab.

### Accesso alla Configurazione

1. Dal menu laterale, clicca su **Platform Settings**
2. Seleziona il tab **API Integrations** (secondo tab)
3. Vedrai le sezioni per: Google Ads, Facebook Ads, Google Search Console

### Google Ads API

Per configurare Google Ads API:
1. Crea un progetto in Google Cloud Console
2. Abilita **Google Ads API**
3. Crea credenziali OAuth 2.0 specifiche per l'API
4. Inserisci in AINSTEIN:
   - **Google Ads Client ID**
   - **Google Ads Client Secret**
   - **Google Ads Developer Token** (se richiesto)

### Facebook Ads API

Per configurare Facebook Ads API:
1. Crea un'app Business in Facebook Developers
2. Richiedi accesso a **Marketing API**
3. Genera un Access Token di lunga durata
4. Inserisci in AINSTEIN:
   - **Facebook Ads App ID**
   - **Facebook Ads App Secret**
   - **Facebook Ads Access Token**

### Google Search Console API

Per configurare Search Console API:
1. Abilita **Search Console API** nel progetto Google Cloud
2. Crea credenziali OAuth 2.0 dedicate
3. Inserisci in AINSTEIN:
   - **Search Console Client ID**
   - **Search Console Client Secret**

## 👥 Gestione Tenant

### Sistema a Due Livelli

AINSTEIN implementa un sistema di configurazione a due livelli:

1. **Livello 1 - Super Admin (Tu)**
   - Configuri credenziali globali che servono da fallback
   - Tutti i tenant possono usare queste credenziali se non hanno le proprie

2. **Livello 2 - Tenant Admin**
   - Ogni tenant può configurare le proprie credenziali OAuth
   - Le credenziali del tenant hanno priorità su quelle globali

### Vantaggi del Sistema a Due Livelli

- **Onboarding Rapido**: Nuovi tenant possono usare subito il social login senza configurazione
- **Flessibilità**: Tenant enterprise possono usare le proprie app OAuth
- **Controllo Centralizzato**: Puoi gestire e monitorare tutto centralmente
- **Isolamento**: Ogni tenant mantiene la propria privacy e controllo

### Creazione Nuovo Tenant

1. Vai su **Tenants** nel menu laterale
2. Clicca **Create New Tenant**
3. Compila:
   - **Name**: Nome del tenant
   - **Domain**: Dominio personalizzato (opzionale)
   - **Plan**: Piano di abbonamento
4. Il tenant erediterà automaticamente le configurazioni OAuth globali

## 📊 Monitoring e Analytics

### Dashboard Metrics

Dalla dashboard admin puoi monitorare:
- **Login Social**: Numero di login per provider (Google/Facebook)
- **Tenant Usage**: Quali tenant usano configurazioni proprie vs globali
- **Error Rate**: Errori di autenticazione OAuth
- **API Usage**: Utilizzo delle API integrations

### Query Utili

Per analisi avanzate, puoi eseguire queste query:

```sql
-- Tenant che usano configurazioni OAuth proprie
SELECT t.name, COUNT(top.id) as custom_providers
FROM tenants t
JOIN tenant_oauth_providers top ON t.id = top.tenant_id
WHERE top.enabled = 1
GROUP BY t.id;

-- Login social negli ultimi 30 giorni
SELECT provider, COUNT(*) as login_count
FROM oauth_logins
WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY provider;

-- Tenant senza configurazione OAuth
SELECT name FROM tenants
WHERE id NOT IN (
    SELECT DISTINCT tenant_id
    FROM tenant_oauth_providers
    WHERE enabled = 1
);
```

## 🔧 Troubleshooting

### Problemi Comuni e Soluzioni

#### I pulsanti social login non appaiono

**Causa**: Nessuna configurazione OAuth valida
**Soluzione**:
1. Verifica di aver salvato le credenziali in Platform Settings → Social Login
2. Controlla che almeno un provider sia configurato
3. Verifica i log in `storage/logs/laravel.log`

#### Errore "Invalid Client"

**Causa**: Client ID o Secret errati
**Soluzione**:
1. Verifica le credenziali nel provider (Google/Facebook)
2. Assicurati di usare le credenziali corrette (Social vs API)
3. Ricontrolla di non avere spazi extra quando copi/incolli

#### Errore "Redirect URI Mismatch"

**Causa**: Il callback URL non corrisponde
**Soluzione**:
1. Copia l'URL esatto dal campo "Callback URL" in AINSTEIN
2. Aggiungi questo URL nelle impostazioni del provider OAuth
3. Attendi 5-10 minuti per la propagazione

#### Tenant non può fare override delle configurazioni

**Causa**: Permessi o configurazione errata
**Soluzione**:
1. Verifica che il tenant admin abbia i permessi corretti
2. Controlla che la tabella `tenant_oauth_providers` esista
3. Verifica i log per errori di encryption/decryption

### Log Files

I log importanti si trovano in:
- `storage/logs/laravel.log` - Log generale applicazione
- `storage/logs/oauth.log` - Log specifici OAuth (se configurato)
- Database: tabella `activity_log` per audit trail

## 💡 Best Practices

### Sicurezza

1. **Rotazione Credenziali**: Cambia le credenziali OAuth ogni 6 mesi
2. **Monitoraggio**: Controlla regolarmente i log per tentativi di accesso sospetti
3. **Backup**: Mantieni backup delle configurazioni OAuth
4. **Encryption Key**: Non cambiare mai APP_KEY senza prima fare backup

### Performance

1. **Cache**: Le configurazioni OAuth sono cachate per 24 ore
2. **Rate Limiting**: Implementa rate limiting sui callback OAuth
3. **Monitoring**: Usa tool come New Relic o Datadog per monitoraggio real-time

### Compliance

1. **GDPR**: Assicurati che le app OAuth richiedano solo permessi necessari
2. **Privacy Policy**: Aggiorna la privacy policy quando aggiungi nuovi provider
3. **Data Retention**: Implementa policy di retention per i log OAuth

### Manutenzione

1. **Test Periodici**: Testa mensilmente che il social login funzioni
2. **Documentazione**: Mantieni documentate tutte le configurazioni custom
3. **Comunicazione**: Notifica i tenant prima di modifiche alle configurazioni globali
4. **Staging**: Testa sempre le modifiche in ambiente di staging

## 🚀 Funzionalità Avanzate

### Multi-Domain Support

Se gestisci più domini:
1. Aggiungi tutti i domini nei callback URLs dei provider
2. Configura `APP_URL` dinamicamente basato sul dominio
3. Usa subdomain wildcards dove supportato

### White-Label OAuth

Per tenant enterprise che vogliono complete white-label:
1. Permetti loro di configurare proprie app OAuth
2. Nascondi i loghi dei provider social se richiesto
3. Customizza i messaggi di consent

### API Rate Limiting

Per proteggere le API:
```php
// config/api.php
'rate_limits' => [
    'oauth_callback' => '60,1', // 60 richieste al minuto
    'api_integration' => '1000,60', // 1000 richieste all'ora
]
```

## 📞 Supporto

### Risorse Interne
- Documentazione tecnica: `/docs/oauth/`
- API Reference: `/api/documentation`
- Change Log: `/CHANGELOG.md`

### Risorse Esterne
- [Laravel Socialite Docs](https://laravel.com/docs/socialite)
- [Google OAuth Guide](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login Docs](https://developers.facebook.com/docs/facebook-login)

### Contatti di Emergenza
- **DevOps Team**: devops@ainstein.com
- **Security Team**: security@ainstein.com
- **On-Call Support**: +39 XXX XXXXXXX

---

**Note**: Questa guida è riservata ai Super Admin. Non condividere con utenti non autorizzati.

**Ultimo aggiornamento**: 10 Ottobre 2025
**Prossima revisione**: Gennaio 2026