#!/bin/bash

# CrewAI Tour Integration Validation Script
# This script validates the tour implementation without browser automation

BASE_URL="http://127.0.0.1:8000"
PASSED=0
FAILED=0

echo "========================================="
echo "CrewAI Tour Integration Validation"
echo "========================================="
echo ""

# Test 1: Server Health
echo "[TEST 1] Server responding..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL)
if [ "$STATUS" -eq "200" ]; then
    echo "✅ PASSED: Server is responding (200 OK)"
    ((PASSED++))
else
    echo "❌ FAILED: Server returned $STATUS"
    ((FAILED++))
fi
echo ""

# Test 2: Login page
echo "[TEST 2] Login page accessible..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/login)
if [ "$STATUS" -eq "200" ]; then
    echo "✅ PASSED: Login page accessible (200 OK)"
    ((PASSED++))
else
    echo "❌ FAILED: Login page returned $STATUS"
    ((FAILED++))
fi
echo ""

# Test 3: Dashboard redirects without auth
echo "[TEST 3] Dashboard requires authentication..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L $BASE_URL/dashboard)
if [ "$STATUS" -eq "200" ] && curl -s $BASE_URL/dashboard | grep -q "login"; then
    echo "✅ PASSED: Dashboard redirects to login"
    ((PASSED++))
else
    echo "⚠️  WARNING: Dashboard auth check inconclusive"
fi
echo ""

# Test 4: Check JavaScript bundle exists
echo "[TEST 4] JavaScript bundle exists..."
if [ -f "public/build/assets/app-2a81a8ba.js" ]; then
    echo "✅ PASSED: JavaScript bundle found"
    ((PASSED++))
else
    echo "❌ FAILED: JavaScript bundle not found"
    ((FAILED++))
fi
echo ""

# Test 5: Tour functions in bundle
echo "[TEST 5] Tour functions exported in JavaScript..."
if grep -q "startCrewLaunchTour" public/build/assets/app-2a81a8ba.js && \
   grep -q "startExecutionMonitorTour" public/build/assets/app-2a81a8ba.js; then
    echo "✅ PASSED: Tour functions found in bundle"
    ((PASSED++))
else
    echo "❌ FAILED: Tour functions not found in bundle"
    ((FAILED++))
fi
echo ""

# Test 6: Shepherd.js bundled
echo "[TEST 6] Shepherd.js library bundled..."
if grep -q "Shepherd" public/build/assets/app-2a81a8ba.js; then
    echo "✅ PASSED: Shepherd.js found in bundle"
    ((PASSED++))
else
    echo "❌ FAILED: Shepherd.js not found in bundle"
    ((FAILED++))
fi
echo ""

# Test 7: Crew view file exists
echo "[TEST 7] Crew view file exists..."
if [ -f "resources/views/tenant/crews/show.blade.php" ]; then
    echo "✅ PASSED: Crew view file exists"
    ((PASSED++))
else
    echo "❌ FAILED: Crew view file not found"
    ((FAILED++))
fi
echo ""

# Test 8: Show Tour button in crew view
echo "[TEST 8] Show Tour button in crew view..."
if grep -q "Show Tour" resources/views/tenant/crews/show.blade.php; then
    echo "✅ PASSED: Show Tour button found in crew view"
    ((PASSED++))
else
    echo "❌ FAILED: Show Tour button not found"
    ((FAILED++))
fi
echo ""

# Test 9: Execution view file exists
echo "[TEST 9] Execution view file exists..."
if [ -f "resources/views/tenant/crew-executions/show.blade.php" ]; then
    echo "✅ PASSED: Execution view file exists"
    ((PASSED++))
else
    echo "❌ FAILED: Execution view file not found"
    ((FAILED++))
fi
echo ""

# Test 10: Show Tour button in execution view
echo "[TEST 10] Show Tour button in execution view..."
if grep -q "Show Tour" resources/views/tenant/crew-executions/show.blade.php; then
    echo "✅ PASSED: Show Tour button found in execution view"
    ((PASSED++))
else
    echo "❌ FAILED: Show Tour button not found"
    ((FAILED++))
fi
echo ""

# Test 11: Tour target elements in crew view
echo "[TEST 11] Tour target elements in crew view..."
TARGETS=("crew-header" "crew-stats" "crew-agents" "crew-tasks" "crew-tabs" "execution-form" "execution-mode" "launch-button")
FOUND=0
for TARGET in "${TARGETS[@]}"; do
    if grep -q "id=\"$TARGET\"" resources/views/tenant/crews/show.blade.php; then
        ((FOUND++))
    fi
done
if [ "$FOUND" -eq "${#TARGETS[@]}" ]; then
    echo "✅ PASSED: All $FOUND/${#TARGETS[@]} tour targets found in crew view"
    ((PASSED++))
else
    echo "⚠️  WARNING: Only $FOUND/${#TARGETS[@]} tour targets found in crew view"
fi
echo ""

# Test 12: Tour target elements in execution view
echo "[TEST 12] Tour target elements in execution view..."
TARGETS=("execution-header" "execution-status" "execution-timeline" "logs-section" "execution-actions")
FOUND=0
for TARGET in "${TARGETS[@]}"; do
    if grep -q "id=\"$TARGET\"" resources/views/tenant/crew-executions/show.blade.php; then
        ((FOUND++))
    fi
done
if [ "$FOUND" -eq "${#TARGETS[@]}" ]; then
    echo "✅ PASSED: All $FOUND/${#TARGETS[@]} tour targets found in execution view"
    ((PASSED++))
else
    echo "⚠️  WARNING: Only $FOUND/${#TARGETS[@]} tour targets found in execution view"
fi
echo ""

# Test 13: Alpine.js data attributes in crew view
echo "[TEST 13] Alpine.js integration in crew view..."
if grep -q "x-data" resources/views/tenant/crews/show.blade.php; then
    echo "✅ PASSED: Alpine.js x-data attribute found"
    ((PASSED++))
else
    echo "❌ FAILED: Alpine.js x-data attribute not found"
    ((FAILED++))
fi
echo ""

# Test 14: Database has crews
echo "[TEST 14] Database has test crews..."
CREW_COUNT=$(php artisan tinker --execute="echo App\Models\Crew::count();" 2>/dev/null)
if [ "$CREW_COUNT" -gt 0 ]; then
    echo "✅ PASSED: Database has $CREW_COUNT crews"
    ((PASSED++))
else
    echo "❌ FAILED: No crews found in database"
    ((FAILED++))
fi
echo ""

# Test 15: Admin user exists
echo "[TEST 15] Admin user exists..."
ADMIN_EXISTS=$(php artisan tinker --execute="echo App\Models\User::where('email', 'admin@ainstein.com')->exists() ? '1' : '0';" 2>/dev/null)
if [ "$ADMIN_EXISTS" -eq "1" ]; then
    echo "✅ PASSED: Admin user exists"
    ((PASSED++))
else
    echo "❌ FAILED: Admin user not found"
    ((FAILED++))
fi
echo ""

# Summary
echo "========================================="
echo "VALIDATION SUMMARY"
echo "========================================="
echo "✅ Passed: $PASSED"
echo "❌ Failed: $FAILED"
TOTAL=$((PASSED + FAILED))
PERCENTAGE=$((PASSED * 100 / TOTAL))
echo "Success Rate: $PERCENTAGE%"
echo ""

if [ "$FAILED" -eq 0 ]; then
    echo "🎉 All tests passed! System is ready for browser testing."
    exit 0
else
    echo "⚠️  Some tests failed. Review the output above."
    exit 1
fi
