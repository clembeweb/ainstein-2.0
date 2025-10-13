#!/bin/bash

# Social Login Automated Verification Script
# This script verifies the Social Login setup without requiring actual OAuth credentials

set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Function to print test result
print_result() {
    local test_name="$1"
    local result="$2"
    local message="$3"

    if [ "$result" = "PASS" ]; then
        echo -e "${GREEN}[PASS]${NC} $test_name"
        ((PASSED++))
    elif [ "$result" = "FAIL" ]; then
        echo -e "${RED}[FAIL]${NC} $test_name"
        if [ -n "$message" ]; then
            echo -e "       ${RED}Error: $message${NC}"
        fi
        ((FAILED++))
    elif [ "$result" = "WARN" ]; then
        echo -e "${YELLOW}[WARN]${NC} $test_name"
        if [ -n "$message" ]; then
            echo -e "       ${YELLOW}Warning: $message${NC}"
        fi
        ((WARNINGS++))
    fi
}

# Function to print section header
print_section() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

# Navigate to Laravel directory
cd "$(dirname "$0")/ainstein-laravel" || exit 1

echo -e "${BLUE}Starting Social Login Verification Tests...${NC}"
echo ""

# TEST 1: Check if .env file exists
print_section "1. Environment Configuration"

if [ -f .env ]; then
    print_result ".env file exists" "PASS"
else
    print_result ".env file exists" "FAIL" ".env file not found"
    exit 1
fi

# TEST 2: Check OAuth credentials in .env
echo ""
echo "Checking OAuth configuration in .env..."

GOOGLE_CLIENT_ID=$(grep -E "^GOOGLE_CLIENT_ID=" .env 2>/dev/null | cut -d '=' -f2)
GOOGLE_CLIENT_SECRET=$(grep -E "^GOOGLE_CLIENT_SECRET=" .env 2>/dev/null | cut -d '=' -f2)
GOOGLE_REDIRECT_URI=$(grep -E "^GOOGLE_REDIRECT_URI=" .env 2>/dev/null | cut -d '=' -f2)

FACEBOOK_CLIENT_ID=$(grep -E "^FACEBOOK_CLIENT_ID=" .env 2>/dev/null | cut -d '=' -f2)
FACEBOOK_CLIENT_SECRET=$(grep -E "^FACEBOOK_CLIENT_SECRET=" .env 2>/dev/null | cut -d '=' -f2)
FACEBOOK_REDIRECT_URI=$(grep -E "^FACEBOOK_REDIRECT_URI=" .env 2>/dev/null | cut -d '=' -f2)

if [ -n "$GOOGLE_CLIENT_ID" ] && [ "$GOOGLE_CLIENT_ID" != "your-google-client-id" ]; then
    print_result "Google Client ID configured" "PASS"
else
    print_result "Google Client ID configured" "WARN" "Google OAuth not configured (optional for testing)"
fi

if [ -n "$GOOGLE_CLIENT_SECRET" ] && [ "$GOOGLE_CLIENT_SECRET" != "your-google-client-secret" ]; then
    print_result "Google Client Secret configured" "PASS"
else
    print_result "Google Client Secret configured" "WARN" "Google OAuth not configured (optional for testing)"
fi

if [ -n "$FACEBOOK_CLIENT_ID" ] && [ "$FACEBOOK_CLIENT_ID" != "your-facebook-client-id" ]; then
    print_result "Facebook Client ID configured" "PASS"
else
    print_result "Facebook Client ID configured" "WARN" "Facebook OAuth not configured (optional for testing)"
fi

if [ -n "$FACEBOOK_CLIENT_SECRET" ] && [ "$FACEBOOK_CLIENT_SECRET" != "your-facebook-client-secret" ]; then
    print_result "Facebook Client Secret configured" "PASS"
else
    print_result "Facebook Client Secret configured" "WARN" "Facebook OAuth not configured (optional for testing)"
fi

# TEST 3: Check if Laravel Socialite is installed
print_section "2. Package Dependencies"

if grep -q "laravel/socialite" composer.json; then
    print_result "Laravel Socialite in composer.json" "PASS"

    # Check if it's actually installed
    if [ -d "vendor/laravel/socialite" ]; then
        print_result "Laravel Socialite installed" "PASS"
    else
        print_result "Laravel Socialite installed" "FAIL" "Package not installed, run: composer install"
    fi
else
    print_result "Laravel Socialite in composer.json" "FAIL" "Package not configured"
fi

# TEST 4: Check Controller exists
print_section "3. Controller Verification"

if [ -f "app/Http/Controllers/Auth/SocialAuthController.php" ]; then
    print_result "SocialAuthController exists" "PASS"

    # Check for required methods
    if grep -q "redirectToProvider" app/Http/Controllers/Auth/SocialAuthController.php; then
        print_result "redirectToProvider method exists" "PASS"
    else
        print_result "redirectToProvider method exists" "FAIL"
    fi

    if grep -q "handleProviderCallback" app/Http/Controllers/Auth/SocialAuthController.php; then
        print_result "handleProviderCallback method exists" "PASS"
    else
        print_result "handleProviderCallback method exists" "FAIL"
    fi

    if grep -q "createUserFromSocial" app/Http/Controllers/Auth/SocialAuthController.php; then
        print_result "createUserFromSocial method exists" "PASS"
    else
        print_result "createUserFromSocial method exists" "FAIL"
    fi
else
    print_result "SocialAuthController exists" "FAIL" "Controller file not found"
fi

# TEST 5: Check Routes
print_section "4. Route Verification"

if [ -f "routes/web.php" ]; then
    if grep -q "social" routes/web.php; then
        print_result "Social routes defined in web.php" "PASS"
    else
        print_result "Social routes defined in web.php" "FAIL" "No social routes found"
    fi
else
    print_result "routes/web.php exists" "FAIL"
fi

if [ -f "routes/api.php" ]; then
    if grep -q "social" routes/api.php; then
        print_result "Social routes defined in api.php" "PASS"
    else
        print_result "Social routes defined in api.php" "WARN" "No API social routes found"
    fi
fi

# TEST 6: Check Database Schema
print_section "5. Database Schema"

if [ -f "database/migrations/2025_09_26_105407_add_social_auth_columns_to_users_table.php" ]; then
    print_result "Social auth migration exists" "PASS"

    # Check migration content
    if grep -q "social_provider" database/migrations/2025_09_26_105407_add_social_auth_columns_to_users_table.php; then
        print_result "social_provider column in migration" "PASS"
    fi

    if grep -q "social_id" database/migrations/2025_09_26_105407_add_social_auth_columns_to_users_table.php; then
        print_result "social_id column in migration" "PASS"
    fi

    if grep -q "social_avatar" database/migrations/2025_09_26_105407_add_social_auth_columns_to_users_table.php; then
        print_result "social_avatar column in migration" "PASS"
    fi
else
    print_result "Social auth migration exists" "FAIL" "Migration file not found"
fi

# TEST 7: Check User Model
print_section "6. User Model Configuration"

if [ -f "app/Models/User.php" ]; then
    print_result "User model exists" "PASS"

    if grep -q "social_provider" app/Models/User.php; then
        print_result "social_provider in User fillable" "PASS"
    else
        print_result "social_provider in User fillable" "FAIL"
    fi

    if grep -q "social_id" app/Models/User.php; then
        print_result "social_id in User fillable" "PASS"
    else
        print_result "social_id in User fillable" "FAIL"
    fi

    if grep -q "hasSocialAuth" app/Models/User.php; then
        print_result "hasSocialAuth helper method exists" "PASS"
    else
        print_result "hasSocialAuth helper method exists" "WARN" "Helper method not found"
    fi
else
    print_result "User model exists" "FAIL"
fi

# TEST 8: Check Config
print_section "7. Configuration Files"

if [ -f "config/services.php" ]; then
    print_result "config/services.php exists" "PASS"

    if grep -q "'google'" config/services.php; then
        print_result "Google service configuration" "PASS"
    else
        print_result "Google service configuration" "WARN" "Google not configured in services.php"
    fi

    if grep -q "'facebook'" config/services.php; then
        print_result "Facebook service configuration" "PASS"
    else
        print_result "Facebook service configuration" "WARN" "Facebook not configured in services.php"
    fi
else
    print_result "config/services.php exists" "FAIL"
fi

# TEST 9: Test Database Connection
print_section "8. Database Connectivity"

if php artisan migrate:status > /dev/null 2>&1; then
    print_result "Database connection working" "PASS"

    # Check if social auth migration is run
    if php artisan migrate:status | grep -q "add_social_auth_columns_to_users"; then
        print_result "Social auth migration applied" "PASS"
    else
        print_result "Social auth migration applied" "WARN" "Migration not applied, run: php artisan migrate"
    fi
else
    print_result "Database connection working" "FAIL" "Cannot connect to database"
fi

# TEST 10: Check if routes are registered
print_section "9. Route Registration"

echo "Checking registered routes..."

if php artisan route:list --json > /dev/null 2>&1; then
    ROUTE_OUTPUT=$(php artisan route:list 2>/dev/null | grep -i "social" || echo "")

    if echo "$ROUTE_OUTPUT" | grep -q "social.redirect"; then
        print_result "Web social redirect route registered" "PASS"
    else
        print_result "Web social redirect route registered" "FAIL" "Route not found"
    fi

    if echo "$ROUTE_OUTPUT" | grep -q "social.callback"; then
        print_result "Web social callback route registered" "PASS"
    else
        print_result "Web social callback route registered" "FAIL" "Route not found"
    fi

    if echo "$ROUTE_OUTPUT" | grep -q "api.social"; then
        print_result "API social routes registered" "PASS"
    else
        print_result "API social routes registered" "WARN" "API routes not found"
    fi
else
    print_result "Route listing" "FAIL" "Cannot list routes"
fi

# TEST 11: Check Tenant Model
print_section "10. Tenant Model (Required for Social Auth)"

if [ -f "app/Models/Tenant.php" ]; then
    print_result "Tenant model exists" "PASS"
else
    print_result "Tenant model exists" "FAIL" "Required for auto-tenant creation"
fi

# TEST 12: Check EmailService
print_section "11. Email Service"

if [ -f "app/Services/EmailService.php" ]; then
    print_result "EmailService exists" "PASS"

    if grep -q "sendWelcomeEmail" app/Services/EmailService.php; then
        print_result "sendWelcomeEmail method exists" "PASS"
    else
        print_result "sendWelcomeEmail method exists" "WARN" "Welcome email not configured"
    fi
else
    print_result "EmailService exists" "WARN" "Email notifications may not work"
fi

# Summary
print_section "Test Summary"

echo ""
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${YELLOW}Warnings: $WARNINGS${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All critical tests passed! Social Login setup is complete.${NC}"
    if [ $WARNINGS -gt 0 ]; then
        echo -e "${YELLOW}Note: There are $WARNINGS warnings. These are optional configurations.${NC}"
    fi
    exit 0
else
    echo -e "${RED}Some tests failed. Please review the errors above.${NC}"
    exit 1
fi
