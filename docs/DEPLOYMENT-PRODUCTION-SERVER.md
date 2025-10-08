# 🚀 Deployment Production Server - Ainstein SaaS

**Data configurazione:** 8 Ottobre 2025
**Status:** Server configurato, pronto per deploy finale

---

## 📋 Indice

1. [Server Hetzner](#server-hetzner)
2. [Configurazione Ploi](#configurazione-ploi)
3. [Database MySQL](#database-mysql)
4. [Repository GitHub](#repository-github)
5. [Configurazione Ambiente (.env)](#configurazione-ambiente-env)
6. [Deploy Script](#deploy-script)
7. [Passaggi Rimanenti](#passaggi-rimanenti)
8. [Comandi Utili](#comandi-utili)

---

## 🖥️ Server Hetzner

### Dettagli Server
- **Provider:** Hetzner Cloud
- **Piano:** CPX21
- **IP Address:** `135.181.42.233`
- **Datacenter:** Helsinki, Finland
- **Sistema Operativo:** Ubuntu 24.04 LTS
- **Architettura:** x86

### Specifiche Hardware
- **RAM:** 4 GB
- **vCPU:** 3 Core AMD
- **Storage:** 80 GB SSD
- **Traffico:** 20 TB/mese

### Credenziali Root
- **Username:** `root`
- **Password:** Inviata via email Hetzner
- **SSH:** `ssh root@135.181.42.233`

---

## 🛠️ Configurazione Ploi

### Account Ploi
- **Email:** Account GitHub OAuth
- **Piano:** Trial gratuito
- **Dashboard:** https://ploi.io/

### Connessione Hetzner
- **API Token:** Configurato e attivo
- **Provider:** Hetzner Cloud
- **Server Name:** ainstein-production

### Site Configuration
- **Dominio:** `ainstein.it`
- **Document Root:** `/home/ploi/ainstein.it`
- **PHP Version:** 8.3
- **Web Server:** Nginx + PHP-FPM
- **User:** `ploi`

### Software Installato (automaticamente da Ploi)
- ✅ Nginx
- ✅ PHP 8.3-FPM
- ✅ MySQL 8.4
- ✅ Redis
- ✅ Composer
- ✅ Node.js & npm
- ✅ Supervisor
- ✅ Git

---

## 🗄️ Database MySQL

### Credenziali Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ainstein_prod
DB_USERNAME=ploi
DB_PASSWORD=825LnmFWtpEYsw5UEGds
```

### Accesso Database
```bash
# SSH nel server
ssh root@135.181.42.233

# Accesso MySQL
mysql -u ploi -p
# Password: 825LnmFWtpEYsw5UEGds

# Usare database
USE ainstein_prod;
```

---

## 📦 Repository GitHub

### Repository
- **URL:** https://github.com/clembeweb/ainstein-2.0
- **Branch Production:** `production`
- **Branch Development:** `master`

### Struttura Branch
- **master:** Sviluppo locale (Laravel in `ainstein-laravel/` subdirectory)
- **production:** Deploy produzione (Laravel nella root del repository)

### Ultimo Commit Production
```
Commit: 35da7bd6
Message: 🚀 Production branch - Laravel in root directory
Date: 8 Ottobre 2025
```

### Perché 2 Branch?
Il branch `production` è stato creato per risolvere il problema del deploy:
- Ploi si aspetta Laravel nella root del repository
- Il branch `master` ha Laravel in `ainstein-laravel/` subdirectory
- Il branch `production` ha tutta l'applicazione Laravel nella root

---

## ⚙️ Configurazione Ambiente (.env)

### File .env Produzione

```env
# === APPLICATION ===
APP_NAME=Ainstein
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://ainstein.it

APP_LOCALE=it
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=it_IT

# === DATABASE ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ainstein_prod
DB_USERNAME=ploi
DB_PASSWORD=825LnmFWtpEYsw5UEGds

# === SESSION & CACHE ===
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_DRIVER=redis
CACHE_PREFIX=

# === QUEUE ===
QUEUE_CONNECTION=redis

# === REDIS ===
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# === MAIL ===
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@ainstein.it"
MAIL_FROM_NAME="${APP_NAME}"

# === OPENAI ===
# Configurare via Admin Panel dopo il deploy
OPENAI_API_KEY=

# === LOGGING ===
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# === BROADCAST ===
BROADCAST_CONNECTION=log

# === FILESYSTEM ===
FILESYSTEM_DISK=local

# === VITE ===
VITE_APP_NAME="${APP_NAME}"
```

### Note Importanti
- ⚠️ **APP_KEY è vuota** - deve essere generata dopo il deploy con `php artisan key:generate --force`
- ⚠️ **OPENAI_API_KEY è vuota** - configurare via Admin Panel dopo il deploy
- ✅ Tutte le altre configurazioni sono pronte

---

## 📜 Deploy Script

### Script Configurato in Ploi

```bash
cd /home/ploi/ainstein.it

# Maintenance mode
php artisan down || true

# Pull latest code
git pull origin production

# Install/update dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Install and build frontend assets
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and optimize caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart

# Reload PHP-FPM
echo "" | sudo -S service php8.3-fpm reload

# Exit maintenance mode
php artisan up

echo "🎉 Application deployed!"
```

### Funzionalità Script
- ✅ Maintenance mode durante il deploy
- ✅ Aggiornamento codice da GitHub
- ✅ Installazione dipendenze PHP e Node.js
- ✅ Build assets frontend (Vite)
- ✅ Esecuzione migrations
- ✅ Ottimizzazione cache
- ✅ Restart queue workers
- ✅ Reload PHP-FPM

---

## ✅ Passaggi Rimanenti

### Step 1: Cambiare Branch in Ploi (2 min)
1. Vai su **Ploi Dashboard** → Site `ainstein.it`
2. Vai su **Repository & Branch**
3. Cambia branch da `master` a `production`
4. Clicca **Save**

### Step 2: Primo Deploy (5-10 min)
1. Nella dashboard del site clicca **Deploy Now**
2. Monitora il log del deploy
3. Attendi completamento (verrà eseguito tutto lo script di deploy)

### Step 3: Generare APP_KEY (1 min)
1. Vai su **Application** → **Commands**
2. Esegui comando:
   ```bash
   php artisan key:generate --force
   ```
3. Verifica che l'APP_KEY sia stata generata

### Step 4: Verificare Applicazione (2 min)
1. Apri browser: `http://135.181.42.233` oppure `http://ainstein.it`
2. Verifica che il sito carichi
3. Se vedi errori, controlla i log: **Logs** → **Application Log**

### Step 5: Installare SSL Let's Encrypt (2 min)
1. Vai su **SSL** nella dashboard del site
2. Clicca **Install Let's Encrypt Certificate**
3. Attendi 1-2 minuti per l'installazione
4. Il sito sarà disponibile su `https://ainstein.it`

### Step 6: Configurare Queue Workers (1 min)
1. Vai su **Queue**
2. Clicca **Add Worker**
3. Configurazione:
   - **Command:** `php artisan queue:work`
   - **Processes:** `1`
   - **Timeout:** `60` secondi
4. Clicca **Create**

### Step 7: Configurare Cron Jobs (1 min)
1. Vai su **Cronjobs**
2. Clicca **Add Cronjob**
3. Configurazione:
   - **Command:** `php artisan schedule:run`
   - **Frequency:** `* * * * *` (ogni minuto)
4. Clicca **Create**

### Step 8: Test Finale (5 min)
1. Accedi all'admin panel: `https://ainstein.it/admin`
2. Configura OpenAI API Key nelle Settings
3. Crea un tenant di test
4. Testa la generazione di contenuti

---

## 🔧 Comandi Utili

### SSH nel Server
```bash
ssh root@135.181.42.233
```

### Accedere alla Directory del Sito
```bash
cd /home/ploi/ainstein.it
```

### Artisan Commands
```bash
# Generare APP_KEY
php artisan key:generate --force

# Vedere migrations
php artisan migrate:status

# Eseguire migrations
php artisan migrate --force

# Rollback migrations
php artisan migrate:rollback

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ottimizzare per produzione
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Creare super admin
php artisan app:create-super-admin

# Test OpenAI
php artisan app:test-openai

# Queue worker manuale
php artisan queue:work --tries=3 --timeout=90
```

### Git Commands
```bash
# Vedere branch corrente
git branch

# Cambiare branch
git checkout production

# Pull ultimo codice
git pull origin production

# Vedere ultimo commit
git log -1
```

### MySQL Commands
```bash
# Accesso MySQL
mysql -u ploi -p

# Backup database
mysqldump -u ploi -p ainstein_prod > backup.sql

# Restore database
mysql -u ploi -p ainstein_prod < backup.sql
```

### Nginx Commands
```bash
# Test configurazione
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Restart Nginx
sudo systemctl restart nginx

# Vedere log errori
sudo tail -f /var/log/nginx/error.log
```

### PHP-FPM Commands
```bash
# Reload PHP-FPM
sudo systemctl reload php8.3-fpm

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Status PHP-FPM
sudo systemctl status php8.3-fpm
```

### Supervisor Commands (Queue Workers)
```bash
# Vedere tutti i processi
sudo supervisorctl status

# Restart tutti i processi
sudo supervisorctl restart all

# Restart specifico processo
sudo supervisorctl restart ainstein-queue:*

# Reload configurazione
sudo supervisorctl reread
sudo supervisorctl update
```

### Log Files
```bash
# Laravel log
tail -f storage/logs/laravel.log

# Nginx access log
sudo tail -f /var/log/nginx/access.log

# Nginx error log
sudo tail -f /var/log/nginx/error.log

# PHP-FPM error log
sudo tail -f /var/log/php8.3-fpm.log
```

---

## 🔒 Security Checklist

### Configurazioni Sicurezza
- ✅ APP_DEBUG=false in produzione
- ✅ APP_ENV=production
- ✅ Password database complessa
- ✅ SSL Let's Encrypt configurabile
- ✅ Firewall attivo su Hetzner
- ✅ SSH con autenticazione chiave (consigliato)

### Post-Deploy Security
- [ ] Cambiare password root del server
- [ ] Configurare firewall rules in Hetzner
- [ ] Abilitare backup automatici in Ploi
- [ ] Configurare monitoring e alerting
- [ ] Abilitare 2FA su Ploi account

---

## 📊 Monitoring

### Metriche da Monitorare
- **CPU Usage:** Max 70-80% continuativo
- **RAM Usage:** Max 3.5GB (lasciare 500MB liberi)
- **Disk Space:** Max 70GB usati
- **MySQL Connections:** Max 100 connessioni
- **Queue Jobs:** Processing time < 60s

### Ploi Monitoring
- Vai su **Server** → **Monitoring**
- Visualizza grafici CPU, RAM, Disk
- Configura alert per thresholds

---

## 🆘 Troubleshooting

### Errore: "Could not open input file: artisan"
**Causa:** Branch sbagliato (master invece di production)
**Soluzione:** Cambiare branch in Ploi da `master` a `production`

### Errore: "No application encryption key has been specified"
**Causa:** APP_KEY non generata
**Soluzione:** Esegui `php artisan key:generate --force`

### Errore 500 Internal Server Error
**Causa:** Vari possibili
**Soluzione:**
1. Controlla `storage/logs/laravel.log`
2. Verifica permessi: `chmod -R 775 storage bootstrap/cache`
3. Verifica owner: `chown -R ploi:ploi storage bootstrap/cache`

### Migrations non eseguite
**Soluzione:**
```bash
php artisan migrate --force
```

### Queue non processa jobs
**Soluzione:**
```bash
# Restart worker via Ploi
# Oppure manualmente:
sudo supervisorctl restart ainstein-queue:*
```

### Frontend non carica (404 su CSS/JS)
**Causa:** Build assets non eseguito
**Soluzione:**
```bash
npm ci
npm run build
```

### Database connection refused
**Soluzione:**
1. Verifica MySQL attivo: `sudo systemctl status mysql`
2. Verifica credenziali in .env
3. Test connessione: `mysql -u ploi -p`

---

## 📞 Supporto

### Documentazione
- **Laravel:** https://laravel.com/docs
- **Ploi:** https://ploi.io/docs
- **Hetzner:** https://docs.hetzner.com

### Account
- **Hetzner Dashboard:** https://console.hetzner.cloud/
- **Ploi Dashboard:** https://ploi.io/
- **GitHub Repository:** https://github.com/clembeweb/ainstein-2.0

---

## 📝 Note Finali

### DNS Configuration
Se il dominio `ainstein.it` punta già al server `135.181.42.233`:
- ✅ A Record: `ainstein.it` → `135.181.42.233`
- ✅ Propagazione DNS: Può richiedere fino a 24-48h
- ✅ Test: `nslookup ainstein.it` o `dig ainstein.it`

### Prossimi Passi Sviluppo
1. Configurare email SMTP reale (attualmente usa `log`)
2. Configurare backup automatici
3. Setup monitoring avanzato (Sentry, etc)
4. Configurare CDN per assets statici
5. Ottimizzazione performance (Redis cache, OPcache)

### Costi Mensili Stimati
- **Hetzner CPX21:** €5.30/mese
- **Ploi:** €9/mese (dopo trial)
- **Dominio:** Già pagato
- **TOTALE:** ~€14.30/mese

---

**Documento creato:** 8 Ottobre 2025
**Ultima modifica:** 8 Ottobre 2025
**Status:** ✅ Server pronto, deploy da completare

🤖 Generated with [Claude Code](https://claude.com/claude-code)
