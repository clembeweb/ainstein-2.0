#!/bin/bash

# =====================================================================
# AINSTEIN - FIX LOGIN PRODUZIONE COMPLETO
# =====================================================================
# Fix 1: Nome tabella TenantOAuthProvider
# Fix 2: Configurazioni SESSION per HTTPS
# =====================================================================

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=====================================================================${NC}"
echo -e "${BLUE}        AINSTEIN - FIX LOGIN PRODUZIONE COMPLETO                     ${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f artisan ]; then
    echo -e "${RED}✗ artisan file not found! Are you in the Laravel root directory?${NC}"
    exit 1
fi

# STEP 1: Backup
echo -e "${GREEN}[1/5]${NC} Creating backups..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
cp app/Models/TenantOAuthProvider.php app/Models/TenantOAuthProvider.php.backup.$(date +%Y%m%d_%H%M%S)
echo -e "      ✓ Backups created"

# STEP 2: Fix Model (nome tabella)
echo -e "${GREEN}[2/5]${NC} Fixing TenantOAuthProvider model..."

# Check if the fix is already applied
if grep -q 'protected \$table = ' app/Models/TenantOAuthProvider.php; then
    echo -e "      ✓ Model already has explicit table name"
else
    # Add the table name after the class declaration
    sed -i.bak '/^class TenantOAuthProvider/a\
\
    protected $table = '\''tenant_oauth_providers'\'';' app/Models/TenantOAuthProvider.php
    echo -e "      ✓ Added explicit table name to model"
fi

# STEP 3: Fix .env configurations
echo -e "${GREEN}[3/5]${NC} Fixing .env configurations for HTTPS..."

# APP_URL
if grep -q "APP_URL=http://" .env; then
    sed -i.bak 's|APP_URL=http://|APP_URL=https://|g' .env
    echo -e "      ✓ APP_URL updated to HTTPS"
elif ! grep -q "APP_URL=https://ainstein.it" .env; then
    sed -i.bak 's|APP_URL=.*|APP_URL=https://ainstein.it|g' .env
    echo -e "      ✓ APP_URL set to https://ainstein.it"
else
    echo -e "      ✓ APP_URL already correct"
fi

# SESSION_SECURE_COOKIE
if grep -q "SESSION_SECURE_COOKIE=" .env; then
    sed -i.bak 's|SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|g' .env
    echo -e "      ✓ SESSION_SECURE_COOKIE updated"
else
    echo "SESSION_SECURE_COOKIE=true" >> .env
    echo -e "      ✓ SESSION_SECURE_COOKIE added"
fi

# SESSION_HTTP_ONLY
if ! grep -q "SESSION_HTTP_ONLY=" .env; then
    echo "SESSION_HTTP_ONLY=true" >> .env
    echo -e "      ✓ SESSION_HTTP_ONLY added"
fi

# SESSION_SAME_SITE
if ! grep -q "SESSION_SAME_SITE=" .env; then
    echo "SESSION_SAME_SITE=lax" >> .env
    echo -e "      ✓ SESSION_SAME_SITE added"
fi

# SANCTUM_STATEFUL_DOMAINS
if grep -q "SANCTUM_STATEFUL_DOMAINS=" .env; then
    sed -i.bak 's|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it|g' .env
    echo -e "      ✓ SANCTUM_STATEFUL_DOMAINS updated"
else
    echo "SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it" >> .env
    echo -e "      ✓ SANCTUM_STATEFUL_DOMAINS added"
fi

# STEP 4: Clear and rebuild cache
echo -e "${GREEN}[4/5]${NC} Clearing and rebuilding cache..."
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
php artisan config:cache > /dev/null 2>&1
echo -e "      ✓ Cache cleared and rebuilt"

# STEP 5: Verify configuration
echo -e "${GREEN}[5/5]${NC} Verifying final configuration..."
echo -e "      ${BLUE}Configuration Summary:${NC}"

php artisan tinker --execute="
    echo '      APP_URL: ' . config('app.url') . PHP_EOL;
    echo '      SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
    echo '      SESSION_HTTP_ONLY: ' . (config('session.http_only') ? 'true' : 'false') . PHP_EOL;
    echo '      SANCTUM_DOMAINS: ' . config('sanctum.stateful')[0] . PHP_EOL;
"

# Test database connection to tenant_oauth_providers
echo -e "      ${BLUE}Testing database table:${NC}"
php artisan tinker --execute="
    try {
        \$count = DB::table('tenant_oauth_providers')->count();
        echo '      tenant_oauth_providers: ' . \$count . ' records' . PHP_EOL;
    } catch (\Exception \$e) {
        echo '      ⚠ Table error: ' . \$e->getMessage() . PHP_EOL;
    }
"

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}✅ FIX COMPLETO APPLICATO CON SUCCESSO!${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo ""
echo -e "${YELLOW}TEST IMMEDIATO:${NC}"
echo "1. Apri browser in modalità INCOGNITO"
echo "2. Vai su: https://ainstein.it/login"
echo "3. Prova a fare login"
echo "4. Dovrebbe funzionare! 🎉"
echo ""
echo -e "${YELLOW}SE HAI ANCORA PROBLEMI:${NC}"
echo "• Verifica logs: tail -f storage/logs/laravel.log"
echo "• Check SSL: curl -I https://ainstein.it"
echo "• Verifica utente: php artisan tinker"
echo ""
echo -e "${GREEN}Backup files salvati con timestamp in caso di rollback necessario${NC}"
echo ""
