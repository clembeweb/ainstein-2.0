# 🚀 ESECUZIONE FIX LOGIN PRODUZIONE - AINSTEIN.IT

## ✅ LOCALE RISOLTO
Il login locale ora funziona! Il problema era il nome della tabella `TenantOAuthProvider`.

---

## 🎯 ADESSO: FIX PRODUZIONE

### PROBLEMA IN PRODUZIONE
1. Stesso errore tabella `tenant_o_auth_providers`
2. Configurazioni SESSION per HTTPS mancanti

### SOLUZIONE PRONTA
Script: **`fix-login-production-COMPLETO.sh`**

---

## 📋 OPZIONE 1: ESECUZIONE AUTOMATICA (CONSIGLIATA)

### Step 1: Accedi al server
```bash
ssh user@ainstein.it
# Inserisci password/chiave SSH
```

### Step 2: Vai nella directory Laravel
```bash
cd /var/www/ainstein
# O il path dove è installato (verifica con: find /var/www -name artisan)
```

### Step 3: Upload script dal PC locale
```bash
# Sul tuo PC (in C:\laragon\www\ainstein-3)
scp fix-login-production-COMPLETO.sh user@ainstein.it:/var/www/ainstein/
```

### Step 4: Esegui lo script
```bash
# Sul server
chmod +x fix-login-production-COMPLETO.sh
./fix-login-production-COMPLETO.sh
```

### Step 5: Test
```bash
# Browser incognito
https://ainstein.it/login
```

---

## 📋 OPZIONE 2: ESECUZIONE MANUALE (SE PREFERISCI)

### 1. Fix Model TenantOAuthProvider
```bash
# Sul server
nano app/Models/TenantOAuthProvider.php

# Aggiungi dopo "class TenantOAuthProvider extends Model"
# e prima dei metodi:

    protected $table = 'tenant_oauth_providers';

# Salva: CTRL+O, ENTER, CTRL+X
```

### 2. Fix .env per HTTPS
```bash
nano .env

# Modifica/aggiungi:
APP_URL=https://ainstein.it
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it

# Salva: CTRL+O, ENTER, CTRL+X
```

### 3. Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### 4. Test Login
Browser incognito → https://ainstein.it/login

---

## 📋 OPZIONE 3: DAMMI ACCESSO SSH

Se vuoi che esegua io, forniscimi:

```
Host: ainstein.it (o IP)
User: ?
Password: ? (o chiave SSH)
Path installazione: ? (default: /var/www/ainstein)
```

Posso connettermi ed eseguire il fix automaticamente.

---

## 🔍 COSA FA LO SCRIPT

✅ **Backup automatico** .env e TenantOAuthProvider.php
✅ **Fix modello** → Aggiunge `protected $table = 'tenant_oauth_providers';`
✅ **Fix APP_URL** → `https://ainstein.it`
✅ **Abilita SESSION_SECURE_COOKIE** per HTTPS
✅ **Configura SANCTUM_STATEFUL_DOMAINS**
✅ **Clear e rebuild cache**
✅ **Verifica configurazioni** finali
✅ **Test connessione database**

---

## ⚠️ VERIFICA POST-FIX

Dopo l'esecuzione, verifica:

```bash
# 1. Check configurazioni
php artisan tinker --execute="echo config('app.url')"
# Deve essere: https://ainstein.it

# 2. Test tabella
php artisan tinker --execute="DB::table('tenant_oauth_providers')->count()"
# Non deve dare errori

# 3. Test login (logs in real-time)
tail -f storage/logs/laravel.log
# Apri browser e prova login, guarda i logs
```

---

## 🆘 SE HAI PROBLEMI

### Errore: "Table tenant_oauth_providers not found"
```bash
php artisan migrate:status
php artisan migrate --force
```

### Login ancora non funziona
```bash
# Check utente
php artisan tinker
$user = App\Models\User::first();
echo $user->email . " - Active: " . $user->is_active;
exit

# Se utente non attivo
php artisan tinker
$user = App\Models\User::where('email', 'TUA_EMAIL')->first();
$user->is_active = 1;
$user->save();
exit
```

### SSL/HTTPS issues
```bash
curl -I https://ainstein.it
# Verifica che risponda 200 OK con HTTPS
```

---

## ⏱️ TEMPO STIMATO: 3-5 MINUTI

Tutto il fix è minimale e sicuro:
- ✅ Non tocca database
- ✅ Non tocca migrations
- ✅ Non interferisce con altri agenti
- ✅ Backup automatici creati
- ✅ Rollback facile se necessario

---

## 🎯 DOPO IL FIX

Potrai lanciare **Comet per testing** senza problemi!

---

**Quale opzione preferisci?**
1. Ti guido passo-passo
2. Esegui tu lo script
3. Mi dai accesso SSH e faccio io
