# AINSTEIN - Fix Login Issue in Production

**Ultimo Aggiornamento**: 2025-10-12
**Status**: Fix Applicato, Test in Corso 🔄

## PROBLEMA IDENTIFICATO
Il login in produzione non funziona correttamente a causa di un redirect loop. Dopo l'analisi del codice, ho identificato le seguenti cause principali:

## FIX APPLICATI (2025-10-12)

### ✅ Configurazioni HTTPS Session
**Applicate le seguenti configurazioni nel file .env di produzione:**
```env
SESSION_SECURE_COOKIE=true       # CRITICO per HTTPS
SESSION_HTTP_ONLY=true           # Sicurezza
SESSION_SAME_SITE=lax            # Protezione CSRF
SESSION_DOMAIN=null              # Per dominio principale
SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it
```
**Status**: ✅ Configurazione completata
**Backup**: `.env.backup.20251012` creato

### ⚠️ Issue Config Cache
**Problema identificato durante il fix:**
```
Error: Call to undefined method Closure::__set_state()
Location: bootstrap/cache/config.php
```
**Impact**: Non è possibile usare `config:cache` finché non viene risolto
**Workaround**: Usare solo `config:clear` per ora

## ROOT CAUSE ANALYSIS

### 1. **Configurazione Sessioni e Cookies**
Il problema principale è legato alla configurazione delle sessioni in ambiente HTTPS:

```env
# CONFIGURAZIONI CRITICHE MANCANTI IN PRODUZIONE
SESSION_SECURE_COOKIE=true       # OBBLIGATORIO per HTTPS
SESSION_HTTP_ONLY=true           # Sicurezza
SESSION_SAME_SITE=lax            # Protezione CSRF
SESSION_DOMAIN=.ainstein.it       # Importante per subdomain
APP_URL=https://ainstein.it       # DEVE essere HTTPS
```

### 2. **Database Sessions**
Il sistema usa `SESSION_DRIVER=database` ma potrebbe mancare la tabella sessions:

```bash
# Verificare in produzione
php artisan migrate:status | grep sessions
```

### 3. **Middleware Conflicts**
Il middleware `EnsureTenantAccess` potrebbe causare redirect loop se:
- L'utente non ha un tenant_id assegnato
- Il tenant non è attivo
- Il campo `is_active` dell'utente è false

## SOLUZIONE IMMEDIATA

### Step 1: Aggiornare .env in Produzione

```env
# File .env in produzione
APP_NAME=AINSTEIN
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ainstein.it

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nome_database_prod
DB_USERNAME=utente_prod
DB_PASSWORD=password_sicura

# Sessioni (CRITICO!)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null              # O .ainstein.it se hai subdomain
SESSION_SECURE_COOKIE=true       # CRITICO per HTTPS!
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Cache
CACHE_STORE=file                 # O redis se disponibile
QUEUE_CONNECTION=database

# Sanctum
SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it
```

### Step 2: Verificare Database

```bash
# SSH in produzione
cd /path/to/ainstein

# Verificare migrazioni
php artisan migrate:status

# Se manca la tabella sessions
php artisan migrate --force

# Pulire cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Ricreare cache ottimizzata
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 3: Debug Login

Aggiungi temporaneamente questo debug nel file `app/Http/Controllers/Auth/AuthController.php`:

```php
public function login(Request $request)
{
    \Log::info('🔐 LOGIN DEBUG', [
        'url' => $request->url(),
        'secure' => $request->secure(),
        'session_driver' => config('session.driver'),
        'session_domain' => config('session.domain'),
        'secure_cookie' => config('session.secure'),
        'app_url' => config('app.url'),
        'sanctum_domains' => config('sanctum.stateful'),
    ]);

    // ... resto del codice
}
```

### Step 4: Verificare Users e Tenants

```sql
-- Verificare in database produzione
SELECT u.id, u.email, u.tenant_id, u.is_active, t.status as tenant_status
FROM users u
LEFT JOIN tenants t ON u.tenant_id = t.id
WHERE u.email = 'email@test.com';

-- Se l'utente non ha tenant o non è attivo
UPDATE users SET is_active = 1 WHERE email = 'email@test.com';
UPDATE tenants SET status = 'active' WHERE id = (SELECT tenant_id FROM users WHERE email = 'email@test.com');
```

## DIFFERENZE TRA BRANCH

### Branch `sviluppo-tool` (attuale)
- **33 commits ahead** di master
- Features implementate:
  - CrewAI Integration completa
  - SEO Audit Agent
  - Campaign Generator
  - Fix Sanctum Token Expiration
  - Performance optimizations

### Branch `production`
- **2 commits unici** non in master:
  - Production deployment documentation
  - Laravel in root directory setup

### Branch `master`
- Base stabile senza le ultime features

## PIANO DI SINCRONIZZAZIONE

### Fase 1: Backup e Preparazione
```bash
# In produzione
cd /path/to/ainstein
git status
git stash  # Salvare modifiche locali

# Backup database
mysqldump -u user -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Fase 2: Merge Features in Master
```bash
# In locale
git checkout master
git pull origin master
git merge sviluppo-tool --no-ff -m "Merge sviluppo-tool: CrewAI, SEO Agent, Campaign Generator"
git push origin master
```

### Fase 3: Deploy in Produzione
```bash
# In produzione
git fetch origin
git checkout master
git pull origin master

# Migrazioni database
php artisan migrate --force

# Aggiornare dipendenze
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# Clear e rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## CHECKLIST PRE-PRODUZIONE

- [ ] Verificare APP_URL è HTTPS
- [ ] SESSION_SECURE_COOKIE=true
- [ ] Database migrations complete
- [ ] Tabella sessions esiste
- [ ] Users hanno tenant_id valido
- [ ] Tenants sono active
- [ ] Cache cleared and rebuilt
- [ ] Logs monitored durante test
- [ ] Backup database effettuato

## MONITORING POST-DEPLOY

```bash
# Monitor logs
tail -f storage/logs/laravel.log

# Verificare health
curl https://ainstein.it/health

# Test login
# 1. Aprire browser in incognito
# 2. Andare su https://ainstein.it/login
# 3. Inserire credenziali
# 4. Verificare redirect a /dashboard
```

## CONTATTI EMERGENZA

Se il problema persiste dopo questi fix:
1. Verificare logs del web server (Apache/Nginx)
2. Controllare configurazione SSL/HTTPS
3. Verificare reverse proxy se presente
4. Controllare firewall/security groups

## FEATURES DA TESTARE POST-FIX

1. **CrewAI Integration**
   - Dashboard crews
   - Esecuzione workflows
   - Template management

2. **SEO Audit Agent**
   - Creazione progetti
   - Analisi SEO
   - Report generation

3. **Campaign Generator**
   - RSA campaigns
   - PMAX campaigns
   - Export functionality

## NOTE IMPORTANTI

- **MAI** fare deploy diretto su production branch
- **SEMPRE** testare in staging prima
- **BACKUP** database prima di ogni migrazione
- **MONITORARE** logs durante e dopo deploy