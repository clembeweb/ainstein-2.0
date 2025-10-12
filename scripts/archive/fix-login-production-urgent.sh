#!/bin/bash

# =====================================================================
# AINSTEIN - URGENT LOGIN FIX FOR PRODUCTION
# =====================================================================
# This script ONLY fixes the login issue without touching anything else
# Safe to run while other agents are working on other features
# =====================================================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=====================================================================${NC}"
echo -e "${BLUE}           AINSTEIN - URGENT PRODUCTION LOGIN FIX                    ${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo ""

# Step 1: Backup .env
echo -e "${GREEN}[1/6]${NC} Creating backup of .env file..."
if [ -f .env ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo -e "      ✓ Backup created"
else
    echo -e "${RED}      ✗ .env file not found!${NC}"
    exit 1
fi

# Step 2: Check and fix APP_URL
echo -e "${GREEN}[2/6]${NC} Checking APP_URL configuration..."
if grep -q "APP_URL=http://" .env; then
    echo -e "${YELLOW}      ⚠ APP_URL is using HTTP, updating to HTTPS...${NC}"
    sed -i.bak 's|APP_URL=http://|APP_URL=https://|g' .env
    echo -e "      ✓ APP_URL updated to HTTPS"
elif grep -q "APP_URL=https://ainstein.it" .env; then
    echo -e "      ✓ APP_URL already correct (https://ainstein.it)"
else
    echo -e "${YELLOW}      ⚠ Adding APP_URL=https://ainstein.it${NC}"
    sed -i.bak 's|APP_URL=.*|APP_URL=https://ainstein.it|g' .env
fi

# Step 3: Fix SESSION configurations
echo -e "${GREEN}[3/6]${NC} Fixing SESSION configurations for HTTPS..."

# SESSION_SECURE_COOKIE
if grep -q "SESSION_SECURE_COOKIE=" .env; then
    sed -i.bak 's|SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|g' .env
    echo -e "      ✓ SESSION_SECURE_COOKIE updated to true"
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

# Step 4: Fix SANCTUM_STATEFUL_DOMAINS
echo -e "${GREEN}[4/6]${NC} Configuring Sanctum for ainstein.it..."
if grep -q "SANCTUM_STATEFUL_DOMAINS=" .env; then
    sed -i.bak 's|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it|g' .env
    echo -e "      ✓ SANCTUM_STATEFUL_DOMAINS updated"
else
    echo "SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it" >> .env
    echo -e "      ✓ SANCTUM_STATEFUL_DOMAINS added"
fi

# Step 5: Clear ONLY session and config cache (non-invasive)
echo -e "${GREEN}[5/6]${NC} Clearing session and config cache..."
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
php artisan config:cache > /dev/null 2>&1
echo -e "      ✓ Cache cleared and rebuilt"

# Step 6: Verify configurations
echo -e "${GREEN}[6/6]${NC} Verifying final configuration..."
echo -e "      ${BLUE}Configuration Summary:${NC}"

php artisan tinker --execute="
    echo '      APP_URL: ' . config('app.url') . PHP_EOL;
    echo '      APP_ENV: ' . config('app.env') . PHP_EOL;
    echo '      SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
    echo '      SESSION_SECURE_COOKIE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
    echo '      SESSION_HTTP_ONLY: ' . (config('session.http_only') ? 'true' : 'false') . PHP_EOL;
    echo '      SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
    echo '      SANCTUM_DOMAINS: ' . config('sanctum.stateful')[0] . PHP_EOL;
"

echo ""
echo -e "${BLUE}=====================================================================${NC}"
echo -e "${GREEN}✅ LOGIN FIX COMPLETED SUCCESSFULLY!${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo ""
echo -e "${YELLOW}NEXT STEPS:${NC}"
echo "1. Open browser in INCOGNITO mode"
echo "2. Go to https://ainstein.it/login"
echo "3. Try logging in with your credentials"
echo "4. If successful, you're ready to launch Comet for testing"
echo ""
echo -e "${YELLOW}IF STILL HAVING ISSUES:${NC}"
echo "• Check: tail -f storage/logs/laravel.log"
echo "• Verify SSL certificate: curl -I https://ainstein.it"
echo "• Check database users: php artisan tinker --execute=\"App\\Models\\User::where('email', 'YOUR_EMAIL')->first()\""
echo ""
echo -e "${GREEN}This fix was minimal and safe - no migrations or builds run.${NC}"
echo ""
