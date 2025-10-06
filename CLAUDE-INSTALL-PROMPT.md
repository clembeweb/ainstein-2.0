# 🤖 Prompt for Claude - Automated Installation

Use this prompt when you want Claude to install Ainstein Platform on a new machine automatically.

---

## 📋 COPY THIS TO CLAUDE

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

Se trovi errori, diagnosticali e risolvili autonomamente.
Al termine, dammi un report completo dell'installazione.
```

---

## 🎯 WHAT CLAUDE WILL DO

Claude will automatically:

1. ✅ **Check system requirements**
   - PHP version and extensions
   - Composer version
   - Node.js and NPM
   - Git availability

2. ✅ **Clone repository**
   ```bash
   git clone https://github.com/your-org/ainstein-3.git ainstein
   cd ainstein
   ```

3. ✅ **Run automated installer**
   ```bash
   bash install.sh
   ```

4. ✅ **Configure environment**
   - Ask for database type (SQLite/MySQL)
   - Ask for OpenAI API key
   - Create .env file
   - Generate application key

5. ✅ **Setup database**
   - Run migrations (37 tables)
   - Seed demo data (1 tenant, 3 users, 4 prompts)

6. ✅ **Build assets**
   ```bash
   npm run build
   ```

7. ✅ **Verify installation**
   - Database connection test
   - Run unit tests (11 tests)
   - Check assets compiled

8. ✅ **Start server**
   ```bash
   php artisan serve
   ```

9. ✅ **Display credentials**
   - Demo tenant admin: admin@demo.com / password
   - Demo tenant member: member@demo.com / password
   - Access URL: http://localhost:8000

---

## 🔍 TROUBLESHOOTING

If Claude encounters errors, it will:

1. **Diagnose the issue**
   - Read error messages
   - Check logs (storage/logs/laravel.log)
   - Identify root cause

2. **Suggest fixes**
   - Missing PHP extensions → Install command
   - Database connection error → Config check
   - Permission errors → chmod commands
   - Port in use → Alternative port

3. **Apply fixes automatically**
   - Run fix commands
   - Verify fix worked
   - Continue installation

4. **Report problems**
   - If can't auto-fix, explain issue
   - Provide manual fix instructions
   - Document in final report

---

## 📊 EXPECTED OUTPUT

After successful installation, Claude will display:

```
╔══════════════════════════════════════════════════════════════════╗
║  ✅ Installation Complete!  ║
╚══════════════════════════════════════════════════════════════════╝

📋 Installation Summary:

Environment:
  ✓ PHP 8.3.12
  ✓ Composer 2.7.1
  ✓ Node.js 20.10.0
  ✓ NPM 10.2.3

Database:
  ✓ SQLite (database.sqlite)
  ✓ 37 migrations applied
  ✓ Seed data inserted

Assets:
  ✓ Frontend built (Vite)
  ✓ 123 KB JavaScript
  ✓ 45 KB CSS

Tests:
  ✓ 11/11 unit tests passing

Server:
  ✓ Running on http://localhost:8000

📋 Default Credentials:

  Demo Tenant Admin:
    Email:    admin@demo.com
    Password: password

  Demo Tenant Member:
    Email:    member@demo.com
    Password: password

🌐 Access the platform at: http://localhost:8000

📚 Documentation:
  - INSTALLATION-GUIDE.md       (Complete installation guide)
  - ARCHITECTURE-OVERVIEW.md     (Technical architecture)
  - DEVELOPMENT-ROADMAP.md       (6-month roadmap)

⚠️  Remember to change default passwords in production!
```

---

## 🎯 USE CASES

### Scenario 1: Fresh Installation on New Machine

**User says**:
```
Scarica e installa Ainstein Platform su questa macchina.
```

**Claude will**:
1. Check requirements
2. Clone repo
3. Run install.sh
4. Configure everything
5. Start server
6. Show credentials

---

### Scenario 2: Reinstall After Corruption

**User says**:
```
La mia installazione è corrotta. Reinstalla tutto da zero.
```

**Claude will**:
1. Backup current .env (if exists)
2. Remove old files
3. Fresh clone
4. Run install.sh
5. Restore .env settings
6. Verify all working

---

### Scenario 3: Install on Different OS

**User says**:
```
Sto su macOS/Linux/Windows. Installa Ainstein Platform.
```

**Claude will**:
1. Detect OS automatically
2. Adapt commands for OS
   - macOS: brew commands
   - Linux: apt/yum commands
   - Windows: Windows-specific paths
3. Install with OS-specific fixes
4. Verify all working

---

### Scenario 4: Custom Configuration

**User says**:
```
Installa Ainstein con MySQL invece di SQLite, e usa questa OpenAI key: sk-abc123
```

**Claude will**:
1. Run installer
2. When asked for database: Choose MySQL
3. When asked for OpenAI key: Use provided key
4. Complete installation
5. Verify MySQL connection
6. Test OpenAI integration

---

## 🔐 SECURITY NOTES

### What Claude WON'T Do

❌ **Commit credentials to Git**
❌ **Share API keys in logs**
❌ **Use production database for testing**
❌ **Skip security checks**
❌ **Bypass authentication**

### What Claude WILL Do

✅ **Keep credentials in .env only**
✅ **Use mock service if no API key**
✅ **Set proper file permissions**
✅ **Enable CSRF protection**
✅ **Use secure database connections**

---

## 📝 MANUAL INSTALLATION (If Automated Fails)

If the automated script fails, Claude will guide you through manual installation:

```bash
# 1. Clone repository
git clone https://github.com/your-org/ainstein-3.git ainstein
cd ainstein/ainstein-laravel

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Configure database (edit .env)
nano .env

# 5. Setup database
touch database/database.sqlite
php artisan migrate --seed

# 6. Build assets
npm run build

# 7. Start server
php artisan serve
```

---

## 🎓 LEARNING MODE

If you want Claude to explain each step:

```
Installa Ainstein Platform, ma spiegami ogni step che fai e perché.
```

Claude will:
- Explain what each command does
- Show command output
- Explain why it's needed
- Teach you the installation process

---

## 🚀 QUICK START (No Explanation)

If you just want it done fast:

```
Installa Ainstein Platform in modalità silenziosa. Solo risultato finale.
```

Claude will:
- Run all commands without explanation
- Only show errors if any
- Display final success message
- Give you credentials and URL

---

## ✅ SUCCESS CRITERIA

Installation is successful when:

- [x] All requirements met (PHP, Composer, Node, Git)
- [x] Repository cloned
- [x] Dependencies installed (Composer, NPM)
- [x] Environment configured (.env)
- [x] Database created and migrated (37 tables)
- [x] Seed data inserted (1 tenant, 3 users, 4 prompts)
- [x] Assets built (JavaScript, CSS)
- [x] Tests passing (11/11)
- [x] Server running (http://localhost:8000)
- [x] Can login with demo credentials
- [x] Content Generator loads correctly
- [x] All icons visible (FontAwesome)

---

## 📞 SUPPORT

If you encounter issues during installation:

1. **Check logs**:
   ```bash
   tail -f ainstein-laravel/storage/logs/laravel.log
   ```

2. **Re-run installer**:
   ```bash
   cd ainstein
   bash install.sh
   ```

3. **Manual installation**:
   See [INSTALLATION-GUIDE.md](./INSTALLATION-GUIDE.md)

4. **Ask Claude**:
   ```
   L'installazione è fallita con questo errore: [paste error]
   Diagnostica e risolvi il problema.
   ```

---

**Ready to install?** Copy the prompt above and paste it to Claude! 🚀

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-06
**Maintained By**: Ainstein Development Team
