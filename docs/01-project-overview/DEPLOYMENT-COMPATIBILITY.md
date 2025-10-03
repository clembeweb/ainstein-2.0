# 🌐 Multi-Hosting Deployment Compatibility Guide

**Status**: 📋 Documentation
**Priority**: P1 HIGH
**Target**: Compatibilità con SiteGround, AWS, DigitalOcean, Cloudways, Laravel Forge

---

## 🎯 OBIETTIVO

Rendere Ainstein deployabile su qualsiasi hosting provider mantenendo la compatibilità primaria con **SiteGround** (hosting corrente).

---

## 📊 HOSTING PROVIDERS SUPPORTATI

### Tier 1 - Priorità Alta ✅
1. **SiteGround** (Current) - Shared/Cloud/Dedicated
2. **Laravel Forge** (AWS/DO/Linode/Vultr) - Recommended for production
3. **Cloudways** (Managed Cloud) - Easy scaling

### Tier 2 - Priorità Media 🔨
4. **DigitalOcean App Platform** - Container-based
5. **AWS Elastic Beanstalk** - Auto-scaling
6. **Heroku** - Simple deployment

### Tier 3 - Future 🔮
7. **Google Cloud Run** - Serverless
8. **Azure App Service** - Enterprise
9. **Vercel + PlanetScale** - Edge deployment

---

## 🏗️ ARCHITETTURA HOSTING-AGNOSTIC

### Principi Fondamentali

```
1. Configuration Management
   ├── .env file (local/shared hosting)
   ├── Platform Settings DB (runtime config)
   └── Environment variables (cloud hosting)

2. File Storage
   ├── Local storage (SiteGround)
   ├── S3-compatible (AWS, DO Spaces, Cloudflare R2)
   └── Auto-detection based on env

3. Database
   ├── MySQL 8.0+ (SiteGround, Forge)
   ├── PostgreSQL (Heroku, DO)
   └── MariaDB (CloudWays)

4. Cache & Queue
   ├── Redis (preferred) - Available on most hosts
   ├── Database fallback - Universal compatibility
   └── File cache - SiteGround shared hosting
```

---

## 📁 FILE STRUCTURE UNIVERSALE

### Required Files in Root

```
ainstein-laravel/
├── .env.example                    # Template con tutti i parametri
├── .htaccess                       # Apache config (SiteGround)
├── nginx.conf.example              # Nginx config (Forge, Cloudways)
├── Procfile                        # Heroku deployment
├── app.yaml                        # Google Cloud App Engine
├── .platform.app.yaml              # Platform.sh
├── docker-compose.yml              # Local development + container deployment
├── Dockerfile                      # Production container
└── deployment/
    ├── siteground-deploy.sh        # Script per SiteGround
    ├── forge-deploy.sh             # Script per Laravel Forge
    ├── cloudways-deploy.sh         # Script per Cloudways
    └── aws-deploy.sh               # Script per AWS EB
```

---

## ⚙️ .ENV CONFIGURATION

### .env.example (Universal Template)

```bash
# ===== APPLICATION =====
APP_NAME=Ainstein
APP_ENV=production
APP_KEY=                           # php artisan key:generate
APP_DEBUG=false
APP_URL=https://yourdomain.com

# ===== DATABASE =====
# SiteGround/cPanel: MySQL con prefix
# Forge/Cloudways: MySQL senza prefix
# Heroku/Railway: PostgreSQL
DB_CONNECTION=mysql                # mysql|pgsql
DB_HOST=127.0.0.1                  # localhost per SiteGround
DB_PORT=3306                       # 3306 per MySQL, 5432 per PostgreSQL
DB_DATABASE=ainstein_db
DB_USERNAME=ainstein_user
DB_PASSWORD=

# ===== CACHE & QUEUE =====
# Opzioni: redis (recommended), database (fallback), file (shared hosting)
CACHE_DRIVER=redis                 # redis|database|file
QUEUE_CONNECTION=redis             # redis|database|sync
SESSION_DRIVER=redis               # redis|database|file

# Redis Configuration (se disponibile)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ===== FILE STORAGE =====
# Opzioni: local (shared hosting), s3 (AWS), do (DigitalOcean), r2 (Cloudflare)
FILESYSTEM_DISK=local              # local|s3|do|r2
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# DigitalOcean Spaces
DO_SPACES_KEY=
DO_SPACES_SECRET=
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
DO_SPACES_REGION=nyc3
DO_SPACES_BUCKET=

# Cloudflare R2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=

# ===== EMAIL =====
# Configurabile via Admin Settings UI
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# ===== API KEYS (Fallback - prefer Admin Settings) =====
OPENAI_API_KEY=                    # Preferito: Admin Settings DB
STRIPE_KEY=
STRIPE_SECRET=

# ===== LOGGING =====
# Opzioni: stack, single, daily, syslog
LOG_CHANNEL=stack                  # daily per production
LOG_LEVEL=debug                    # error per production

# ===== HOSTING-SPECIFIC =====
# SiteGround: Abilita ottimizzazioni
SITEGROUND_OPTIMIZATIONS=false

# Laravel Forge: Abilita OPcache
OPCACHE_ENABLED=true

# Heroku: Force HTTPS
FORCE_HTTPS=false
```

---

## 🔧 CONFIG FILES HOSTING-SPECIFIC

### 1. SiteGround (.htaccess già presente)

File: `public/.htaccess` (Laravel standard + ottimizzazioni SG)

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]

    # SiteGround Optimizations
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
    </IfModule>

    # Browser Caching
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/gif "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
</IfModule>
```

### 2. Nginx (Laravel Forge, Cloudways)

File: `nginx.conf.example`

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    root /home/forge/ainstein.com/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3. Heroku (Procfile)

File: `Procfile`

```
web: vendor/bin/heroku-php-apache2 public/
worker: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
release: php artisan migrate --force
```

### 4. Docker (Universal Container)

File: `Dockerfile`

```dockerfile
FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    redis \
    mysql-client \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

File: `docker-compose.yml`

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: ainstein-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - ainstein-network

  nginx:
    image: nginx:alpine
    container_name: ainstein-nginx
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./:/var/www
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - ainstein-network

  mysql:
    image: mysql:8.0
    container_name: ainstein-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - ainstein-network

  redis:
    image: redis:alpine
    container_name: ainstein-redis
    restart: unless-stopped
    networks:
      - ainstein-network

networks:
  ainstein-network:
    driver: bridge

volumes:
  mysql-data:
```

---

## 📜 DEPLOYMENT SCRIPTS

### 1. SiteGround Deploy Script

File: `deployment/siteground-deploy.sh`

```bash
#!/bin/bash

echo "🚀 Deploying to SiteGround..."

# 1. Pull latest code
git pull origin master

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Clear and cache config
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations
php artisan migrate --force

# 5. Clear application cache
php artisan cache:clear

# 6. Optimize autoloader
composer dump-autoload -o

# 7. Set permissions (if needed)
chmod -R 755 storage bootstrap/cache

echo "✅ Deployment completed!"
```

### 2. Laravel Forge Deploy Script

File: `deployment/forge-deploy.sh`

```bash
cd /home/forge/ainstein.com

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
    $FORGE_PHP artisan queue:restart
    $FORGE_PHP artisan horizon:terminate
fi
```

### 3. AWS Elastic Beanstalk

File: `.ebextensions/01-laravel.config`

```yaml
option_settings:
  aws:elasticbeanstalk:container:php:phpini:
    document_root: /public
    memory_limit: 512M
    zlib.output_compression: "Off"
    allow_url_fopen: "On"
    display_errors: "Off"
    max_execution_time: 60

container_commands:
  01_storage_link:
    command: "php artisan storage:link"
  02_migrate:
    command: "php artisan migrate --force"
    leader_only: true
  03_cache:
    command: "php artisan config:cache && php artisan route:cache && php artisan view:cache"
```

---

## 🔍 AUTO-DETECTION SERVICE

### app/Services/HostingDetector.php

```php
<?php

namespace App\Services;

class HostingDetector
{
    /**
     * Detect current hosting provider
     */
    public static function detect(): string
    {
        // Check environment variables
        if (env('DYNO')) return 'heroku';
        if (env('FORGE_SITE_BRANCH')) return 'forge';
        if (env('AWS_EXECUTION_ENV')) return 'aws';
        if (isset($_SERVER['CLOUDWAYS'])) return 'cloudways';
        if (isset($_SERVER['SITEGROUND'])) return 'siteground';

        // Check server signature
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';

        if (stripos($serverSoftware, 'nginx') !== false) {
            return 'nginx'; // Likely Forge, Cloudways, DO
        }

        if (stripos($serverSoftware, 'apache') !== false) {
            return 'apache'; // Likely SiteGround, cPanel
        }

        return 'unknown';
    }

    /**
     * Check if Redis is available
     */
    public static function hasRedis(): bool
    {
        try {
            $redis = new \Redis();
            $redis->connect(env('REDIS_HOST', '127.0.0.1'), env('REDIS_PORT', 6379));
            return $redis->ping();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get recommended cache driver
     */
    public static function recommendedCacheDriver(): string
    {
        if (self::hasRedis()) return 'redis';

        $hosting = self::detect();

        if (in_array($hosting, ['forge', 'cloudways', 'aws'])) {
            return 'database'; // Better for distributed systems
        }

        return 'file'; // Safe fallback for shared hosting
    }

    /**
     * Get recommended queue driver
     */
    public static function recommendedQueueDriver(): string
    {
        if (self::hasRedis()) return 'redis';

        return 'database'; // Universal fallback
    }

    /**
     * Get recommended storage driver
     */
    public static function recommendedStorageDriver(): string
    {
        $hosting = self::detect();

        if ($hosting === 'aws') return 's3';
        if ($hosting === 'heroku') return 's3'; // Use AWS S3

        return 'local'; // Default
    }
}
```

---

## 🛠️ BOOTSTRAP OPTIMIZATION

### bootstrap/app.php (Auto-configuration)

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Services\HostingDetector;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->booted(function () {
        // Auto-configure based on hosting environment
        $hosting = HostingDetector::detect();

        if ($hosting === 'siteground') {
            // SiteGround-specific optimizations
            ini_set('max_execution_time', '300');
            ini_set('memory_limit', '256M');
        }

        if ($hosting === 'forge' || $hosting === 'cloudways') {
            // Enable OPcache optimizations
            if (function_exists('opcache_get_status')) {
                ini_set('opcache.enable', '1');
                ini_set('opcache.memory_consumption', '256');
            }
        }
    })
    ->create();
```

---

## ✅ CHECKLIST COMPATIBILITÀ

### Pre-Deployment Checklist

- [ ] `.env.example` aggiornato con tutte le variabili
- [ ] `composer.json` dependencies compatibili (no dev-only packages)
- [ ] `php artisan config:cache` funziona senza errori
- [ ] Storage symlink: `php artisan storage:link`
- [ ] Migrations tested: `php artisan migrate --pretend`
- [ ] Permissions corrette: `chmod -R 755 storage bootstrap/cache`
- [ ] `.gitignore` esclude `.env`, `vendor/`, `node_modules/`, `storage/`
- [ ] Database backup effettuato
- [ ] SSL certificate configurato (Let's Encrypt via cPanel/Forge)
- [ ] Queue worker running (Supervisor, Laravel Horizon, Forge daemon)

### Post-Deployment Verification

```bash
# 1. Check application status
php artisan about

# 2. Verify database connection
php artisan tinker
>>> DB::connection()->getPdo()

# 3. Check cache
php artisan cache:clear
php artisan config:cache

# 4. Verify queue
php artisan queue:work --once

# 5. Test OpenAI connection (via Admin Settings UI)
# Navigate to /admin/settings -> Test OpenAI

# 6. Check logs
tail -f storage/logs/laravel.log
```

---

## 📊 HOSTING COMPARISON TABLE

| Feature | SiteGround | Laravel Forge | Cloudways | Heroku | AWS EB |
|---------|------------|---------------|-----------|--------|--------|
| **Setup Time** | 30 min | 15 min | 20 min | 10 min | 45 min |
| **Price (est.)** | $14/mo | $12/mo + server | $10/mo + server | $7/mo dyno | $20/mo |
| **Redis** | ❌ (add-on) | ✅ | ✅ | ✅ | ✅ |
| **Queue Workers** | ⚠️ Cron jobs | ✅ Supervisor | ✅ Supervisor | ✅ Native | ✅ Worker |
| **Auto-Scaling** | ❌ | ⚠️ Manual | ✅ | ✅ | ✅ |
| **Backups** | ✅ Daily | ⚠️ Manual | ✅ Daily | ⚠️ Add-on | ✅ Automated |
| **SSL** | ✅ Free | ✅ Free | ✅ Free | ✅ Free | ✅ Free |
| **Deployment** | FTP/Git | ✅ Git push | ✅ Git push | ✅ Git push | ✅ Git push |
| **Best For** | MVP, Small | Production | Managed Prod | Quick MVPs | Enterprise |

---

## 🎯 RACCOMANDAZIONI

### Fase 1 - MVP (Attuale)
**SiteGround Cloud Hosting**
- ✅ Già configurato
- ✅ Economico ($14/mese)
- ✅ cPanel facile da usare
- ⚠️ Limitato per queue workers (usare cron job ogni minuto)
- ⚠️ No Redis (usare database cache)

### Fase 2 - Production (10-50 tenants)
**Laravel Forge + DigitalOcean Droplet**
- ✅ Ottimizzato per Laravel
- ✅ Redis, Supervisor, Queue workers nativi
- ✅ Deploy automatico con git push
- ✅ Cost: ~$17/mese ($12 Forge + $5 DO droplet)

### Fase 3 - Scale (100+ tenants)
**AWS Elastic Beanstalk + RDS + ElastiCache**
- ✅ Auto-scaling
- ✅ Load balancing
- ✅ Multi-region deployment
- ⚠️ Cost: $50-200/mese (based on traffic)

---

## 🚨 CRITICAL NOTES

1. **Queue Workers su SiteGround**: Usare cron job ogni minuto
   ```bash
   * * * * * cd /path/to/ainstein-laravel && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Redis su SiteGround**: Non disponibile nativamente, usare cache database:
   ```env
   CACHE_DRIVER=database
   QUEUE_CONNECTION=database
   SESSION_DRIVER=database
   ```

3. **File Upload su Production**: Usare S3 o DO Spaces per storage:
   ```env
   FILESYSTEM_DISK=s3
   ```

4. **Environment Variables**: Su hosting condiviso, usare `.env` file. Su cloud, usare variabili ambiente native del provider.

---

_Last Updated: 2025-10-03_
_Compatibile con: Laravel 12.x_
_Hosting Primario: SiteGround Cloud_
