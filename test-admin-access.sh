#!/bin/bash

echo "🧪 TESTING SUPER ADMIN ACCESS"
echo "========================================================================"
echo ""

BASE_URL="http://127.0.0.1:8080"
COOKIE_FILE="admin_cookies.txt"
rm -f $COOKIE_FILE

# Test 1: Check if admin login page exists
echo "📍 STEP 1: Checking admin login page..."
echo "------------------------------------------------------------------------"
ADMIN_LOGIN=$(curl -s -w "\nHTTP_CODE:%{http_code}" $BASE_URL/admin/login)
HTTP_CODE=$(echo "$ADMIN_LOGIN" | grep "HTTP_CODE:" | cut -d: -f2)

echo "URL: $BASE_URL/admin/login"
echo "Status: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Admin login page accessible"

    # Check if it's a login form or already redirected to dashboard
    if [[ "$ADMIN_LOGIN" == *"login"* ]] || [[ "$ADMIN_LOGIN" == *"email"* ]]; then
        echo "✅ Login form present"
    else
        echo "ℹ️  Page loaded but no login form detected"
    fi
elif [ "$HTTP_CODE" = "302" ]; then
    echo "ℹ️  Redirected (302) - might be Filament panel"
else
    echo "⚠️  Unexpected status: $HTTP_CODE"
fi
echo ""

# Test 2: Try accessing /admin directly
echo "📍 STEP 2: Checking /admin endpoint..."
echo "------------------------------------------------------------------------"
ADMIN_PAGE=$(curl -s -L -w "\nHTTP_CODE:%{http_code}" $BASE_URL/admin)
HTTP_CODE=$(echo "$ADMIN_PAGE" | grep "HTTP_CODE:" | cut -d: -f2)

echo "URL: $BASE_URL/admin"
echo "Status: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Admin endpoint accessible"

    # Check what kind of panel it is
    if [[ "$ADMIN_PAGE" == *"Filament"* ]]; then
        echo "✅ Filament admin panel detected"
    elif [[ "$ADMIN_PAGE" == *"admin"* ]] || [[ "$ADMIN_PAGE" == *"Admin"* ]]; then
        echo "✅ Custom admin panel detected"
    fi
else
    echo "Status: $HTTP_CODE"
fi
echo ""

# Test 3: Check available admin routes
echo "📍 STEP 3: Available admin routes..."
echo "------------------------------------------------------------------------"
cd /c/laragon/www/ainstein-3/ainstein-laravel
php artisan route:list | grep -i "admin" | grep -E "GET|POST" | head -10
echo ""

# Test 4: Try login with superadmin credentials
echo "📍 STEP 4: Testing admin login..."
echo "------------------------------------------------------------------------"

# Get CSRF token from login page
curl -s -c $COOKIE_FILE $BASE_URL/admin/login > /tmp/admin_login.html 2>/dev/null
CSRF=$(grep -oP 'name="_token"\s+value="\K[^"]+' /tmp/admin_login.html 2>/dev/null | head -1)

if [ -z "$CSRF" ]; then
    # Try alternative CSRF extraction
    CSRF=$(grep -oP 'csrf-token.*content="\K[^"]+' /tmp/admin_login.html 2>/dev/null | head -1)
fi

if [ -n "$CSRF" ]; then
    echo "✅ CSRF token found"
    echo "Email: superadmin@ainstein.com"
    echo "Password: admin123"

    LOGIN_RESULT=$(curl -s -L -b $COOKIE_FILE -c $COOKIE_FILE \
        -w "\nFINAL_URL:%{url_effective}\nHTTP_CODE:%{http_code}" \
        -X POST $BASE_URL/admin/login \
        -d "email=superadmin@ainstein.com" \
        -d "password=admin123" \
        -d "_token=$CSRF" 2>/dev/null)

    HTTP_CODE=$(echo "$LOGIN_RESULT" | grep "HTTP_CODE:" | cut -d: -f2)
    FINAL_URL=$(echo "$LOGIN_RESULT" | grep "FINAL_URL:" | cut -d: -f2-)

    echo "Status: $HTTP_CODE"
    echo "Final URL: $FINAL_URL"

    if [[ "$FINAL_URL" == *"/admin"* ]] && [[ "$FINAL_URL" != *"/login"* ]]; then
        echo "✅ Login successful! Redirected to admin dashboard"
    else
        echo "⚠️  Login may have failed or unexpected redirect"
    fi
else
    echo "ℹ️  Could not extract CSRF token (might be Filament with different auth)"
fi

echo ""

# Cleanup
rm -f $COOKIE_FILE /tmp/admin_login.html

echo "========================================================================"
echo "🎉 ADMIN ACCESS TEST COMPLETED"
echo "========================================================================"
echo ""
echo "📝 SUMMARY:"
echo ""
echo "🔐 Super Admin Accounts:"
echo "   1. superadmin@ainstein.com / admin123"
echo "   2. admin@ainstein.com / Admin123!"
echo ""
echo "🌐 Admin Panel Access:"
echo "   URL: http://127.0.0.1:8080/admin"
echo "   OR: http://127.0.0.1:8080/admin/login"
echo ""
echo "========================================================================"
