# Deployment Ainstein su Hetzner + Ploi - Resume

**Data Iniziale**: 2025-10-09
**Ultimo Aggiornamento**: 2025-10-12
**Stato**: Laravel Deployato ✅ | Login Fix in Progress 🔄

---

## 📋 Stato Attuale

### ✅ Completato
- **Server Hetzner attivo e configurato**:
  - Nome: `ainstein`
  - IP: `135.181.42.233`
  - OS: Ubuntu 24.04.3 LTS
  - Specs: CPX21 (3 vCPU, 4GB RAM, 80GB Disk, Helsinki)
  - Domain: ainstein.it (SSL attivo)
- **Stack LEMP installato**:
  - PHP 8.3 con tutte le estensioni Laravel
  - Nginx configurato e funzionante
  - MySQL 8.0 database attivo
  - Redis, Supervisor, Queue workers operativi
- **Laravel deployato in produzione**:
  - Path: `/var/www/ainstein`
  - Branch: `sviluppo-tool` (33 commits ahead di master)
  - HTTPS configurato e SSL funzionante
  - Database migrato (parzialmente)

### 🔄 In Progress (2025-10-12)
- **Login Issue Fix**: Configurazioni HTTPS session applicate, test in corso
- **Config Cache**: Problema Closure serialization identificato
- **Feature Deployment**: 3 major features pronte (CrewAI, SEO Agent, Campaign Generator)

### ⚠️ Da Completare
- Risolvere completamente login redirect loop
- Fix config cache Closure issue
- Merge `sviluppo-tool` → `master`
- Deploy pending features in produzione
- Run pending migrations (OAuth, CrewAI, SEO)

---

## 🎯 Piano di Deployment

### Opzione A: Setup Automatico con Ploi (CONSIGLIATO - 10 min)

1. **Su Ploi.io (https://ploi.io)**:
   - Login all'account
   - Vai su "Servers" → "Add Server"
   - Scegli "Custom VPS" o "Custom Provider"
   - Inserisci:
     - Name: `Ainstein Production`
     - IP Address: `135.181.42.233`
     - Root Password: `tNakVHcTMkUu`
     - SSH Port: `22`
     - User: `ploi` (verrà creato automaticamente)

2. **Ploi ti fornirà un comando da eseguire** tipo:
   ```bash
   ssh root@135.181.42.233
   curl -sSL https://cdn.ploi.io/install.sh | bash -s -- TOKEN_QUI
   ```

3. **Esegui il comando sul server** (via SSH)

4. **Ploi installerà automaticamente**:
   - PHP 8.3 (con estensioni Laravel)
   - Nginx
   - MySQL 8.0
   - Composer
   - Node.js
   - Redis
   - Supervisor
   - Certbot (Let's Encrypt)
   - User `ploi` con permessi corretti

5. **Dopo il setup Ploi**:
   - Vai su "Sites" → "Add Site"
   - Domain: `ainstein.yourdomain.com` (o IP temporaneo)
   - Repository: `https://github.com/[username]/ainstein-3.git`
   - Branch: `master` (o `production`)
   - Root directory: `/`
   - Zero downtime: ON

---

### Opzione B: Setup Manuale (30-40 min)

Se Ploi non funziona, seguire questi step manuali.

#### 1. Installazione Stack LEMP

```bash
# Connessione al server
ssh root@135.181.42.233

# Update sistema
apt update && apt upgrade -y

# Installazione PHP 8.3 + estensioni
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
    php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml \
    php8.3-bcmath php8.3-intl php8.3-redis php8.3-sqlite3

# Installazione Nginx
apt install -y nginx

# Installazione MySQL
apt install -y mysql-server
mysql_secure_installation

# Installazione Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Installazione Node.js + npm
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Installazione Redis
apt install -y redis-server
systemctl enable redis-server

# Installazione Supervisor
apt install -y supervisor
systemctl enable supervisor
```

#### 2. Creazione User e Directory

```bash
# Crea user ploi (o ainstein)
useradd -m -s /bin/bash ploi
usermod -aG www-data ploi

# Setup SSH per user ploi
mkdir -p /home/ploi/.ssh
echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICxxsRkNQy1jTvRXKtiAz8A7ZlvwvgZ22u2WOpSFAcsM ainstein-deployment" > /home/ploi/.ssh/authorized_keys
chmod 700 /home/ploi/.ssh
chmod 600 /home/ploi/.ssh/authorized_keys
chown -R ploi:ploi /home/ploi/.ssh

# Crea directory progetto
mkdir -p /var/www/ainstein
chown -R ploi:www-data /var/www/ainstein
```

#### 3. Clone Repository

```bash
# Login come ploi
su - ploi

# Clone progetto
cd /var/www/ainstein
git clone https://github.com/[USERNAME]/[REPO].git .

# Oppure se hai già la repo locale, usa rsync da locale:
# rsync -avz --exclude 'node_modules' --exclude 'vendor' \
#   /c/laragon/www/ainstein-3/ ploi@135.181.42.233:/var/www/ainstein/
```

#### 4. Setup Laravel

```bash
cd /var/www/ainstein

# Installa dipendenze
composer install --no-dev --optimize-autoloader

# Copia .env
cp .env.example .env
nano .env  # Configura:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://yourdomain.com
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ainstein
# DB_USERNAME=ainstein_user
# DB_PASSWORD=[genera password sicura]

# Genera chiave
php artisan key:generate

# Setup database
mysql -u root -p
# CREATE DATABASE ainstein CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# CREATE USER 'ainstein_user'@'localhost' IDENTIFIED BY '[PASSWORD]';
# GRANT ALL PRIVILEGES ON ainstein.* TO 'ainstein_user'@'localhost';
# FLUSH PRIVILEGES;
# EXIT;

# Migrazioni
php artisan migrate --force
php artisan db:seed --force  # Se necessario

# Storage link
php artisan storage:link

# Ottimizzazioni
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build assets
npm install
npm run build

# Permessi
chown -R ploi:www-data /var/www/ainstein
chmod -R 755 /var/www/ainstein
chmod -R 775 /var/www/ainstein/storage
chmod -R 775 /var/www/ainstein/bootstrap/cache
```

#### 5. Configurazione Nginx

```bash
# Come root
sudo nano /etc/nginx/sites-available/ainstein
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name ainstein.yourdomain.com;
    root /var/www/ainstein/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Abilita sito
ln -s /etc/nginx/sites-available/ainstein /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

#### 6. Setup SSL (Let's Encrypt)

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d ainstein.yourdomain.com
```

#### 7. Setup Queue Worker (Supervisor)

```bash
sudo nano /etc/supervisor/conf.d/ainstein-worker.conf
```

```ini
[program:ainstein-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ainstein/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ploi
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ainstein/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start ainstein-worker:*
```

#### 8. Setup Cron

```bash
crontab -e -u ploi
```

Aggiungi:
```
* * * * * cd /var/www/ainstein && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Comandi Utili Post-Deployment

```bash
# Test connessione SSH (da locale)
ssh root@135.181.42.233
ssh ploi@135.181.42.233

# Alias nel config SSH (già configurato)
ssh ainstein-ploi

# Check servizi sul server
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mysql
systemctl status redis-server
supervisorctl status

# Laravel logs
tail -f /var/www/ainstein/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Artisan commands
cd /var/www/ainstein
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

---

## 📦 Informazioni Repository

**Repository locale**: `C:\laragon\www\ainstein-3`

**Branch principale**: `master` o `production`

**Struttura progetto**:
- Laravel 11.x
- PHP 8.3+
- MySQL/SQLite
- Vite per asset building

**File da configurare sul server**:
- `.env` (copiare da `.env.example`)
- Database credentials
- APP_KEY (generare con `php artisan key:generate`)

---

## 🔐 Credenziali e Accessi

### Server Hetzner
- IP: `135.181.42.233`
- User root: `root`
- Password root: `tNakVHcTMkUu`
- Chiave SSH: `~/.ssh/ainstein_ploi`

### Ploi
- URL: https://ploi.io
- Account: [inserire email]

### Database (da configurare)
- Host: `localhost`
- Database: `ainstein`
- User: `ainstein_user`
- Password: [generare sicura]

---

## 📝 Checklist Deployment

- [ ] Server Hetzner connesso via SSH
- [ ] Stack LEMP installato (PHP, Nginx, MySQL, Composer)
- [ ] User `ploi` creato con chiave SSH
- [ ] Repository clonata in `/var/www/ainstein`
- [ ] Dipendenze Composer installate
- [ ] `.env` configurato correttamente
- [ ] Database creato e migrato
- [ ] Storage linked
- [ ] Assets compilati (npm run build)
- [ ] Nginx configurato e testato
- [ ] SSL certificato installato
- [ ] Queue worker attivo (Supervisor)
- [ ] Cron job configurato
- [ ] Permessi file corretti (ploi:www-data)
- [ ] Cache Laravel ottimizzate
- [ ] Test finale applicazione funzionante

---

## 🚨 Troubleshooting

### Errore "Permission denied"
```bash
chown -R ploi:www-data /var/www/ainstein
chmod -R 755 /var/www/ainstein
chmod -R 775 /var/www/ainstein/storage
chmod -R 775 /var/www/ainstein/bootstrap/cache
```

### Errore 500 - Check logs
```bash
tail -f /var/www/ainstein/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Database connection failed
- Verifica credenziali in `.env`
- Verifica MySQL attivo: `systemctl status mysql`
- Test connessione: `mysql -u ainstein_user -p ainstein`

### Assets non caricano
```bash
cd /var/www/ainstein
npm run build
php artisan storage:link
```

---

## 📞 Comando per Claude

Quando riprendi la sessione, scrivi:

**"Leggi il file DEPLOYMENT-RESUME.md e procedi con il deployment completo della piattaforma Ainstein. Usa l'Opzione A (Ploi) se possibile, altrimenti procedi con setup manuale (Opzione B)."**

---

**Fine documento** - Ultimo aggiornamento: 2025-10-09 20:30
