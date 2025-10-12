# 🚀 DEPLOY IN PRODUZIONE - AINSTEIN.IT

## ✅ STATO ATTUALE

### LOCALE: TUTTO FUNZIONANTE
- ✅ Login: Risolto (TenantOAuthProvider fix applicato)
- ✅ Campaign Generator: Completamente operativo
- ✅ Test: Tutti passati
- ✅ Browser: http://127.0.0.1:8001 funzionante

### PRODUZIONE: PRONTO PER DEPLOY
- 🔧 Login: Da fixare (script pronto)
- 🔧 Campaign Generator: Da deployare
- 📦 Script completo: PRONTO

---

## 🎯 COSA È STATO RISOLTO

### 1. LOGIN FIX
**Problema**: Tabella `tenant_o_auth_providers` vs `tenant_oauth_providers`
**Soluzione**: Aggiunto `protected $table = 'tenant_oauth_providers';` nel model
**File**: `app/Models/TenantOAuthProvider.php:13`

### 2. CAMPAIGN GENERATOR
**Scoperta**: NON era rotto! Era già completamente implementato
**Componenti verificati**:
- ✅ Controller (359 righe)
- ✅ Service AI (447 righe)
- ✅ Views complete (index, create, show, edit)
- ✅ Routes (9 endpoints)
- ✅ Models e relazioni
- ✅ Export CSV/Google Ads
- ✅ Multi-tenancy
- ✅ Token tracking

---

## 🚀 DEPLOYMENT IN 3 MODI

### MODO 1: SCRIPT AUTOMATICO (CONSIGLIATO)

```bash
# 1. SSH in produzione
ssh user@ainstein.it

# 2. Vai in directory Laravel
cd /var/www/ainstein  # o il tuo path

# 3. Upload script
# Dal tuo PC:
scp C:\laragon\www\ainstein-3\DEPLOY_PRODUZIONE_COMPLETO.sh user@ainstein.it:/var/www/ainstein/

# 4. Esegui
chmod +x DEPLOY_PRODUZIONE_COMPLETO.sh
./DEPLOY_PRODUZIONE_COMPLETO.sh
```

**Cosa fa lo script:**
- ✅ Backup automatico (.env, database, model)
- ✅ Fix login (model + sessioni HTTPS)
- ✅ Deploy Campaign Generator
- ✅ Migrations database
- ✅ Clear e rebuild cache
- ✅ Build assets NPM
- ✅ Set permissions
- ✅ Verifica finale

**Tempo: 5-10 minuti**

---

### MODO 2: MANUALE STEP-BY-STEP

```bash
# SSH in produzione
ssh user@ainstein.it
cd /var/www/ainstein

# 1. BACKUP
cp .env .env.backup.$(date +%Y%m%d)
mysqldump -u user -p database > backup.sql

# 2. FIX MODEL
nano app/Models/TenantOAuthProvider.php
# Aggiungi dopo "class TenantOAuthProvider extends Model {":
#     protected $table = 'tenant_oauth_providers';
# Salva: CTRL+O, ENTER, CTRL+X

# 3. FIX .ENV
nano .env
# Aggiungi/modifica:
APP_URL=https://ainstein.it
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it
OPENAI_API_KEY=sk-proj-YOUR-REAL-KEY
# Salva

# 4. PULL CODE
git pull origin master

# 5. DEPENDENCIES
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# 6. DATABASE
php artisan migrate --force

# 7. CACHE
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. PERMISSIONS
chmod -R 755 storage bootstrap/cache
```

**Tempo: 10-15 minuti**

---

### MODO 3: DAMMI ACCESSO SSH

Forniscimi:
```
Host: ainstein.it (o IP server)
User: ?
Password/Key: ?
Path: /var/www/ainstein (o altro?)
```

Eseguo tutto io in 5 minuti.

---

## 📋 DOPO IL DEPLOY

### 1. TEST LOGIN
```
1. Browser INCOGNITO
2. https://ainstein.it/login
3. Inserisci credenziali
4. Verifica accesso dashboard
```

### 2. TEST CAMPAIGN GENERATOR
```
1. Vai su: https://ainstein.it/dashboard/campaigns
2. Click "Nuova Campagna"
3. Compila form (RSA o PMAX)
4. Genera assets
5. Verifica qualità contenuti
6. Testa export CSV
```

### 3. MONITORAGGIO
```bash
# Logs in real-time
tail -f storage/logs/laravel.log

# Check errori
grep ERROR storage/logs/laravel.log

# Test health
curl https://ainstein.it/health
```

---

## ⚠️ CONFIGURAZIONE OPENAI

**CRITICO PER PRODUZIONE:**

Devi avere una vera API key OpenAI:

```bash
# Nel .env di produzione
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxx
OPENAI_DEFAULT_MODEL=gpt-4o-mini
```

**Come ottenerla:**
1. Vai su: https://platform.openai.com/api-keys
2. Crea nuovo API key
3. Copia la chiave (inizia con `sk-proj-`)
4. Aggiungi nel .env produzione

**Senza questa chiave**, il Campaign Generator userà il mock (dati finti).

---

## 🆘 TROUBLESHOOTING

### Login non funziona ancora
```bash
# Verifica model
grep "protected \$table" app/Models/TenantOAuthProvider.php

# Verifica .env
grep "SESSION_SECURE_COOKIE" .env

# Test tabella
php artisan tinker
>>> DB::table('tenant_oauth_providers')->count();
```

### Campaign Generator non genera
```bash
# Check OpenAI key
php artisan tinker
>>> config('ai.openai_api_key')

# Test service
php test_campaign_generator.php
```

### Errore 500
```bash
tail -50 storage/logs/laravel.log
```

---

## 📊 COSA È PRONTO

### File Script
- ✅ `DEPLOY_PRODUZIONE_COMPLETO.sh` - Deployment automatico
- ✅ `fix-login-production-COMPLETO.sh` - Solo fix login
- ✅ `fix-login-production-urgent.sh` - Fix minimo login

### Documentazione
- ✅ `CAMPAIGN_GENERATOR_DEPLOYMENT.md` - Guida completa Campaign Generator
- ✅ `PRODUCTION_LOGIN_FIX.md` - Fix login dettagliato
- ✅ `ANALISI_COMPLETA_PROGETTO_AINSTEIN.md` - Analisi completa progetto
- ✅ `README_DEPLOY_ADESSO.md` - Questa guida

### Test
- ✅ `test_campaign_generator.php`
- ✅ `test_export.php`
- ✅ `test_end_to_end.php`
- ✅ `tests/Feature/LoginFixTest.php`

---

## ⏱️ TIMELINE

**ADESSO** (2 min): Scegli modo deployment
**+5 min**: Esegui deployment
**+2 min**: Test login e campaigns
**+1 min**: Verifica tutto OK

**TOTALE: 10 MINUTI** e sei in produzione! 🚀

---

## 🎯 DECISIONE RAPIDA

**Quale preferisci?**

A. **Script automatico** - Carico script e lo eseguo
B. **Manuale step-by-step** - Ti guido passo passo
C. **Lo faccio io** - Mi dai accesso SSH

**Dimmi A, B o C e partiamo!**
