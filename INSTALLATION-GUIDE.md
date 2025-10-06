# 🚀 Ainstein Platform - Complete Installation Guide

**Last Updated**: 2025-10-06
**Version**: 1.0.0
**Supported OS**: Windows, macOS, Linux

---

## 📋 TABLE OF CONTENTS

1. [Quick Install (One Command)](#quick-install-one-command)
2. [Prerequisites](#prerequisites)
3. [Manual Installation](#manual-installation)
4. [Environment Setup](#environment-setup)
5. [Database Setup](#database-setup)
6. [Verification](#verification)
7. [Troubleshooting](#troubleshooting)
8. [Next Steps](#next-steps)

---

## ⚡ QUICK INSTALL (One Command)

### Option 1: Automated Script (Recommended)

Open terminal and run:

```bash
# Clone and run automated installer
git clone https://github.com/your-org/ainstein-3.git ainstein
cd ainstein
bash install.sh
```

The script will:
- ✅ Check system requirements
- ✅ Install dependencies (Composer, NPM)
- ✅ Configure environment (.env)
- ✅ Setup database with seed data
- ✅ Build frontend assets
- ✅ Start development server
- ✅ Display login credentials

**Total time**: ~5-10 minutes

---

### Option 2: One-Liner (Bash)

```bash
curl -sSL https://raw.githubusercontent.com/your-org/ainstein-3/main/install.sh | bash
```

⚠️ **Warning**: Always review scripts before running with `curl | bash`

---

## 📦 PREREQUISITES

### Required Software

| Software | Min Version | Check Command | Install Link |
|----------|-------------|---------------|--------------|
| **PHP** | 8.3+ | `php -v` | https://www.php.net/downloads |
| **Composer** | 2.6+ | `composer -V` | https://getcomposer.org/download/ |
| **Node.js** | 18+ | `node -v` | https://nodejs.org/ |
| **NPM** | 9+ | `npm -v` | (included with Node.js) |
| **Git** | 2.x | `git --version` | https://git-scm.com/downloads |

### Optional (Recommended)

| Software | Purpose | Check Command |
|----------|---------|---------------|
| **MySQL** | Production database | `mysql --version` |
| **Redis** | Queue & cache | `redis-cli --version` |
| **SQLite** | Development database | `sqlite3 --version` |

### PHP Extensions Required

```bash
# Check installed extensions
php -m

# Required extensions:
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- cURL
- GD (for image manipulation)
- SQLite (for dev) or MySQL (for prod)
```

**Install missing extensions**:

**Ubuntu/Debian**:
```bash
sudo apt install php8.3-bcmath php8.3-curl php8.3-gd php8.3-mbstring php8.3-xml php8.3-sqlite3
```

**macOS (Homebrew)**:
```bash
brew install php@8.3
```

**Windows (Laragon/XAMPP)**:
- Extensions are pre-installed, just enable them in `php.ini`

---

## 🛠️ MANUAL INSTALLATION

### Step 1: Clone Repository

```bash
# HTTPS
git clone https://github.com/your-org/ainstein-3.git ainstein
cd ainstein

# OR SSH
git clone git@github.com:your-org/ainstein-3.git ainstein
cd ainstein
```

**Verify**:
```bash
ls -la
# Should see: ainstein-laravel/, docs/, README.md, etc.
```

---

### Step 2: Navigate to Laravel Directory

```bash
cd ainstein-laravel
pwd
# Should output: /path/to/ainstein/ainstein-laravel
```

---

### Step 3: Install PHP Dependencies

```bash
composer install
```

**Expected output**:
```
Loading composer repositories with package information
Installing dependencies from lock file
...
Generating optimized autoload files
> @php artisan package:discover --ansi
Discovered Package: ...
```

**If errors occur**, see [Troubleshooting](#troubleshooting)

---

### Step 4: Install Node.js Dependencies

```bash
npm install
```

**Expected output**:
```
added 1234 packages in 30s
```

---

### Step 5: Environment Configuration

```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

**Expected output**:
```
Application key set successfully.
```

---

### Step 6: Configure Environment Variables

Edit `.env` file:

```bash
# Open with your preferred editor
nano .env
# OR
vim .env
# OR (Windows)
notepad .env
```

**Required Configuration**:

```env
# Application
APP_NAME="Ainstein Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (SQLite for dev, MySQL for prod)
DB_CONNECTION=sqlite
# DB_CONNECTION=mysql  # Uncomment for MySQL
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ainstein
# DB_USERNAME=root
# DB_PASSWORD=

# OpenAI API (REQUIRED for AI features)
OPENAI_API_KEY=sk-your-key-here

# Queue (optional, defaults to sync)
QUEUE_CONNECTION=sync
# QUEUE_CONNECTION=redis  # For production

# Cache (optional)
CACHE_STORE=file
# CACHE_STORE=redis  # For production

# Session
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@ainstein.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**⚠️ IMPORTANT**: Replace `OPENAI_API_KEY` with your actual key from https://platform.openai.com/api-keys

**For production**, also set:
- `APP_ENV=production`
- `APP_DEBUG=false`
- Use MySQL database
- Configure Redis for cache/queue
- Set up email (SMTP)

---

### Step 7: Database Setup

#### Option A: SQLite (Development - Recommended)

```bash
# Create SQLite database file
touch database/database.sqlite

# Verify file exists
ls -lh database/database.sqlite
```

#### Option B: MySQL (Production)

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE ainstein CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Verify
mysql -u root -p -e "SHOW DATABASES LIKE 'ainstein';"
```

Then update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ainstein
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

---

### Step 8: Run Migrations & Seeders

```bash
# Run all migrations (creates 37 tables)
php artisan migrate --seed
```

**Expected output**:
```
Migration table created successfully.
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table (123.45ms)
...
Migrating: 2025_10_06_135605_fix_content_generations_foreign_key_to_contents
Migrated:  2025_10_06_135605_fix_content_generations_foreign_key_to_contents (45.67ms)

Database seeding completed successfully.
```

**Seeded Data**:
- ✅ 1 Tenant: "Demo Company"
- ✅ 3 Users: admin@demo.com, member@demo.com, guest@demo.com
- ✅ 4 System Prompts (blog, seo, ecommerce)
- ✅ Sample pages and content generations

**If errors occur**, see [Troubleshooting](#troubleshooting)

---

### Step 9: Build Frontend Assets

```bash
# Production build
npm run build

# OR for development with hot reload
npm run dev
```

**Expected output** (build):
```
vite v5.x.x building for production...
✓ 1234 modules transformed.
dist/assets/app-abc123.js    123.45 kB
dist/assets/app-def456.css   45.67 kB
✓ built in 12.34s
```

---

### Step 10: Start Development Server

```bash
php artisan serve
```

**Expected output**:
```
   INFO  Server running on [http://127.0.0.1:8000].

  Press Ctrl+C to stop the server
```

**🎉 Installation Complete!**

Visit: http://localhost:8000

---

## 🔑 DEFAULT CREDENTIALS

### Super Admin (Platform Owner)
```
Email:    admin@ainstein.com
Password: password
```

### Demo Tenant Admin
```
Email:    admin@demo.com
Password: password
```

### Demo Tenant Member
```
Email:    member@demo.com
Password: password
```

### Demo Tenant Guest
```
Email:    guest@demo.com
Password: password
```

⚠️ **IMPORTANT**: Change these passwords in production!

---

## ✅ VERIFICATION

### 1. Check Laravel Installation

```bash
php artisan about
```

**Expected output**:
```
Environment .......................... local
Debug Mode ........................... ENABLED
URL .................................. http://localhost:8000
Timezone ............................. UTC
Locale ............................... en
Database ............................. sqlite (database.sqlite)
```

---

### 2. Check Database Connection

```bash
php artisan tinker --execute="
echo 'Tenants: ' . App\Models\Tenant::count() . PHP_EOL;
echo 'Users: ' . App\Models\User::count() . PHP_EOL;
echo 'Contents: ' . App\Models\Content::count() . PHP_EOL;
echo 'Prompts: ' . App\Models\Prompt::count() . PHP_EOL;
"
```

**Expected output**:
```
Tenants: 1
Users: 3
Contents: 0 (or more if seeded)
Prompts: 4
```

---

### 3. Run Tests

```bash
php artisan test
```

**Expected output**:
```
  PASS  Tests\Unit\Services\AI\OpenAIServiceTest
  ✓ it initializes with mock service when fake key
  ✓ it can perform chat completion
  ... (11 tests total)

  Tests:    11 passed (28 assertions)
  Duration: 17.46s
```

---

### 4. Check Frontend Assets

Visit: http://localhost:8000

**Verify**:
- ✅ Homepage loads
- ✅ Styles applied (Tailwind CSS)
- ✅ Icons visible (FontAwesome)
- ✅ Login form works
- ✅ Can login with demo credentials

---

### 5. Test Content Generator

1. Login with `admin@demo.com` / `password`
2. Navigate to: http://localhost:8000/dashboard/content
3. Verify:
   - ✅ 3 tabs visible (Pages, Generations, Prompts)
   - ✅ "Tour Guidato" button visible (top-right)
   - ✅ Icons displayed correctly
   - ✅ Can click tabs and switch views

---

## 🐛 TROUBLESHOOTING

### Issue 1: "composer: command not found"

**Cause**: Composer not installed or not in PATH

**Fix**:
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify
composer -V
```

---

### Issue 2: "PHP extension ... is missing"

**Cause**: Required PHP extension not installed

**Fix (Ubuntu/Debian)**:
```bash
# Example: Missing mbstring
sudo apt install php8.3-mbstring

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

**Fix (macOS)**:
```bash
brew install php@8.3
brew services restart php@8.3
```

---

### Issue 3: "SQLSTATE[HY000]: General error: 1 no such table"

**Cause**: Migrations not run or database file missing

**Fix**:
```bash
# SQLite: Create database file
touch database/database.sqlite

# Run migrations again
php artisan migrate:fresh --seed
```

---

### Issue 4: "Vite manifest not found"

**Cause**: Frontend assets not built

**Fix**:
```bash
# Build assets
npm run build

# Clear view cache
php artisan view:clear
```

---

### Issue 5: "OpenAI API error: Invalid API key"

**Cause**: `OPENAI_API_KEY` not set or invalid

**Fix**:
1. Get API key from https://platform.openai.com/api-keys
2. Add to `.env`:
   ```env
   OPENAI_API_KEY=sk-your-actual-key-here
   ```
3. Clear config cache:
   ```bash
   php artisan config:clear
   ```

**Alternative**: Use mock service (for testing):
```env
OPENAI_API_KEY=fake-key-for-testing
```
Mock service will auto-activate.

---

### Issue 6: "Port 8000 already in use"

**Cause**: Another process using port 8000

**Fix**:
```bash
# Option 1: Use different port
php artisan serve --port=8080

# Option 2: Kill process using port 8000
# Windows:
netstat -ano | findstr :8000
taskkill /PID <PID> /F

# macOS/Linux:
lsof -ti:8000 | xargs kill -9
```

---

### Issue 7: "Permission denied" errors

**Cause**: Wrong file permissions

**Fix (Linux/macOS)**:
```bash
# Set correct permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# OR (for development)
chmod -R 777 storage bootstrap/cache
```

**Fix (Windows)**: Run terminal as Administrator

---

### Issue 8: Icons not visible in browser

**Cause**: FontAwesome not loaded

**Fix**: Already fixed in this session! Verify:
```bash
grep -n "font-awesome" resources/views/layouts/app.blade.php
# Should show line 15 with FontAwesome CDN
```

If missing, add to `resources/views/layouts/app.blade.php` after line 12:
```html
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
```

---

## 🎯 NEXT STEPS

### 1. Explore the Platform

**Login** as `admin@demo.com` / `password`

**Navigate to**:
- Dashboard: http://localhost:8000/dashboard
- Content Generator: http://localhost:8000/dashboard/content
- Campaigns: http://localhost:8000/dashboard/campaigns

**Try**:
1. Click "Tour Guidato" in Content Generator (13-step guided tour)
2. Create a new page
3. Generate AI content
4. View/edit/copy generated content

---

### 2. Configure OpenAI API

**Get API Key**:
1. Sign up at https://platform.openai.com/
2. Go to https://platform.openai.com/api-keys
3. Create new secret key
4. Copy key (starts with `sk-`)

**Add to `.env`**:
```env
OPENAI_API_KEY=sk-your-actual-key-here
```

**Test**:
```bash
php artisan tinker --execute="
\$service = app(App\Services\AI\OpenAIService::class);
\$result = \$service->completion('Say hello in 3 words');
print_r(\$result);
"
```

---

### 3. Read Documentation

**Essential Reading**:
1. **ARCHITECTURE-OVERVIEW.md** - Complete technical architecture
2. **DEVELOPMENT-ROADMAP.md** - 6-month development plan
3. **SESSION-REPORT-2025-10-06.md** - Latest changes and fixes

**Quick Links**:
- Architecture: [ARCHITECTURE-OVERVIEW.md](./ARCHITECTURE-OVERVIEW.md)
- Roadmap: [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md)
- Session Report: [SESSION-REPORT-2025-10-06.md](./SESSION-REPORT-2025-10-06.md)

---

### 4. Start Development

**Common Commands**:
```bash
# Start dev server
php artisan serve

# Watch assets (separate terminal)
npm run dev

# Run tests
php artisan test

# Interactive testing
php artisan tinker

# Clear all caches
php artisan optimize:clear
```

**Next Development Tasks**:
See [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md) → Immediate Actions (Week 1-2)

Priority tasks:
1. Complete Campaign Assets Generator Service (P0)
2. Set up production OpenAI API key (P0)
3. Add real-time status updates (P1)
4. Fix copy to clipboard function (P1)

---

## 🚀 PRODUCTION DEPLOYMENT

### Pre-Deployment Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Configure MySQL database (not SQLite)
- [ ] Set real `OPENAI_API_KEY`
- [ ] Configure email (SMTP)
- [ ] Set up Redis for cache/queue
- [ ] Configure CDN (Cloudflare)
- [ ] Set up SSL certificate
- [ ] Configure backups
- [ ] Set up monitoring (Sentry, UptimeRobot)

**Deployment Commands**:
```bash
# Build assets for production
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Restart queue worker
php artisan queue:restart
```

**Detailed Guide**: See [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md) → Deployment Plan

---

## 📞 SUPPORT

### Documentation
- **Architecture**: [ARCHITECTURE-OVERVIEW.md](./ARCHITECTURE-OVERVIEW.md)
- **Roadmap**: [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md)
- **Latest Session**: [SESSION-REPORT-2025-10-06.md](./SESSION-REPORT-2025-10-06.md)

### External Resources
- **Laravel Docs**: https://laravel.com/docs/12.x
- **Alpine.js**: https://alpinejs.dev
- **OpenAI API**: https://platform.openai.com/docs
- **Tailwind CSS**: https://tailwindcss.com/docs

### Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue logs
php artisan queue:work --verbose

# Database queries (enable in .env)
DB_LOG_QUERIES=true
```

---

## 📊 SYSTEM REQUIREMENTS

### Minimum (Development)
- **CPU**: 2 cores
- **RAM**: 4 GB
- **Disk**: 5 GB free space
- **OS**: Windows 10+, macOS 11+, Ubuntu 20.04+

### Recommended (Production)
- **CPU**: 4+ cores
- **RAM**: 8+ GB
- **Disk**: 20+ GB SSD
- **OS**: Ubuntu 22.04 LTS
- **Web Server**: Nginx or Apache
- **PHP-FPM**: With opcache enabled
- **Database**: MySQL 8.0+ or PostgreSQL 14+
- **Redis**: For cache and queue

---

## ✅ VERIFICATION CHECKLIST

After installation, verify:

**Backend**:
- [ ] `php artisan about` shows correct info
- [ ] Database has tables (37 migrations)
- [ ] Seeders created demo data (1 tenant, 3 users, 4 prompts)
- [ ] Tests pass: `php artisan test` (11/11)

**Frontend**:
- [ ] Homepage loads without errors
- [ ] Styles applied (Tailwind CSS)
- [ ] Icons visible (FontAwesome)
- [ ] JavaScript works (Alpine.js)

**Features**:
- [ ] Can login with demo credentials
- [ ] Content Generator loads (3 tabs)
- [ ] "Tour Guidato" button visible
- [ ] Can create page
- [ ] Can view prompts (4 system prompts)

**AI Integration**:
- [ ] OpenAI API key configured
- [ ] Mock service works (if no key)
- [ ] Can generate test content

If ALL checkboxes are ✅, installation is successful!

---

**Installation Guide Complete** ✅

**Estimated Installation Time**: 5-15 minutes (depending on internet speed)

**Status**: Ready for development or production deployment

---

**Last Updated**: 2025-10-06
**Version**: 1.0.0
**Maintained By**: Ainstein Development Team
