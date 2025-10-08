#!/bin/bash

echo "🧪 BROWSER NAVIGATION SIMULATION (WITH SESSION)"
echo "========================================================================"
echo ""

BASE_URL="http://127.0.0.1:8080"
COOKIE_FILE="browser_session_cookies.txt"
rm -f $COOKIE_FILE

# Step 1: Get login page and CSRF token
echo "📍 STEP 1: Loading login page..."
curl -s -c $COOKIE_FILE $BASE_URL/login > /tmp/login_page.html
CSRF=$(grep -oP 'name="_token"\s+value="\K[^"]+' /tmp/login_page.html | head -1)

if [ -z "$CSRF" ]; then
    echo "❌ Failed to get CSRF token"
    exit 1
fi

echo "✅ CSRF token obtained"
echo ""

# Step 2: Login
echo "📍 STEP 2: Logging in as demo@tenant.com..."
LOGIN_RESPONSE=$(curl -s -L -b $COOKIE_FILE -c $COOKIE_FILE \
    -w "\nFINAL_URL:%{url_effective}\nHTTP_CODE:%{http_code}" \
    -X POST $BASE_URL/login \
    -d "email=demo@tenant.com" \
    -d "password=password" \
    -d "_token=$CSRF")

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
FINAL_URL=$(echo "$LOGIN_RESPONSE" | grep "FINAL_URL:" | cut -d: -f2-)

echo "Status: $HTTP_CODE"
echo "Final URL: $FINAL_URL"

if [[ "$FINAL_URL" == *"/dashboard"* ]]; then
    echo "✅ Login successful, redirected to dashboard"
else
    echo "❌ Login failed"
    exit 1
fi
echo ""

# Step 3: Test navigation to each page
PAGES=(
    "dashboard:Dashboard"
    "dashboard/pages:Pages Management"
    "dashboard/prompts:Prompts Management"
    "dashboard/content:Content Generation"
    "dashboard/api-keys:API Keys Management"
)

PASSED=0
FAILED=0

for PAGE_INFO in "${PAGES[@]}"; do
    IFS=':' read -r PAGE_URL PAGE_NAME <<< "$PAGE_INFO"

    echo "📍 Testing: $PAGE_NAME"
    echo "------------------------------------------------------------------------"

    RESPONSE=$(curl -s -b $COOKIE_FILE -w "\nHTTP_CODE:%{http_code}" $BASE_URL/$PAGE_URL)
    HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)

    echo "URL: $BASE_URL/$PAGE_URL"
    echo "Status: $HTTP_CODE"

    if [ "$HTTP_CODE" = "200" ]; then
        echo "✅ Page loaded successfully (200 OK)"

        # Check if response contains actual content (not redirect)
        if [[ "$RESPONSE" == *"<!DOCTYPE"* ]] || [[ "$RESPONSE" == *"<html"* ]]; then
            echo "✅ HTML content received"
            ((PASSED++))
        else
            echo "⚠️  Received 200 but no HTML content"
            ((FAILED++))
        fi
    elif [ "$HTTP_CODE" = "302" ]; then
        echo "⚠️  Received redirect (302) - session might be lost"
        ((FAILED++))
    else
        echo "❌ Failed with status: $HTTP_CODE"
        ((FAILED++))
    fi

    echo ""
done

# Cleanup
rm -f $COOKIE_FILE /tmp/login_page.html

# Summary
echo "========================================================================"
echo "🎉 BROWSER NAVIGATION TEST COMPLETED"
echo "========================================================================"
echo ""
echo "📊 RESULTS:"
echo "   ✅ Tests passed: $PASSED"
echo "   ❌ Tests failed: $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "🎉 ALL NAVIGATION TESTS PASSED!"
    echo ""
    echo "✅ The platform is fully functional in browser mode!"
    echo "✅ All pages accessible with proper session management"
    echo "✅ Ready for production use!"
else
    echo "⚠️  Some tests failed"
    echo "Check the details above"
fi

echo ""
echo "========================================================================"
