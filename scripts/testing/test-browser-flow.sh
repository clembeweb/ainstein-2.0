#!/bin/bash
# Content Generator - Browser Flow Test
# Test completo del flusso utente dal browser

echo "=================================="
echo "🧪 CONTENT GENERATOR BROWSER TEST"
echo "=================================="
echo ""

BASE_URL="http://localhost:8000"
COOKIES_FILE="/tmp/ainstein_cookies.txt"
rm -f $COOKIES_FILE

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

test_endpoint() {
    local name=$1
    local url=$2
    local expected_status=$3
    local check_content=$4

    echo -n "Testing: $name... "

    response=$(curl -s -o /tmp/response.html -w "%{http_code}" -b $COOKIES_FILE -c $COOKIES_FILE "$url")

    if [ "$response" -eq "$expected_status" ]; then
        if [ -n "$check_content" ]; then
            if grep -q "$check_content" /tmp/response.html; then
                echo -e "${GREEN}✓ PASSED${NC} (HTTP $response, content found)"
                ((TESTS_PASSED++))
            else
                echo -e "${RED}✗ FAILED${NC} (HTTP $response, content NOT found: '$check_content')"
                ((TESTS_FAILED++))
            fi
        else
            echo -e "${GREEN}✓ PASSED${NC} (HTTP $response)"
            ((TESTS_PASSED++))
        fi
    else
        echo -e "${RED}✗ FAILED${NC} (Expected HTTP $expected_status, got $response)"
        ((TESTS_FAILED++))
    fi
}

echo "Step 1: Homepage"
echo "----------------"
test_endpoint "Homepage accessible" "$BASE_URL" 200 "Ainstein"
echo ""

echo "Step 2: Login Page"
echo "------------------"
test_endpoint "Login page accessible" "$BASE_URL/login" 200 "Login"

# Get CSRF token
CSRF_TOKEN=$(curl -s -c $COOKIES_FILE "$BASE_URL/login" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
echo "CSRF Token: ${CSRF_TOKEN:0:20}..."
echo ""

echo "Step 3: Login (admin@demo.com)"
echo "-------------------------------"
response=$(curl -s -o /tmp/login_response.html -w "%{http_code}" \
    -b $COOKIES_FILE -c $COOKIES_FILE \
    -X POST "$BASE_URL/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Referer: $BASE_URL/login" \
    -d "email=admin@demo.com&password=password&_token=$CSRF_TOKEN" \
    -L)

if [ "$response" -eq "200" ]; then
    if grep -q "Dashboard\|Content Generator\|demo.com" /tmp/login_response.html; then
        echo -e "${GREEN}✓ Login successful${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗ Login failed - not redirected to dashboard${NC}"
        ((TESTS_FAILED++))
    fi
else
    echo -e "${RED}✗ Login failed - HTTP $response${NC}"
    ((TESTS_FAILED++))
fi
echo ""

echo "Step 4: Content Generator"
echo "-------------------------"
test_endpoint "Content Generator page" "$BASE_URL/dashboard/content" 200 "Content Generator"
test_endpoint "Tour Guidato button present" "$BASE_URL/dashboard/content" 200 "startContentGeneratorOnboarding"
test_endpoint "Pages tab visible" "$BASE_URL/dashboard/content" 200 "Pages"
test_endpoint "Generations tab visible" "$BASE_URL/dashboard/content" 200 "Generations"
test_endpoint "Prompts tab visible" "$BASE_URL/dashboard/content" 200 "Prompts"
echo ""

echo "Step 5: Pages Tab Content"
echo "-------------------------"
test_endpoint "Create Page button present" "$BASE_URL/dashboard/content?tab=pages" 200 "Create Page"
test_endpoint "Edit button present" "$BASE_URL/dashboard/content?tab=pages" 200 "fa-edit"
test_endpoint "Generate button present" "$BASE_URL/dashboard/content?tab=pages" 200 "fa-magic"
test_endpoint "Delete button present" "$BASE_URL/dashboard/content?tab=pages" 200 "fa-trash"
test_endpoint "Test page visible" "$BASE_URL/dashboard/content?tab=pages" 200 "scarpe-running-test"
echo ""

echo "Step 6: Create Page Form"
echo "------------------------"
test_endpoint "Create page form accessible" "$BASE_URL/dashboard/pages/create" 200 "Create New Page"
test_endpoint "URL field present" "$BASE_URL/dashboard/pages/create" 200 'name="url"'
test_endpoint "Keyword field present" "$BASE_URL/dashboard/pages/create" 200 'name="keyword"'
echo ""

echo "Step 7: Generations Tab"
echo "-----------------------"
test_endpoint "Generations tab accessible" "$BASE_URL/dashboard/content?tab=generations" 200 "Generations"
test_endpoint "Test generation visible" "$BASE_URL/dashboard/content?tab=generations" 200 "completed"
test_endpoint "View button present" "$BASE_URL/dashboard/content?tab=generations" 200 "fa-eye"
test_endpoint "Edit button present" "$BASE_URL/dashboard/content?tab=generations" 200 "fa-edit"
test_endpoint "Copy button present" "$BASE_URL/dashboard/content?tab=generations" 200 "fa-copy"
test_endpoint "Token count visible" "$BASE_URL/dashboard/content?tab=generations" 200 "fa-coins"
echo ""

echo "Step 8: Prompts Tab"
echo "-------------------"
test_endpoint "Prompts tab accessible" "$BASE_URL/dashboard/content?tab=prompts" 200 "Prompts"
test_endpoint "Create Prompt button present" "$BASE_URL/dashboard/content?tab=prompts" 200 "Create Prompt"
test_endpoint "Prompt cards visible" "$BASE_URL/dashboard/content?tab=prompts" 200 "Articolo Blog SEO"
test_endpoint "System badge visible" "$BASE_URL/dashboard/content?tab=prompts" 200 "System"
echo ""

echo "Step 9: JavaScript & Assets"
echo "---------------------------"
test_endpoint "App.js loaded" "$BASE_URL/build/assets/app-*.js" 200
test_endpoint "App.css loaded" "$BASE_URL/build/assets/app-*.css" 200
echo ""

echo "=================================="
echo "📊 TEST SUMMARY"
echo "=================================="
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"
echo "Total Tests: $((TESTS_PASSED + TESTS_FAILED))"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}🎉 ALL TESTS PASSED!${NC}"
    exit 0
else
    echo -e "\n${RED}❌ SOME TESTS FAILED${NC}"
    exit 1
fi
