# AI Article Steps — Deployment Guide

**Documento**: 07 — Production Deployment & Configuration
**Progetto**: Ainstein Laravel Multi-Tenant Platform
**Tool**: AI Article Steps (Copy Article Generator)
**Environment**: Ubuntu 22.04 + Nginx + PHP 8.3 + MySQL 8

---

## Indice
1. [Prerequisites](#1-prerequisites)
2. [Database Setup](#2-database-setup)
3. [Application Configuration](#3-application-configuration)
4. [Queue Workers](#4-queue-workers)
5. [Cron Jobs](#5-cron-jobs)
6. [Performance Optimization](#6-performance-optimization)
7. [Monitoring & Logging](#7-monitoring--logging)
8. [Security Checklist](#8-security-checklist)

---

## 1. Prerequisites

### System Requirements

```bash
# OS
Ubuntu 22.04 LTS (recommended)

# Software
PHP 8.3+ with extensions:
  - mbstring, xml, bcmath, curl, gd, pdo_mysql, redis

MySQL 8.0+
Redis 7.0+
Nginx 1.24+
Supervisor 4.2+
Composer 2.x
Node.js 20.x + npm
```

### Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml \
  php8.3-bcmath php8.3-curl php8.3-gd php8.3-mysql php8.3-redis

# Install MySQL
sudo apt install -y mysql-server-8.0

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Supervisor
sudo apt install -y supervisor

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 2. Database Setup

### Run Migrations

```bash
# Navigate to project
cd /var/www/ainstein

# Run migrations for AI Article Steps
php artisan migrate --path=database/migrations/article_steps

# Verify tables
php artisan tinker
>>> DB::table('articles')->count();
>>> DB::table('keywords')->count();
>>> exit
```

### Database Indexes (Production Optimization)

```sql
-- Articles table
CREATE INDEX idx_articles_tenant_status ON articles(tenant_id, status);
CREATE INDEX idx_articles_tenant_created ON articles(tenant_id, created_at DESC);
CREATE INDEX idx_articles_keyword ON articles(keyword_id);
CREATE FULLTEXT INDEX idx_articles_search ON articles(title, content);

-- Keywords table
CREATE INDEX idx_keywords_tenant_status ON keywords(tenant_id, status);
CREATE INDEX idx_keywords_priority ON keywords(priority DESC, search_volume DESC);

-- SEO Steps table
CREATE INDEX idx_seo_steps_article_status ON seo_steps(article_id, status);
CREATE INDEX idx_seo_steps_article_order ON seo_steps(article_id, step_order);

-- Internal Links table
CREATE INDEX idx_internal_links_article ON internal_links(article_id);
CREATE INDEX idx_internal_links_relevance ON internal_links(relevance_score DESC);

-- Article Generations table
CREATE INDEX idx_generations_article ON article_generations(article_id);
CREATE INDEX idx_generations_status ON article_generations(status);
```

### Database Backup Script

```bash
#!/bin/bash
# File: /opt/scripts/backup-article-steps-db.sh

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/mysql"
DB_NAME="ainstein_db"

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump only AI Article Steps tables
mysqldump -u root -p$MYSQL_ROOT_PASSWORD $DB_NAME \
  articles \
  keywords \
  prompt_templates \
  seo_steps \
  internal_links \
  article_variants \
  article_generations \
  > $BACKUP_DIR/article_steps_$TIMESTAMP.sql

# Compress
gzip $BACKUP_DIR/article_steps_$TIMESTAMP.sql

# Delete backups older than 30 days
find $BACKUP_DIR -name "article_steps_*.sql.gz" -mtime +30 -delete

echo "Backup completed: article_steps_$TIMESTAMP.sql.gz"
```

---

## 3. Application Configuration

### Environment Variables

**File**: `.env` (production)

```bash
# App
APP_NAME="Ainstein AI Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ainstein.app

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ainstein_db
DB_USERNAME=ainstein_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# OpenAI (required for article generation)
OPENAI_API_KEY=sk-proj-XXXXXXXXXXXXXXXXXXXXXXXX
OPENAI_DEFAULT_MODEL=gpt-4o

# Article Generation Config
ARTICLE_GENERATION_TIMEOUT=600
ARTICLE_GENERATION_MAX_RETRIES=3
ARTICLE_GENERATION_QUEUE=articles

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

### Service Provider Registration

**File**: `config/app.php`

```php
'providers' => [
    // ... existing providers
    App\Providers\ArticleStepsServiceProvider::class,
],
```

### Create Service Provider

**File**: `app/Providers/ArticleStepsServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ArticleStepsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\ArticleSteps\ArticleGenerationService::class);
        $this->app->singleton(\App\Services\ArticleSteps\SeoOptimizationService::class);
        $this->app->singleton(\App\Services\ArticleSteps\InternalLinkSuggestionService::class);
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(base_path('routes/article-steps.php'));

        // Load views
        $this->loadViewsFrom(resource_path('views/tenant/article-steps'), 'article-steps');

        // Publish config
        $this->publishes([
            __DIR__.'/../../config/article-steps.php' => config_path('article-steps.php'),
        ], 'article-steps-config');
    }
}
```

---

## 4. Queue Workers

### Queue Configuration

**File**: `config/queue.php`

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 600, // 10 minutes for article generation
        'block_for' => null,
    ],
],

'failed' => [
    'driver' => 'database-uuids',
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'failed_jobs',
],
```

### Supervisor Configuration

**File**: `/etc/supervisor/conf.d/ainstein-article-queue.conf`

```ini
[program:ainstein-article-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ainstein/artisan queue:work redis --queue=articles --sleep=3 --tries=3 --max-time=3600 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/ainstein/storage/logs/queue-articles.log
stopwaitsecs=3600
```

### Start Queue Workers

```bash
# Reload supervisor config
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start ainstein-article-queue:*

# Check status
sudo supervisorctl status ainstein-article-queue:*

# View logs
tail -f /var/www/ainstein/storage/logs/queue-articles.log
```

### Monitor Queue Health

**File**: `/opt/scripts/monitor-article-queue.sh`

```bash
#!/bin/bash
# Monitor article generation queue

QUEUE_SIZE=$(php /var/www/ainstein/artisan queue:size redis --queue=articles)
FAILED_JOBS=$(php /var/www/ainstein/artisan queue:failed --json | jq length)

if [ "$QUEUE_SIZE" -gt 100 ]; then
    echo "WARNING: Article queue size is $QUEUE_SIZE (threshold: 100)"
    # Send alert (email, Slack, etc.)
fi

if [ "$FAILED_JOBS" -gt 10 ]; then
    echo "WARNING: Failed article jobs: $FAILED_JOBS"
    # Send alert
fi
```

---

## 5. Cron Jobs

### Schedule Configuration

**File**: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    // Publish scheduled articles
    $schedule->command('articles:publish-scheduled')
        ->everyFiveMinutes()
        ->withoutOverlapping();

    // Cleanup old failed jobs
    $schedule->command('queue:prune-failed --hours=168')
        ->weekly();

    // Auto-categorize keywords
    $schedule->command('keywords:auto-categorize')
        ->daily();

    // Cleanup old generation records
    $schedule->command('articles:cleanup-generations --days=90')
        ->weekly();

    // Database backup
    $schedule->exec('/opt/scripts/backup-article-steps-db.sh')
        ->daily()
        ->at('02:00');

    // Queue health monitor
    $schedule->exec('/opt/scripts/monitor-article-queue.sh')
        ->everyFiveMinutes();
}
```

### Cron Entry

```bash
# Edit crontab
sudo crontab -e -u www-data

# Add Laravel scheduler
* * * * * cd /var/www/ainstein && php artisan schedule:run >> /dev/null 2>&1
```

### Custom Artisan Commands

**Publish Scheduled Articles**

**File**: `app/Console/Commands/PublishScheduledArticles.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class PublishScheduledArticles extends Command
{
    protected $signature = 'articles:publish-scheduled';
    protected $description = 'Publish articles that are scheduled for now';

    public function handle()
    {
        $articles = Article::readyToPublish()->get();

        foreach ($articles as $article) {
            $article->publish();
            $this->info("Published: {$article->title}");
        }

        $this->info("Published {$articles->count()} article(s)");
    }
}
```

---

## 6. Performance Optimization

### Redis Cache Configuration

```php
// Cache SEO scores for 1 hour
Cache::remember("article_seo_score_{$articleId}", 3600, function () use ($article) {
    return $this->seoOptimizationService->calculateSeoScore($article);
});

// Cache keyword analytics for 30 minutes
Cache::remember("keyword_analytics_{$tenantId}", 1800, function () use ($tenant) {
    return $this->keywordService->getAnalytics($tenant);
});
```

### Database Query Optimization

```php
// Eager load relationships
$articles = Article::with([
    'keyword',
    'promptTemplate',
    'seoSteps' => fn($q) => $q->ordered(),
    'internalLinks' => fn($q) => $q->byRelevance(),
    'latestGeneration',
])->paginate(20);

// Use indexes for filtering
Article::forTenant($tenantId)
    ->where('status', Article::STATUS_COMPLETED)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();
```

### Opcache Configuration

**File**: `/etc/php/8.3/fpm/conf.d/10-opcache.ini`

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

---

## 7. Monitoring & Logging

### Log Rotation

**File**: `/etc/logrotate.d/ainstein-article-steps`

```
/var/www/ainstein/storage/logs/queue-articles.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### Application Logging

```php
// Log article generation start
Log::info('Article generation started', [
    'article_id' => $article->id,
    'tenant_id' => $tenant->id,
    'keyword' => $keyword->keyword,
]);

// Log failures with context
Log::error('Article generation failed', [
    'article_id' => $article->id,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
]);
```

### Health Check Endpoint

**File**: `routes/api.php`

```php
Route::get('/health/article-steps', function () {
    return response()->json([
        'status' => 'healthy',
        'queue' => [
            'size' => Queue::size('articles'),
            'failed' => DB::table('failed_jobs')->where('queue', 'articles')->count(),
        ],
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::ping() === '+PONG' ? 'connected' : 'disconnected',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

---

## 8. Security Checklist

### Production Security

- [ ] ✅ Set `APP_DEBUG=false` in `.env`
- [ ] ✅ Generate strong `APP_KEY`
- [ ] ✅ Use HTTPS only (SSL certificate)
- [ ] ✅ Enable CSRF protection on all forms
- [ ] ✅ Implement rate limiting on generation endpoints
- [ ] ✅ Secure OpenAI API key in environment variables
- [ ] ✅ Enable XSS protection headers
- [ ] ✅ Implement tenant isolation in all queries
- [ ] ✅ Use prepared statements (Eloquent handles this)
- [ ] ✅ Sanitize user input in prompts
- [ ] ✅ Limit file upload sizes
- [ ] ✅ Configure proper file permissions (755 for dirs, 644 for files)

### Rate Limiting

**File**: `app/Http/Kernel.php`

```php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        // ...
    ],
];

protected $middlewareAliases = [
    'throttle.articles' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':10,1', // 10 requests per minute
];
```

**Apply to routes**:

```php
Route::post('articles', [TenantArticleController::class, 'store'])
    ->middleware('throttle.articles');
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Run all tests (`php artisan test`)
- [ ] Check migration files
- [ ] Verify environment variables
- [ ] Backup production database
- [ ] Review queue configuration

### Deployment Steps
```bash
# 1. Pull latest code
cd /var/www/ainstein
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Restart services
sudo supervisorctl restart ainstein-article-queue:*
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx

# 6. Verify
php artisan queue:work redis --queue=articles --once
curl https://ainstein.app/api/health/article-steps
```

### Post-Deployment
- [ ] Monitor queue workers (`supervisorctl status`)
- [ ] Check application logs
- [ ] Test article generation workflow
- [ ] Verify database connections
- [ ] Check Redis connectivity

---

**Production Ready**: ✅
**Estimated Deployment Time**: 30-45 minutes
**Rollback Time**: < 10 minutes (restore DB backup, revert code)

---

_AI Article Steps — Ainstein Platform_
_Laravel Multi-Tenant SaaS_
_Generated: October 2025_
