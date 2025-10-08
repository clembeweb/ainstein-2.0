#!/bin/bash

echo "🧪 TEST ADMIN UI - SIMULAZIONE UTENTE"
echo "========================================"
echo ""

# Step 1: Get login page and extract CSRF token
echo "📍 STEP 1: Getting login page..."
curl -s -c cookies.txt http://127.0.0.1:8080/admin/login > /tmp/login.html
CSRF_TOKEN=$(grep -oP 'name="_token" value="\K[^"]+' /tmp/login.html | head -1)

if [ -z "$CSRF_TOKEN" ]; then
    echo "❌ Failed to get CSRF token"
    exit 1
fi

echo "✅ CSRF Token obtained: ${CSRF_TOKEN:0:20}..."
echo ""

# Step 2: Login with credentials
echo "📍 STEP 2: Logging in as superadmin..."
LOGIN_RESPONSE=$(curl -s -b cookies.txt -c cookies.txt -L -X POST http://127.0.0.1:8080/admin/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "_token=$CSRF_TOKEN&email=superadmin@ainstein.com&password=admin123")

if echo "$LOGIN_RESPONSE" | grep -q "Dashboard"; then
    echo "✅ Login successful"
else
    echo "❌ Login failed"
    echo "$LOGIN_RESPONSE" | head -20
    exit 1
fi
echo ""

# Step 3: Test /admin/users
echo "📍 STEP 3: Testing /admin/users..."
USERS_RESPONSE=$(curl -s -b cookies.txt -w "\nHTTP_CODE:%{http_code}" http://127.0.0.1:8080/admin/users)
HTTP_CODE=$(echo "$USERS_RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ /admin/users accessible (200)"
    if echo "$USERS_RESPONSE" | grep -q "Users"; then
        echo "   ✅ Page contains 'Users'"
    fi
else
    echo "❌ /admin/users returned $HTTP_CODE"
fi
echo ""

# Step 4: Test /admin/tenants
echo "📍 STEP 4: Testing /admin/tenants..."
TENANTS_RESPONSE=$(curl -s -b cookies.txt -w "\nHTTP_CODE:%{http_code}" http://127.0.0.1:8080/admin/tenants)
HTTP_CODE=$(echo "$TENANTS_RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ /admin/tenants accessible (200)"
    if echo "$TENANTS_RESPONSE" | grep -q "Tenants"; then
        echo "   ✅ Page contains 'Tenants'"
    fi
else
    echo "❌ /admin/tenants returned $HTTP_CODE"
fi
echo ""

# Step 5: Test /admin/subscriptions
echo "📍 STEP 5: Testing /admin/subscriptions..."
SUBS_RESPONSE=$(curl -s -b cookies.txt -w "\nHTTP_CODE:%{http_code}" http://127.0.0.1:8080/admin/subscriptions)
HTTP_CODE=$(echo "$SUBS_RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ /admin/subscriptions accessible (200)"
    if echo "$SUBS_RESPONSE" | grep -q "Subscriptions"; then
        echo "   ✅ Page contains 'Subscriptions'"
    fi
else
    echo "❌ /admin/subscriptions returned $HTTP_CODE"
fi
echo ""

# Step 6: Test /admin/settings
echo "📍 STEP 6: Testing /admin/settings..."
SETTINGS_RESPONSE=$(curl -s -b cookies.txt -w "\nHTTP_CODE:%{http_code}" http://127.0.0.1:8080/admin/settings)
HTTP_CODE=$(echo "$SETTINGS_RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ /admin/settings accessible (200)"
    if echo "$SETTINGS_RESPONSE" | grep -q "Settings"; then
        echo "   ✅ Page contains 'Settings'"
    fi
else
    echo "❌ /admin/settings returned $HTTP_CODE"
fi
echo ""

# Step 7: Check navigation sidebar
echo "📍 STEP 7: Checking sidebar navigation..."
DASHBOARD_HTML=$(curl -s -b cookies.txt http://127.0.0.1:8080/admin)

NAV_ITEMS=("Dashboard" "Users" "Tenants" "Subscriptions" "Settings" "AI Prompts")

for item in "${NAV_ITEMS[@]}"; do
    if echo "$DASHBOARD_HTML" | grep -qi "$item"; then
        echo "   ✅ '$item' found in navigation"
    else
        echo "   ❌ '$item' NOT found in navigation"
    fi
done

echo ""
echo "========================================"
echo "🎯 ADMIN UI TEST COMPLETED"
echo "========================================"
