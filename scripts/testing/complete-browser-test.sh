#!/bin/bash

echo "═══════════════════════════════════════════════════════════════════"
echo "  COMPLETE PLATFORM TEST - Every Feature"
echo "═══════════════════════════════════════════════════════════════════"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

BASE_URL="http://127.0.0.1:8000"

echo "[PHASE 1] Guest Pages & Authentication"
echo "───────────────────────────────────────────────────────────────────"
echo ""

echo "Test 1.1: Homepage"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/)
if [ "$STATUS" == "200" ] || [ "$STATUS" == "302" ]; then
    echo "   ✅ Homepage loads (HTTP $STATUS)"
else
    echo "   ❌ Homepage error (HTTP $STATUS)"
fi

echo "Test 1.2: Login Page"
RESPONSE=$(curl -s $BASE_URL/login)
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/login)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Login page loads (HTTP $STATUS)"
    if echo "$RESPONSE" | grep -q "email"; then
        echo "   ✅ Email field present"
    fi
    if echo "$RESPONSE" | grep -q "password"; then
        echo "   ✅ Password field present"
    fi
    if echo "$RESPONSE" | grep -q "_token"; then
        echo "   ✅ CSRF token present"
    fi
else
    echo "   ❌ Login page error (HTTP $STATUS)"
fi

echo ""
echo "Test 1.3: Register Page"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/register)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Register page loads (HTTP $STATUS)"
else
    echo "   ❌ Register page error (HTTP $STATUS)"
fi

echo ""
echo "Test 1.4: Forgot Password Page"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/forgot-password)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Forgot password page loads (HTTP $STATUS)"
else
    echo "   ❌ Forgot password error (HTTP $STATUS)"
fi

echo ""
echo "[PHASE 2] Tenant Login & Dashboard"
echo "───────────────────────────────────────────────────────────────────"
echo ""

# Extract CSRF token and login
COOKIE_JAR="/tmp/ainstein_cookies.txt"
rm -f $COOKIE_JAR

echo "Test 2.1: Extract CSRF Token"
TOKEN=$(curl -s -c $COOKIE_JAR $BASE_URL/login | grep -oP 'name="_token" value="\K[^"]+' | head -1)
if [ ! -z "$TOKEN" ]; then
    echo "   ✅ CSRF token extracted"
else
    echo "   ❌ Failed to extract CSRF token"
    exit 1
fi

echo "Test 2.2: Tenant Login (admin@demo.com)"
LOGIN_STATUS=$(curl -s -b $COOKIE_JAR -c $COOKIE_JAR -X POST $BASE_URL/login \
    -d "_token=$TOKEN" \
    -d "email=admin@demo.com" \
    -d "password=password" \
    -o /dev/null -w "%{http_code}" -L)

if [ "$LOGIN_STATUS" == "200" ]; then
    echo "   ✅ Login successful (HTTP $LOGIN_STATUS)"
else
    echo "   ⚠️  Login status: HTTP $LOGIN_STATUS (might be redirect 302)"
fi

echo ""
echo "Test 2.3: Tenant Dashboard Access"
DASHBOARD=$(curl -s -b $COOKIE_JAR $BASE_URL/dashboard)
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/dashboard)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Dashboard loads (HTTP $STATUS)"
    if echo "$DASHBOARD" | grep -q "Dashboard"; then
        echo "   ✅ Dashboard content present"
    fi
    if echo "$DASHBOARD" | grep -q "tokens"; then
        echo "   ✅ Token tracking visible"
    fi
else
    echo "   ❌ Dashboard error (HTTP $STATUS)"
fi

echo ""
echo "[PHASE 3] Content Generator (3 Tabs)"
echo "───────────────────────────────────────────────────────────────────"
echo ""

echo "Test 3.1: Content Generator Main Page"
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/dashboard/content)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Content Generator loads (HTTP $STATUS)"
else
    echo "   ❌ Content Generator error (HTTP $STATUS)"
fi

echo ""
echo "[PHASE 4] Campaign Generator"
echo "───────────────────────────────────────────────────────────────────"
echo ""

echo "Test 4.1: Campaigns List"
CAMPAIGNS=$(curl -s -b $COOKIE_JAR $BASE_URL/dashboard/campaigns)
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/dashboard/campaigns)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Campaigns list loads (HTTP $STATUS)"
    if echo "$CAMPAIGNS" | grep -q "Campaign"; then
        echo "   ✅ Campaign content visible"
    fi
else
    echo "   ❌ Campaigns list error (HTTP $STATUS)"
fi

echo ""
echo "Test 4.2: Create Campaign Page"
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/dashboard/campaigns/create)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Create campaign page loads (HTTP $STATUS)"
else
    echo "   ❌ Create campaign error (HTTP $STATUS)"
fi

echo ""
echo "[PHASE 5] Super Admin (Logout & Login as Admin)"
echo "───────────────────────────────────────────────────────────────────"
echo ""

echo "Test 5.1: Logout Tenant User"
curl -s -b $COOKIE_JAR -X POST $BASE_URL/logout -d "_token=$TOKEN" -o /dev/null
rm -f $COOKIE_JAR
echo "   ✅ Logged out"

echo ""
echo "Test 5.2: Login as Super Admin"
TOKEN=$(curl -s -c $COOKIE_JAR $BASE_URL/login | grep -oP 'name="_token" value="\K[^"]+' | head -1)
ADMIN_LOGIN=$(curl -s -b $COOKIE_JAR -c $COOKIE_JAR -X POST $BASE_URL/login \
    -d "_token=$TOKEN" \
    -d "email=admin@ainstein.com" \
    -d "password=password" \
    -L -o /dev/null -w "%{http_code}")

if [ "$ADMIN_LOGIN" == "200" ]; then
    echo "   ✅ Admin login successful"
else
    echo "   ⚠️  Admin login: HTTP $ADMIN_LOGIN"
fi

echo ""
echo "Test 5.3: Admin Dashboard"
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/admin/dashboard)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Admin dashboard loads (HTTP $STATUS)"
else
    echo "   ❌ Admin dashboard error (HTTP $STATUS)"
fi

echo ""
echo "Test 5.4: Admin Settings Page"
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/admin/settings)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Admin settings loads (HTTP $STATUS)"
else
    echo "   ❌ Admin settings error (HTTP $STATUS)"
fi

echo ""
echo "Test 5.5: Users Management"
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/admin/users)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Users management loads (HTTP $STATUS)"
else
    echo "   ❌ Users management error (HTTP $STATUS)"
fi

echo ""
echo "Test 5.6: Tenants Management"
STATUS=$(curl -s -b $COOKIE_JAR -o /dev/null -w "%{http_code}" $BASE_URL/admin/tenants)
if [ "$STATUS" == "200" ]; then
    echo "   ✅ Tenants management loads (HTTP $STATUS)"
else
    echo "   ❌ Tenants management error (HTTP $STATUS)"
fi

echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo "  TEST COMPLETE"
echo "═══════════════════════════════════════════════════════════════════"
echo ""

# Cleanup
rm -f $COOKIE_JAR
