# 📖 Guida Utilizzo Piattaforma Ainstein

## 🚀 Panoramica

Ainstein è una piattaforma SaaS intelligente per la gestione e ottimizzazione delle campagne di marketing attraverso l'intelligenza artificiale. La piattaforma offre un sistema multi-tenant completo con gestione utenti, pagine, prompt e generazione contenuti.

## 🔧 Requisiti di Sistema

- **PHP**: 8.1 o superiore
- **Composer**: Latest version
- **Node.js**: 18+ con npm
- **Database**: MySQL/MariaDB
- **Server Web**: Laravel Development Server o Apache/Nginx

## 📦 Installazione e Setup

### 1. Installazione Dipendenze

```bash
# Installa dipendenze PHP
composer install

# Installa dipendenze JavaScript
npm install
```

### 2. Configurazione Ambiente

```bash
# Copia file di configurazione
cp .env.example .env

# Genera chiave applicazione
php artisan key:generate

# Configura database nel file .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ainstein_database
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database Setup

```bash
# Esegui migrazioni
php artisan migrate

# Popola database con dati di esempio (opzionale)
php artisan db:seed
```

### 4. Avvio Servizi

```bash
# Avvia server Laravel (terminale 1)
php artisan serve --host=127.0.0.1 --port=8080

# Avvia watcher asset frontend (terminale 2)
npm run dev

# Avvia queue worker per background jobs (terminale 3)
php artisan queue:work
```

## 🎯 Utilizzo della Piattaforma

### 🔐 Accesso e Registrazione

#### Registrazione Nuovo Utente

1. **Accedi alla pagina di registrazione**: `http://127.0.0.1:8080/register`

2. **Compila il form con i seguenti campi obbligatori**:
   - Nome completo
   - Email aziendale
   - Password (minimo 8 caratteri)
   - Conferma password
   - **Nome azienda** (campo obbligatorio)
   - Accettazione termini e condizioni

3. **Processo automatico**:
   - Creazione account utente
   - Creazione tenant aziendale automatica
   - Associazione utente al tenant
   - Redirect automatico al dashboard

#### Login Utente Esistente

1. **Accedi alla pagina di login**: `http://127.0.0.1:8080/login`

2. **Credenziali richieste**:
   - Email
   - Password

3. **Opzioni alternative**:
   - **Login Social**: Google OAuth disponibile
   - **Password dimenticata**: Reset via email

### 🏠 Dashboard Principale

Una volta autenticato, l'utente accede al dashboard principale che include:

#### Sezioni Principali

1. **Analytics Dashboard**
   - Statistiche utilizzo
   - Metriche performance
   - Grafici trend

2. **Gestione Pagine**
   - Lista pagine del tenant
   - Creazione nuove pagine
   - Modifica pagine esistenti
   - Gestione SEO e keywords

3. **Gestione Prompt**
   - Libreria prompt personalizzati
   - Creazione prompt AI
   - Categorizzazione prompt
   - Attivazione/disattivazione

4. **Generazione Contenuti**
   - Processo guidato generazione
   - Selezione prompt e pagine
   - Preview contenuti generati
   - Storico generazioni

5. **Gestione API Keys**
   - Creazione chiavi API
   - Monitoraggio utilizzo
   - Controllo permessi
   - Revoca accessi

### 📊 Funzionalità API

#### Endpoint Principali

**Base URL**: `http://127.0.0.1:8080/api/v1`

#### Autenticazione API

```bash
# Login API per ottenere token
curl -X POST "http://127.0.0.1:8080/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

#### Endpoint Disponibili

1. **Autenticazione**
   - `POST /auth/login` - Login
   - `POST /auth/logout` - Logout
   - `GET /auth/me` - Info utente corrente

2. **Gestione Tenant**
   - `GET /tenants` - Lista tenant
   - `POST /tenants` - Crea tenant
   - `PUT /tenants/{id}` - Modifica tenant

3. **Gestione Pagine**
   - `GET /pages` - Lista pagine
   - `POST /pages` - Crea pagina
   - `PUT /pages/{id}` - Modifica pagina
   - `DELETE /pages/{id}` - Elimina pagina

4. **Gestione Prompt**
   - `GET /prompts` - Lista prompt
   - `POST /prompts` - Crea prompt
   - `PUT /prompts/{id}` - Modifica prompt

5. **Utilità**
   - `GET /utils/health` - Stato sistema
   - `GET /utils/stats` - Statistiche tenant
   - `GET /utils/tenant` - Info tenant corrente

### 🔒 Sicurezza e Isolamento

#### Multi-Tenancy

- **Isolamento completo**: Ogni tenant vede solo i propri dati
- **Middleware di sicurezza**: EnsureTenantAccess su tutte le route protette
- **Validazione accessi**: Controllo permessi automatico

#### Autenticazione

- **Laravel Sanctum**: Token-based authentication
- **CSRF Protection**: Protezione su tutte le form
- **Social Login**: Integrazione OAuth Google/Facebook
- **Password Reset**: Sistema recupero password sicuro

## 🛠️ Manutenzione e Monitoraggio

### Health Check

```bash
# Verifica stato applicazione
curl http://127.0.0.1:8080/api/health

# Risposta attesa:
{
  "status": "healthy",
  "timestamp": "2025-09-26T18:00:00.000Z",
  "version": "1.0.0",
  "checks": {
    "database": true,
    "redis": "n/a",
    "storage": true
  }
}
```

### Cache Management

```bash
# Pulisci cache applicazione
php artisan cache:clear

# Pulisci cache configurazione
php artisan config:clear

# Pulisci cache route
php artisan route:clear

# Pulisci cache view
php artisan view:clear
```

### Queue Management

```bash
# Verifica job in coda
php artisan queue:work

# Riavvia worker in caso di modifiche
php artisan queue:restart

# Monitor job falliti
php artisan queue:failed
```

## 🚨 Troubleshooting

### Problemi Comuni

#### 1. Server non si avvia
```bash
# Verifica porta disponibile
netstat -an | grep :8080

# Cambia porta se necessario
php artisan serve --port=8081
```

#### 2. Errori database
```bash
# Verifica connessione database
php artisan tinker
DB::connection()->getPdo();
```

#### 3. Asset non caricano
```bash
# Ricompila asset
npm run build

# Modalità sviluppo
npm run dev
```

#### 4. Problemi permission
```bash
# Su Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Log di Sistema

```bash
# Visualizza log Laravel
tail -f storage/logs/laravel.log

# Visualizza log server
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

## 🎯 Best Practices

### Sviluppo

1. **Utilizza sempre migrazioni** per modifiche database
2. **Testa API endpoints** prima del deploy
3. **Mantieni cache pulita** durante sviluppo
4. **Utilizza queue** per processi lunghi

### Produzione

1. **Configura cache** Redis per performance
2. **Utilizza HTTPS** per sicurezza
3. **Monitora log** regolarmente
4. **Backup database** automatici
5. **Utilizza environment variables** per configurazioni sensibili

### Sicurezza

1. **Aggiorna dipendenze** regolarmente
2. **Utilizza token API** con scadenza
3. **Implementa rate limiting** su API
4. **Valida sempre input** utente
5. **Utilizza CSRF protection** su form

## 📞 Supporto

Per assistenza tecnica o domande sulla piattaforma:

- **Email**: support@ainstein.com
- **Documentazione API**: `http://127.0.0.1:8080/api/docs`
- **GitHub Issues**: Report problemi sul repository

---

**Versione Piattaforma**: 1.0.0
**Ultimo Aggiornamento**: Settembre 2025
**Ambiente Testato**: PHP 8.1, Laravel 11, TailwindCSS 4.1.12