#!/bin/bash

echo "🧪 SIMULATING LOGIN WITH DETAILED LOGGING"
echo "========================================"

cd "C:\laragon\www\ainstein-3\ainstein-laravel"

# Clear old cookies
rm -f test_cookies.txt

# Get CSRF token
echo "1. Getting CSRF token..."
curl -s -c test_cookies.txt http://127.0.0.1:8080/login > /tmp/login_page.html
CSRF=$(grep -oP 'name="_token"\s+value="\K[^"]+' /tmp/login_page.html | head -1)

if [ -z "$CSRF" ]; then
    echo "❌ Failed to get CSRF token"
    exit 1
fi

echo "✅ CSRF token: ${CSRF:0:20}..."

# Submit login
echo "2. Submitting login..."
curl -v -L -b test_cookies.txt -c test_cookies.txt \
    -X POST http://127.0.0.1:8080/login \
    -d "email=demo@tenant.com" \
    -d "password=password" \
    -d "_token=$CSRF" \
    2>&1 | grep -E "(Location:|< HTTP)"

echo ""
echo "3. Waiting for logs..."
sleep 2

echo ""
echo "========================================"
echo "📋 LARAVEL LOGS:"
echo "========================================"
tail -100 storage/logs/laravel.log

rm -f test_cookies.txt /tmp/login_page.html
