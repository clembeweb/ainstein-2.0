# 📖 Ainstein Platform - Quick Reference Guide

**Last Updated**: 2025-10-06
**For**: Quick access to commands and resources

---

## 🚀 INSTALL ON NEW MACHINE

### Option 1: Tell Claude (AI-Powered)

Open terminal on new machine and say to Claude:

```
Scarica e installa la piattaforma Ainstein seguendo questi step:
1. Verifica l'ambiente (PHP 8.3+, Composer, Node 18+, Git)
2. Clona la repository da: https://github.com/your-org/ainstein-3.git
3. Esegui l'installazione automatica con lo script install.sh
4. Configura database (chiedi se SQLite o MySQL)
5. Configura OpenAI API key (chiedi o usa mock service)
6. Verifica l'installazione (migrations, test, database)
7. Avvia il server di sviluppo
8. Mostrami le credenziali di accesso
```

Claude will do everything automatically!

**More details**: [CLAUDE-INSTALL-PROMPT.md](./CLAUDE-INSTALL-PROMPT.md)

---

### Option 2: Automated Script

```bash
git clone https://github.com/your-org/ainstein-3.git ainstein
cd ainstein
bash install.sh
```

**More details**: [INSTALLATION-GUIDE.md](./INSTALLATION-GUIDE.md)

---

## 🔑 DEFAULT CREDENTIALS

```
Demo Tenant Admin:  admin@demo.com / password
Demo Tenant Member: member@demo.com / password
Demo Tenant Guest:  guest@demo.com / password
```

---

## ⚡ COMMON COMMANDS

### Start Development Server
```bash
cd ainstein-laravel
php artisan serve
# Visit: http://localhost:8000
```

### Watch Frontend Assets (Hot Reload)
```bash
cd ainstein-laravel
npm run dev
```

### Run Tests
```bash
cd ainstein-laravel
php artisan test
```

### Interactive Shell (Tinker)
```bash
cd ainstein-laravel
php artisan tinker
```

### Clear All Caches
```bash
cd ainstein-laravel
php artisan optimize:clear
```

### Fresh Database
```bash
cd ainstein-laravel
php artisan migrate:fresh --seed
```

---

## 📚 DOCUMENTATION INDEX

| Document | Purpose | Size |
|----------|---------|------|
| **INSTALLATION-GUIDE.md** | Complete installation guide | 40 KB |
| **CLAUDE-INSTALL-PROMPT.md** | AI-powered installation | 15 KB |
| **ARCHITECTURE-OVERVIEW.md** | Technical architecture | 66 KB |
| **DEVELOPMENT-ROADMAP.md** | 6-month development plan | 72 KB |
| **SESSION-REPORT-2025-10-06.md** | Latest session report | 20 KB |
| **README.md** | Project overview | 8 KB |

---

## 🎯 QUICK NAVIGATION

### For Developers
1. Read: [ARCHITECTURE-OVERVIEW.md](./ARCHITECTURE-OVERVIEW.md)
2. See roadmap: [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md)
3. Check latest: [SESSION-REPORT-2025-10-06.md](./SESSION-REPORT-2025-10-06.md)

### For New Team Members
1. Install: [INSTALLATION-GUIDE.md](./INSTALLATION-GUIDE.md)
2. Learn: [ARCHITECTURE-OVERVIEW.md](./ARCHITECTURE-OVERVIEW.md)
3. Start coding: [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md) → Immediate Actions

### For Product Managers
1. Read: [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md)
2. Check priorities: Section "Immediate Actions (Week 1-2)"
3. See metrics: Section "Success Metrics"

---

## 🐛 TROUBLESHOOTING QUICK FIXES

### Icons Not Visible
```bash
# Check if FontAwesome is in layout
grep "font-awesome" ainstein-laravel/resources/views/layouts/app.blade.php

# Should show: line 15 with CDN link
```

### Database Connection Error
```bash
# Check .env database settings
cat ainstein-laravel/.env | grep DB_

# Test connection
cd ainstein-laravel
php artisan tinker --execute="DB::connection()->getPdo();"
```

### Assets Not Updating
```bash
cd ainstein-laravel
npm run build
php artisan view:clear
# Then hard refresh browser (Ctrl+F5)
```

### OpenAI API Error
```bash
# Check API key in .env
grep OPENAI_API_KEY ainstein-laravel/.env

# Test with mock service
cd ainstein-laravel
php artisan tinker --execute="
\$service = app(App\Services\AI\OpenAIService::class);
echo 'Service loaded successfully';
"
```

### Port 8000 In Use
```bash
# Use different port
php artisan serve --port=8080

# OR kill process on port 8000 (macOS/Linux)
lsof -ti:8000 | xargs kill -9
```

---

## 🔧 USEFUL TINKER COMMANDS

### Check Database Counts
```php
php artisan tinker --execute="
echo 'Tenants: ' . App\Models\Tenant::count() . PHP_EOL;
echo 'Users: ' . App\Models\User::count() . PHP_EOL;
echo 'Contents: ' . App\Models\Content::count() . PHP_EOL;
echo 'Prompts: ' . App\Models\Prompt::count() . PHP_EOL;
echo 'Generations: ' . App\Models\ContentGeneration::count() . PHP_EOL;
"
```

### Test OpenAI Service
```php
php artisan tinker --execute="
\$service = app(App\Services\AI\OpenAIService::class);
\$result = \$service->completion('Say hello');
print_r(\$result);
"
```

### Check Model Relations
```php
php artisan tinker --execute="
\$gen = App\Models\ContentGeneration::first();
echo 'Has content: ' . (\$gen->content ? 'YES' : 'NO') . PHP_EOL;
if (\$gen->content) {
    echo 'Content URL: ' . \$gen->content->url . PHP_EOL;
}
"
```

---

## 🎓 LEARNING RESOURCES

### Internal Docs
- [ARCHITECTURE-OVERVIEW.md](./ARCHITECTURE-OVERVIEW.md) - System architecture
- [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md) - Development plan
- [docs/](./docs/) - Detailed documentation folder

### External Resources
- **Laravel**: https://laravel.com/docs/12.x
- **Alpine.js**: https://alpinejs.dev
- **Tailwind CSS**: https://tailwindcss.com/docs
- **OpenAI API**: https://platform.openai.com/docs

---

## 📊 PROJECT STATUS (2025-10-06)

### ✅ Complete (100%)
- Content Generator (3-tab unified interface)
- OpenAI Service (production-ready, 11/11 tests)
- Guided Onboarding (13-step tour)
- Multi-tenancy (database isolation)
- Authentication & Authorization

### 🚧 In Progress (30%)
- Campaign Generator (database + UI done, service pending)

### ⏸️ Planned
- Real-time Status Updates (polling)
- Image Generation (DALL-E)
- Batch Operations
- Export Functionality
- Analytics Dashboard
- 15+ more features (see roadmap)

---

## ⏭️ NEXT IMMEDIATE TASKS

### This Week (P0/P1)
1. **[P0]** Complete Campaign Assets Generator Service (M, 2-3 days)
2. **[P0]** Set Up Production OpenAI API Key (XS, <1h)
3. **[P1]** Real-time Status Updates with Polling (S, 4-8h)
4. **[P1]** Copy to Clipboard Function (XS, 1h)
5. **[P1]** Add Database Indexes (XS, 2h)

**Full roadmap**: [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md)

---

## 🌐 URLS

### Development
- **Homepage**: http://localhost:8000
- **Dashboard**: http://localhost:8000/dashboard
- **Content Generator**: http://localhost:8000/dashboard/content
- **Campaigns**: http://localhost:8000/dashboard/campaigns

### API Endpoints (Future)
- **API Base**: http://localhost:8000/api/v1
- **Generate Content**: POST /api/v1/content/generate
- **List Generations**: GET /api/v1/content/generations

---

## 🔐 SECURITY CHECKLIST

### Development
- [x] CSRF protection enabled
- [x] Password hashing (bcrypt)
- [x] SQL injection prevention (Eloquent)
- [x] XSS protection (Blade auto-escaping)

### Production (Before Deploy)
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Change default passwords
- [ ] Configure SSL certificate
- [ ] Set up rate limiting
- [ ] Enable query logging
- [ ] Configure backups
- [ ] Set up monitoring (Sentry)

---

## 🎯 KEY METRICS

### Technical
- **Migrations**: 37
- **Models**: 7 core models
- **Controllers**: 6 tenant controllers
- **Services**: 8 service classes
- **Tests**: 11/11 passing ✅
- **Test Coverage**: 65% (target: 80%)

### Database (Current)
- **Tenants**: 1
- **Users**: 3
- **Contents (Pages)**: 22
- **Prompts**: 4 (system)
- **Generations**: 2 (completed)
- **Campaigns**: 7
- **Assets**: 6

---

## 🆘 GET HELP

### Issues During Installation
1. Check [INSTALLATION-GUIDE.md](./INSTALLATION-GUIDE.md) → Troubleshooting
2. Check logs: `tail -f ainstein-laravel/storage/logs/laravel.log`
3. Ask Claude: "L'installazione è fallita con questo errore: [paste error]"

### Issues During Development
1. Check [ARCHITECTURE-OVERVIEW.md](./ARCHITECTURE-OVERVIEW.md)
2. Check [SESSION-REPORT-2025-10-06.md](./SESSION-REPORT-2025-10-06.md) → Known Issues
3. Run tests: `php artisan test`
4. Ask Claude: "Ho questo problema: [describe issue]"

### Questions About Features
1. Check [DEVELOPMENT-ROADMAP.md](./DEVELOPMENT-ROADMAP.md)
2. Search in documentation: `grep -r "keyword" docs/`
3. Ask team or Claude

---

## 💡 TIPS & TRICKS

### Speed Up Development
```bash
# Use aliases in ~/.bashrc or ~/.zshrc
alias pas="php artisan serve"
alias pat="php artisan test"
alias pam="php artisan migrate"
alias pati="php artisan tinker"
alias nrd="npm run dev"
alias nrb="npm run build"
```

### Watch Logs Live
```bash
# Terminal 1: Server
php artisan serve

# Terminal 2: Assets
npm run dev

# Terminal 3: Logs
tail -f storage/logs/laravel.log

# Terminal 4: Tinker (for testing)
php artisan tinker
```

### Quick Database Reset
```bash
# Reset and seed in one command
php artisan migrate:fresh --seed
```

### Test Single File
```bash
# Run specific test
php artisan test --filter=OpenAIServiceTest

# Run with output
php artisan test --filter=OpenAIServiceTest --verbose
```

---

**This is your quick reference guide!**
Bookmark this page for fast access to commands and resources.

---

**Last Updated**: 2025-10-06
**Version**: 1.0.0
**Maintained By**: Ainstein Development Team
