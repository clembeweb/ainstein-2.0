#!/bin/bash

echo "🎨 TEST UI/UX - FRONTEND SIMULATION"
echo "===================================="
echo ""

# Test Admin Pages UI
echo "📍 ADMIN PANEL UI TESTS"
echo "----------------------------------------"

echo "1. Testing /admin/login..."
RESPONSE=$(curl -s http://127.0.0.1:8080/admin/login)
if echo "$RESPONSE" | grep -q "Ainstein Admin"; then
    echo "   ✅ Admin login page loads with branding"
else
    echo "   ❌ Admin login page broken"
fi

if echo "$RESPONSE" | grep -q "Email"; then
    echo "   ✅ Login form present"
else
    echo "   ❌ Login form missing"
fi
echo ""

# Test Tenant Pages UI
echo "📍 TENANT PANEL UI TESTS"
echo "----------------------------------------"

echo "2. Testing /login (tenant)..."
RESPONSE=$(curl -s http://127.0.0.1:8080/login)
if echo "$RESPONSE" | grep -q "Email"; then
    echo "   ✅ Tenant login page loads"
else
    echo "   ❌ Tenant login page broken"
fi
echo ""

echo "3. Testing / (landing page)..."
RESPONSE=$(curl -s http://127.0.0.1:8080/)
if echo "$RESPONSE" | grep -q "html"; then
    echo "   ✅ Landing page loads"
else
    echo "   ❌ Landing page broken"
fi
echo ""

# Test Protected Routes (should redirect to login)
echo "📍 PROTECTED ROUTES TESTS"
echo "----------------------------------------"

echo "4. Testing /dashboard (should redirect)..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/dashboard)
if [ "$HTTP_CODE" = "302" ]; then
    echo "   ✅ Dashboard protected (redirects to login)"
else
    echo "   ⚠️  Dashboard returned HTTP $HTTP_CODE"
fi
echo ""

echo "5. Testing /admin (should redirect)..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/admin)
if [ "$HTTP_CODE" = "302" ]; then
    echo "   ✅ Admin dashboard protected (redirects to login)"
else
    echo "   ⚠️  Admin dashboard returned HTTP $HTTP_CODE"
fi
echo ""

# Test CSS/Tailwind
echo "📍 FRONTEND ASSETS TESTS"
echo "----------------------------------------"

echo "6. Testing Tailwind CSS in pages..."
RESPONSE=$(curl -s http://127.0.0.1:8080/admin/login)
if echo "$RESPONSE" | grep -q "tailwindcss.com"; then
    echo "   ✅ Tailwind CSS loaded"
else
    echo "   ❌ Tailwind CSS missing"
fi
echo ""

# Test Form Structure
echo "7. Testing Form Elements..."
RESPONSE=$(curl -s http://127.0.0.1:8080/admin/login)
if echo "$RESPONSE" | grep -q 'type="email"'; then
    echo "   ✅ Email input present"
else
    echo "   ❌ Email input missing"
fi

if echo "$RESPONSE" | grep -q 'type="password"'; then
    echo "   ✅ Password input present"
else
    echo "   ❌ Password input missing"
fi

if echo "$RESPONSE" | grep -q 'type="submit"'; then
    echo "   ✅ Submit button present"
else
    echo "   ❌ Submit button missing"
fi
echo ""

echo "===================================="
echo "✅ UI/UX FRONTEND TEST COMPLETED"
echo "===================================="
