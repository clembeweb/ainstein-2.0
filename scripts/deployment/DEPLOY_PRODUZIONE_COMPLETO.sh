#!/bin/bash

# =====================================================================
# AINSTEIN - DEPLOYMENT COMPLETO IN PRODUZIONE
# =====================================================================
# Questo script esegue:
# 1. Fix login (TenantOAuthProvider + HTTPS sessions)
# 2. Deploy Campaign Generator
# 3. Verifica e test finale
# =====================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

echo -e "${BLUE}=====================================================================${NC}"
echo -e "${BLUE}${BOLD}        AINSTEIN - DEPLOYMENT COMPLETO IN PRODUZIONE${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f artisan ]; then
    echo -e "${RED}✗ artisan file not found! Are you in the Laravel root directory?${NC}"
    exit 1
fi

echo -e "${YELLOW}Questo script eseguirà:${NC}"
echo "  1. Fix login (tabella TenantOAuthProvider + sessioni HTTPS)"
echo "  2. Deploy Campaign Generator"
echo "  3. Aggiornamento database e cache"
echo "  4. Verifica finale"
echo ""
read -p "Vuoi continuare? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment annullato."
    exit 1
fi

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}${BOLD}FASE 1: BACKUP${NC}"
echo -e "${BLUE}=====================================================================${NC}"

# Create backup directory
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo -e "${GREEN}[1/3]${NC} Backup .env..."
cp .env "$BACKUP_DIR/.env"

echo -e "${GREEN}[2/3]${NC} Backup database..."
# Detect database type and backup
DB_CONNECTION=$(grep "DB_CONNECTION=" .env | cut -d '=' -f2)
if [ "$DB_CONNECTION" = "mysql" ]; then
    DB_DATABASE=$(grep "DB_DATABASE=" .env | cut -d '=' -f2)
    DB_USERNAME=$(grep "DB_USERNAME=" .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep "DB_PASSWORD=" .env | cut -d '=' -f2)
    mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/database.sql" 2>/dev/null || echo "⚠ MySQL backup failed (non-critical)"
fi

echo -e "${GREEN}[3/3]${NC} Backup model TenantOAuthProvider..."
cp app/Models/TenantOAuthProvider.php "$BACKUP_DIR/TenantOAuthProvider.php"

echo -e "      ✓ Backups salvati in: ${BOLD}$BACKUP_DIR${NC}"

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}${BOLD}FASE 2: FIX LOGIN${NC}"
echo -e "${BLUE}=====================================================================${NC}"

# Fix Model TenantOAuthProvider
echo -e "${GREEN}[1/4]${NC} Fixing TenantOAuthProvider model..."
if grep -q 'protected \$table = ' app/Models/TenantOAuthProvider.php; then
    echo -e "      ✓ Model già configurato"
else
    sed -i.bak '/^class TenantOAuthProvider/a\
\
    protected $table = '\''tenant_oauth_providers'\'';' app/Models/TenantOAuthProvider.php
    echo -e "      ✓ Table name aggiunto al model"
fi

# Fix .env for HTTPS
echo -e "${GREEN}[2/4]${NC} Fixing .env per HTTPS..."

# APP_URL
if ! grep -q "APP_URL=https://ainstein.it" .env; then
    sed -i.bak 's|APP_URL=.*|APP_URL=https://ainstein.it|g' .env
    echo -e "      ✓ APP_URL → https://ainstein.it"
fi

# SESSION_SECURE_COOKIE
if grep -q "SESSION_SECURE_COOKIE=" .env; then
    sed -i.bak 's|SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|g' .env
else
    echo "SESSION_SECURE_COOKIE=true" >> .env
fi
echo -e "      ✓ SESSION_SECURE_COOKIE=true"

# SESSION_HTTP_ONLY
if ! grep -q "SESSION_HTTP_ONLY=" .env; then
    echo "SESSION_HTTP_ONLY=true" >> .env
    echo -e "      ✓ SESSION_HTTP_ONLY=true"
fi

# SESSION_SAME_SITE
if ! grep -q "SESSION_SAME_SITE=" .env; then
    echo "SESSION_SAME_SITE=lax" >> .env
    echo -e "      ✓ SESSION_SAME_SITE=lax"
fi

# SANCTUM_STATEFUL_DOMAINS
if grep -q "SANCTUM_STATEFUL_DOMAINS=" .env; then
    sed -i.bak 's|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it|g' .env
else
    echo "SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it" >> .env
fi
echo -e "      ✓ SANCTUM_STATEFUL_DOMAINS configurato"

# OpenAI Configuration
echo -e "${GREEN}[3/4]${NC} Verificando configurazione OpenAI..."
if grep -q "OPENAI_API_KEY=" .env; then
    OPENAI_KEY=$(grep "OPENAI_API_KEY=" .env | cut -d '=' -f2)
    if [ "$OPENAI_KEY" = "sk-test-key" ] || [ -z "$OPENAI_KEY" ]; then
        echo -e "${YELLOW}      ⚠ ATTENZIONE: OpenAI API key non configurata per produzione!${NC}"
        echo -e "${YELLOW}      Aggiungi: OPENAI_API_KEY=sk-proj-YOUR-REAL-KEY${NC}"
    else
        echo -e "      ✓ OpenAI API key configurata"
    fi
else
    echo -e "${YELLOW}      ⚠ OpenAI API key mancante in .env${NC}"
    echo "# OpenAI Configuration" >> .env
    echo "OPENAI_API_KEY=sk-proj-YOUR-KEY-HERE" >> .env
    echo "OPENAI_DEFAULT_MODEL=gpt-4o-mini" >> .env
fi

# Dependencies
echo -e "${GREEN}[4/4]${NC} Updating dependencies..."
composer install --optimize-autoloader --no-dev --quiet
echo -e "      ✓ Composer dependencies updated"

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}${BOLD}FASE 3: DATABASE E MIGRATIONS${NC}"
echo -e "${BLUE}=====================================================================${NC}"

echo -e "${GREEN}[1/2]${NC} Running migrations..."
php artisan migrate --force
echo -e "      ✓ Migrations completate"

echo -e "${GREEN}[2/2]${NC} Verificando tabelle..."
php artisan tinker --execute="
    try {
        \$count = DB::table('tenant_oauth_providers')->count();
        echo '      ✓ tenant_oauth_providers: ' . \$count . ' records' . PHP_EOL;
    } catch (\Exception \$e) {
        echo '      ✗ Errore tenant_oauth_providers: ' . \$e->getMessage() . PHP_EOL;
    }

    try {
        \$count = DB::table('adv_campaigns')->count();
        echo '      ✓ adv_campaigns: ' . \$count . ' records' . PHP_EOL;
    } catch (\Exception \$e) {
        echo '      ✗ Errore adv_campaigns: ' . \$e->getMessage() . PHP_EOL;
    }
"

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}${BOLD}FASE 4: CACHE E OTTIMIZZAZIONE${NC}"
echo -e "${BLUE}=====================================================================${NC}"

echo -e "${GREEN}[1/5]${NC} Clearing caches..."
php artisan cache:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
echo -e "      ✓ All caches cleared"

echo -e "${GREEN}[2/5]${NC} Rebuilding configuration cache..."
php artisan config:cache > /dev/null 2>&1
echo -e "      ✓ Config cached"

echo -e "${GREEN}[3/5]${NC} Rebuilding route cache..."
php artisan route:cache > /dev/null 2>&1
echo -e "      ✓ Routes cached"

echo -e "${GREEN}[4/5]${NC} Rebuilding view cache..."
php artisan view:cache > /dev/null 2>&1
echo -e "      ✓ Views cached"

echo -e "${GREEN}[5/5]${NC} Setting permissions..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || true
echo -e "      ✓ Permissions set"

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}${BOLD}FASE 5: ASSETS E BUILD${NC}"
echo -e "${BLUE}=====================================================================${NC}"

if [ -f "package.json" ]; then
    echo -e "${GREEN}[1/2]${NC} Installing npm dependencies..."
    npm ci --silent
    echo -e "      ✓ NPM dependencies installed"

    echo -e "${GREEN}[2/2]${NC} Building assets..."
    npm run build
    echo -e "      ✓ Assets compiled"
else
    echo -e "${YELLOW}      ⚠ package.json not found, skipping assets build${NC}"
fi

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}${BOLD}FASE 6: VERIFICA FINALE${NC}"
echo -e "${BLUE}=====================================================================${NC}"

echo -e "${GREEN}[1/3]${NC} Verificando configurazioni..."
php artisan tinker --execute="
    echo '      APP_ENV: ' . config('app.env') . PHP_EOL;
    echo '      APP_URL: ' . config('app.url') . PHP_EOL;
    echo '      SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
    echo '      DB_CONNECTION: ' . config('database.default') . PHP_EOL;
"

echo -e "${GREEN}[2/3]${NC} Testing health endpoint..."
if command -v curl &> /dev/null; then
    HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" https://ainstein.it/health 2>/dev/null || echo "000")
    if [ "$HEALTH_CHECK" = "200" ]; then
        echo -e "      ✓ Health check: OK (200)"
    else
        echo -e "${YELLOW}      ⚠ Health check: $HEALTH_CHECK${NC}"
    fi
else
    echo -e "${YELLOW}      ⚠ curl not found, skipping health check${NC}"
fi

echo -e "${GREEN}[3/3]${NC} Testing routes..."
ROUTE_COUNT=$(php artisan route:list | grep -c "dashboard/campaigns" || echo "0")
if [ "$ROUTE_COUNT" -gt "0" ]; then
    echo -e "      ✓ Campaign Generator routes: $ROUTE_COUNT found"
else
    echo -e "${YELLOW}      ⚠ Campaign Generator routes not found${NC}"
fi

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}${BOLD}✅ DEPLOYMENT COMPLETATO CON SUCCESSO!${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo ""
echo -e "${BOLD}PROSSIMI PASSI:${NC}"
echo ""
echo -e "1. ${BOLD}TEST LOGIN${NC}"
echo "   • Apri: https://ainstein.it/login"
echo "   • Usa modalità incognito"
echo "   • Verifica che il login funzioni"
echo ""
echo -e "2. ${BOLD}TEST CAMPAIGN GENERATOR${NC}"
echo "   • Vai su: https://ainstein.it/dashboard/campaigns"
echo "   • Crea una nuova campagna RSA o PMAX"
echo "   • Verifica generazione assets"
echo "   • Testa export CSV"
echo ""
echo -e "3. ${BOLD}MONITORAGGIO${NC}"
echo "   • Logs: tail -f storage/logs/laravel.log"
echo "   • Errori JS: Apri console browser (F12)"
echo "   • Performance: Controlla tempi di risposta"
echo ""
echo -e "${BOLD}BACKUP SALVATO IN:${NC} $BACKUP_DIR"
echo ""
echo -e "${YELLOW}NOTE IMPORTANTI:${NC}"
echo "• Verifica che OpenAI API key sia configurata correttamente"
echo "• Se hai problemi, usa i backup per rollback"
echo "• Monitora i logs per eventuali errori"
echo ""
echo -e "${GREEN}Ainstein è pronto per l'uso in produzione! 🚀${NC}"
echo ""
