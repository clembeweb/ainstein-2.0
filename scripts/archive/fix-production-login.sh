#!/bin/bash

# =====================================================================
# AINSTEIN - Fix Production Login Issue Script
# =====================================================================
# This script fixes the login redirect loop issue in production
# Run this on your production server after updating .env file
# =====================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

echo "======================================================================"
echo "                    AINSTEIN LOGIN FIX SCRIPT                         "
echo "======================================================================"
echo ""

# Step 1: Check current environment
print_info "Checking current environment..."
if [ ! -f .env ]; then
    print_error ".env file not found!"
    exit 1
fi

# Step 2: Backup current .env
print_status "Creating backup of .env file..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Step 3: Check critical session configurations
print_info "Checking critical configurations..."

# Check if APP_URL is HTTPS
if grep -q "APP_URL=http://" .env; then
    print_warning "APP_URL is using HTTP. For production, it should be HTTPS."
    print_info "Please update APP_URL to use https:// in .env file"
fi

# Check SESSION_SECURE_COOKIE
if ! grep -q "SESSION_SECURE_COOKIE=true" .env; then
    print_warning "SESSION_SECURE_COOKIE is not set to true"
    print_info "Adding SESSION_SECURE_COOKIE=true to .env..."

    # Add or update SESSION_SECURE_COOKIE
    if grep -q "SESSION_SECURE_COOKIE=" .env; then
        sed -i 's/SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
    else
        echo "" >> .env
        echo "# Security settings for HTTPS" >> .env
        echo "SESSION_SECURE_COOKIE=true" >> .env
    fi
fi

# Check SESSION_HTTP_ONLY
if ! grep -q "SESSION_HTTP_ONLY=true" .env; then
    print_info "Adding SESSION_HTTP_ONLY=true to .env..."
    echo "SESSION_HTTP_ONLY=true" >> .env
fi

# Check SESSION_SAME_SITE
if ! grep -q "SESSION_SAME_SITE=" .env; then
    print_info "Adding SESSION_SAME_SITE=lax to .env..."
    echo "SESSION_SAME_SITE=lax" >> .env
fi

# Step 4: Clear all caches
print_status "Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# Step 5: Run migrations to ensure sessions table exists
print_status "Checking database migrations..."
php artisan migrate --force

# Step 6: Check if sessions table exists and has records
print_info "Verifying sessions table..."
php artisan tinker --execute="
    try {
        \$count = DB::table('sessions')->count();
        echo 'Sessions table exists with ' . \$count . ' records';
    } catch (\Exception \$e) {
        echo 'Sessions table error: ' . \$e->getMessage();
    }
"
echo ""

# Step 7: Clear expired sessions
print_status "Clearing expired sessions..."
php artisan tinker --execute="DB::table('sessions')->where('last_activity', '<', time() - 86400)->delete();"

# Step 8: Verify database connection
print_info "Testing database connection..."
php artisan tinker --execute="
    try {
        DB::connection()->getPdo();
        echo 'Database connection: OK';
    } catch (\Exception \$e) {
        echo 'Database connection failed: ' . \$e->getMessage();
    }
"
echo ""

# Step 9: Check user and tenant status
if [ "$1" == "--check-user" ] && [ -n "$2" ]; then
    USER_EMAIL="$2"
    print_info "Checking user status for: $USER_EMAIL"

    php artisan tinker --execute="
        \$user = \App\Models\User::where('email', '$USER_EMAIL')->first();
        if (\$user) {
            echo 'User found: ' . \$user->email . PHP_EOL;
            echo 'Active: ' . (\$user->is_active ? 'Yes' : 'No') . PHP_EOL;
            echo 'Tenant ID: ' . (\$user->tenant_id ?? 'NULL') . PHP_EOL;

            if (\$user->tenant_id) {
                \$tenant = \App\Models\Tenant::find(\$user->tenant_id);
                if (\$tenant) {
                    echo 'Tenant: ' . \$tenant->name . PHP_EOL;
                    echo 'Tenant Status: ' . \$tenant->status . PHP_EOL;
                } else {
                    echo 'Tenant not found!' . PHP_EOL;
                }
            }
        } else {
            echo 'User not found!' . PHP_EOL;
        }
    "
    echo ""
fi

# Step 10: Fix user without tenant (optional)
if [ "$1" == "--fix-user" ] && [ -n "$2" ]; then
    USER_EMAIL="$2"
    print_warning "Attempting to fix user: $USER_EMAIL"

    php artisan tinker --execute="
        \$user = \App\Models\User::where('email', '$USER_EMAIL')->first();
        if (\$user) {
            // Activate user
            \$user->is_active = 1;
            \$user->save();
            echo 'User activated' . PHP_EOL;

            // Check/create tenant
            if (!\$user->tenant_id) {
                \$tenant = \App\Models\Tenant::create([
                    'name' => \$user->name . ' Tenant',
                    'domain' => strtolower(str_replace(' ', '-', \$user->name)) . '.ainstein.local',
                    'subdomain' => strtolower(str_replace(' ', '-', \$user->name)),
                    'status' => 'active',
                    'plan_type' => 'starter',
                    'tokens_monthly_limit' => 10000,
                    'tokens_used_current' => 0,
                ]);
                \$user->tenant_id = \$tenant->id;
                \$user->save();
                echo 'Tenant created and assigned' . PHP_EOL;
            } else {
                // Activate existing tenant
                \$tenant = \App\Models\Tenant::find(\$user->tenant_id);
                if (\$tenant) {
                    \$tenant->status = 'active';
                    \$tenant->save();
                    echo 'Tenant activated' . PHP_EOL;
                }
            }
        }
    "
    echo ""
fi

# Step 11: Rebuild cache
print_status "Rebuilding optimized cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Step 12: Show final configuration
print_info "Final configuration check:"
echo "----------------------------------------"
php artisan tinker --execute="
    echo 'APP_ENV: ' . config('app.env') . PHP_EOL;
    echo 'APP_URL: ' . config('app.url') . PHP_EOL;
    echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
    echo 'SESSION_SECURE_COOKIE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
    echo 'SESSION_HTTP_ONLY: ' . (config('session.http_only') ? 'true' : 'false') . PHP_EOL;
    echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
"
echo "----------------------------------------"

# Step 13: Test health endpoint
print_info "Testing health endpoint..."
if command -v curl &> /dev/null; then
    APP_URL=$(grep "APP_URL=" .env | cut -d '=' -f2 | tr -d '"')
    curl -s "${APP_URL}/health" | php -r "
        \$json = json_decode(stream_get_contents(STDIN), true);
        if (\$json && \$json['status'] == 'healthy') {
            echo 'Health check: OK' . PHP_EOL;
        } else {
            echo 'Health check: FAILED' . PHP_EOL;
        }
    "
else
    print_warning "curl not found, skipping health check"
fi

echo ""
echo "======================================================================"
print_status "Login fix script completed!"
echo "======================================================================"
echo ""
echo "NEXT STEPS:"
echo "1. Try logging in with your browser (use incognito mode)"
echo "2. Check logs: tail -f storage/logs/laravel.log"
echo "3. If still having issues, run: $0 --check-user user@email.com"
echo "4. To fix a user: $0 --fix-user user@email.com"
echo ""
echo "IMPORTANT REMINDERS:"
echo "- Ensure your domain has valid SSL certificate"
echo "- Check that APP_URL matches your actual domain with https://"
echo "- Verify that your web server passes HTTPS headers correctly"
echo ""