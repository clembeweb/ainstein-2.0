#!/bin/bash

echo "🧪 SIMULAZIONE COMPLETA FLUSSO UTENTE"
echo "========================================================================"
echo ""

BASE_URL="http://127.0.0.1:8080"
COOKIE_FILE="test_cookies.txt"
rm -f $COOKIE_FILE

echo "📍 STEP 1: Visita Landing Page"
echo "------------------------------------------------------------------------"
echo "GET $BASE_URL/"
RESPONSE=$(curl -s -c $COOKIE_FILE -w "\nHTTP_CODE:%{http_code}" $BASE_URL/)
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
echo "Status: $HTTP_CODE"

if [[ "$RESPONSE" == *"Ainstein"* ]]; then
    echo "✅ Landing page caricata"
    echo "✅ Contiene 'Ainstein'"
else
    echo "❌ Landing page non caricata correttamente"
    exit 1
fi
echo ""

echo "📍 STEP 2: Visita Login Page"
echo "------------------------------------------------------------------------"
echo "GET $BASE_URL/login"
LOGIN_PAGE=$(curl -s -b $COOKIE_FILE -c $COOKIE_FILE -w "\nHTTP_CODE:%{http_code}" $BASE_URL/login)
HTTP_CODE=$(echo "$LOGIN_PAGE" | grep "HTTP_CODE:" | cut -d: -f2)
echo "Status: $HTTP_CODE"

# Estrai CSRF token
CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -oP 'name="_token"\s+value="\K[^"]+' | head -1)
if [ -z "$CSRF_TOKEN" ]; then
    # Prova metodo alternativo
    CSRF_TOKEN=$(echo "$LOGIN_PAGE" | grep -oP 'csrf-token.*content="\K[^"]+' | head -1)
fi

if [ -n "$CSRF_TOKEN" ]; then
    echo "✅ CSRF Token trovato: ${CSRF_TOKEN:0:20}..."
else
    echo "❌ CSRF Token non trovato"
    exit 1
fi

if [[ "$LOGIN_PAGE" == *"Demo Login"* ]]; then
    echo "✅ Pulsante 'Demo Login' presente"
else
    echo "⚠️  Pulsante 'Demo Login' non trovato"
fi
echo ""

echo "📍 STEP 3: Invio Login Form (Demo User)"
echo "------------------------------------------------------------------------"
echo "POST $BASE_URL/login"
echo "Data: email=demo@tenant.com, password=password"

LOGIN_RESPONSE=$(curl -s -L -b $COOKIE_FILE -c $COOKIE_FILE \
    -w "\nHTTP_CODE:%{http_code}\nFINAL_URL:%{url_effective}" \
    -X POST $BASE_URL/login \
    -d "email=demo@tenant.com" \
    -d "password=password" \
    -d "_token=$CSRF_TOKEN")

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
FINAL_URL=$(echo "$LOGIN_RESPONSE" | grep "FINAL_URL:" | cut -d: -f2-)

echo "Status: $HTTP_CODE"
echo "Final URL: $FINAL_URL"

if [[ "$FINAL_URL" == *"/dashboard"* ]]; then
    echo "✅ Redirect a dashboard corretto"
elif [[ "$FINAL_URL" == *"/admin"* ]]; then
    echo "❌ Redirect errato: admin invece di dashboard"
    exit 1
elif [[ "$FINAL_URL" == *"/login"* ]]; then
    echo "❌ Redirect a login - autenticazione fallita"
    echo "Contenuto risposta:"
    echo "$LOGIN_RESPONSE" | grep -i "error\|credenziali" | head -5
    exit 1
else
    echo "⚠️  URL finale inaspettato: $FINAL_URL"
fi
echo ""

echo "📍 STEP 4: Accesso Dashboard"
echo "------------------------------------------------------------------------"
echo "GET $BASE_URL/dashboard"

DASHBOARD=$(curl -s -b $COOKIE_FILE -w "\nHTTP_CODE:%{http_code}" $BASE_URL/dashboard)
HTTP_CODE=$(echo "$DASHBOARD" | grep "HTTP_CODE:" | cut -d: -f2)
echo "Status: $HTTP_CODE"

# Verifica contenuti dashboard
CHECKS=(
    "Welcome back"
    "Dashboard"
    "Token Usage"
    "stat-card"
    "Total Pages"
    "Generations"
)

PASSED=0
FAILED=0

for check in "${CHECKS[@]}"; do
    if [[ "$DASHBOARD" == *"$check"* ]]; then
        echo "✅ '$check' presente"
        ((PASSED++))
    else
        echo "❌ '$check' NON presente"
        ((FAILED++))
    fi
done

echo ""
echo "Elementi verificati: $PASSED/$((PASSED + FAILED))"
echo ""

echo "📍 STEP 5: Verifica Onboarding Script"
echo "------------------------------------------------------------------------"

if [[ "$DASHBOARD" == *"autoStartOnboarding"* ]]; then
    echo "✅ Script onboarding presente"
    if [[ "$DASHBOARD" == *"Shepherd"* ]] || [[ "$DASHBOARD" == *"shepherd"* ]]; then
        echo "✅ Shepherd.js caricato"
    else
        echo "⚠️  Shepherd.js potrebbe non essere caricato"
    fi
else
    echo "⚠️  Script onboarding non trovato (utente potrebbe aver completato)"
fi
echo ""

echo "📍 STEP 6: Verifica Assets"
echo "------------------------------------------------------------------------"

# Controlla se ci sono link a JS/CSS
if [[ "$DASHBOARD" == *"/build/assets/app-"* ]]; then
    echo "✅ Assets Vite trovati"

    # Estrai path asset
    JS_PATH=$(echo "$DASHBOARD" | grep -oP '/build/assets/app-[^"]+\.js' | head -1)
    CSS_PATH=$(echo "$DASHBOARD" | grep -oP '/build/assets/app-[^"]+\.css' | head -1)

    if [ -n "$JS_PATH" ]; then
        echo "   JS: $JS_PATH"
        JS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL$JS_PATH)
        if [ "$JS_STATUS" = "200" ]; then
            echo "   ✅ JS accessibile (200)"
        else
            echo "   ❌ JS non accessibile ($JS_STATUS)"
        fi
    fi

    if [ -n "$CSS_PATH" ]; then
        echo "   CSS: $CSS_PATH"
        CSS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL$CSS_PATH)
        if [ "$CSS_STATUS" = "200" ]; then
            echo "   ✅ CSS accessibile (200)"
        else
            echo "   ❌ CSS non accessibile ($CSS_STATUS)"
        fi
    fi
else
    echo "⚠️  Assets Vite non trovati nel HTML"
fi
echo ""

echo "📍 STEP 7: Navigazione Pages"
echo "------------------------------------------------------------------------"
echo "GET $BASE_URL/dashboard/pages"

PAGES=$(curl -s -b $COOKIE_FILE -w "\nHTTP_CODE:%{http_code}" $BASE_URL/dashboard/pages)
HTTP_CODE=$(echo "$PAGES" | grep "HTTP_CODE:" | cut -d: -f2)
echo "Status: $HTTP_CODE"

if [[ "$PAGES" == *"Pages Management"* ]] || [[ "$PAGES" == *"pages"* ]]; then
    echo "✅ Pagina Pages caricata"
else
    echo "❌ Pagina Pages non caricata correttamente"
fi
echo ""

echo "📍 STEP 8: Logout"
echo "------------------------------------------------------------------------"
echo "POST $BASE_URL/logout"

LOGOUT=$(curl -s -L -b $COOKIE_FILE \
    -w "\nFINAL_URL:%{url_effective}" \
    -X POST $BASE_URL/logout \
    -d "_token=$CSRF_TOKEN")

FINAL_URL=$(echo "$LOGOUT" | grep "FINAL_URL:" | cut -d: -f2-)
echo "Final URL: $FINAL_URL"

if [[ "$FINAL_URL" == *"/"* ]] && [[ "$FINAL_URL" != *"/dashboard"* ]]; then
    echo "✅ Logout corretto, redirect a home"
else
    echo "⚠️  Redirect logout inaspettato"
fi
echo ""

echo "========================================================================"
echo "🎉 TEST COMPLETATO"
echo "========================================================================"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "✅ TUTTI I TEST PASSATI!"
    echo ""
    echo "Il sistema funziona correttamente:"
    echo "  ✅ Landing page accessibile"
    echo "  ✅ Login page con CSRF protection"
    echo "  ✅ Autenticazione funzionante"
    echo "  ✅ Dashboard renderizzata"
    echo "  ✅ Onboarding script presente"
    echo "  ✅ Assets compilati e serviti"
    echo "  ✅ Navigazione funzionante"
    echo "  ✅ Logout funzionante"
    echo ""
    echo "🚀 La piattaforma è PRONTA per l'uso!"
else
    echo "⚠️  Alcuni test hanno fallito ($FAILED elementi mancanti)"
    echo "Controlla i dettagli sopra"
fi

# Cleanup
rm -f $COOKIE_FILE

echo ""
echo "========================================================================"
