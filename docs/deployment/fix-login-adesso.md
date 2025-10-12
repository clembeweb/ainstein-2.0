# 🚨 FIX LOGIN PRODUZIONE - ESECUZIONE IMMEDIATA

## PROBLEMA
Il login su **https://ainstein.it** fa redirect loop → torna sempre al login.

## SOLUZIONE (5 MINUTI)

### STEP 1: Accedi al server di produzione

```bash
# Sostituisci con i tuoi dati di accesso SSH
ssh user@ainstein.it
# oppure
ssh user@YOUR_SERVER_IP
```

### STEP 2: Vai nella directory dell'applicazione

```bash
cd /var/www/ainstein
# oppure il path dove è installato ainstein
# Esempi comuni:
# cd /home/ainstein/public_html
# cd /var/www/html/ainstein
```

### STEP 3: Scarica lo script di fix

**OPZIONE A - Da locale al server:**
```bash
# Sul tuo PC locale (in C:\laragon\www\ainstein-3)
scp fix-login-production-urgent.sh user@ainstein.it:/var/www/ainstein/
```

**OPZIONE B - Crea il file direttamente sul server:**
```bash
# Sul server di produzione
nano fix-login-urgent.sh

# Copia TUTTO il contenuto del file fix-login-production-urgent.sh
# Salva con CTRL+O, ENTER, CTRL+X
```

**OPZIONE C - Fix manuale rapido (se preferisci):**
```bash
# Sul server di produzione
nano .env

# Trova e modifica/aggiungi queste righe:
APP_URL=https://ainstein.it
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=ainstein.it,www.ainstein.it

# Salva: CTRL+O, ENTER, CTRL+X

# Poi esegui:
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### STEP 4: Esegui lo script (se usi opzione A o B)

```bash
chmod +x fix-login-production-urgent.sh
./fix-login-production-urgent.sh
```

Lo script farà automaticamente:
- ✅ Backup del .env corrente
- ✅ Fix APP_URL → https://ainstein.it
- ✅ Aggiunge SESSION_SECURE_COOKIE=true
- ✅ Aggiunge SESSION_HTTP_ONLY=true
- ✅ Aggiunge SESSION_SAME_SITE=lax
- ✅ Configura SANCTUM_STATEFUL_DOMAINS
- ✅ Clear e rebuild cache config

### STEP 5: Test login

```bash
# Apri browser in INCOGNITO
# Vai su: https://ainstein.it/login
# Prova a fare login
```

---

## ⚠️ SE IL LOGIN ANCORA NON FUNZIONA

### Check 1: Verifica configurazione SSL
```bash
curl -I https://ainstein.it
# Deve rispondere con 200 OK e header HTTPS
```

### Check 2: Verifica utente nel database
```bash
php artisan tinker

# Dentro tinker:
$user = App\Models\User::where('email', 'TUA_EMAIL@example.com')->first();
echo "Active: " . $user->is_active . "\n";
echo "Tenant ID: " . $user->tenant_id . "\n";
echo "Tenant Status: " . $user->tenant->status . "\n";

# Se vedi problemi, fixa l'utente:
$user->is_active = 1;
$user->save();
$user->tenant->status = 'active';
$user->tenant->save();
exit
```

### Check 3: Verifica logs
```bash
tail -f storage/logs/laravel.log
# Tieni aperto e riprova il login, guarda gli errori
```

### Check 4: Clear sessioni vecchie
```bash
php artisan tinker
DB::table('sessions')->truncate();
exit
```

---

## 🎯 DOPO IL FIX - LANCIARE COMET

Una volta che il login funziona:

```bash
# Il sistema è pronto per il testing con Comet
# Puoi procedere con i tuoi test
```

---

## 📋 CHECKLIST RAPIDA

- [ ] SSH in produzione: `ssh user@ainstein.it`
- [ ] Vai in directory: `cd /var/www/ainstein`
- [ ] Carica script: `scp fix-login-production-urgent.sh user@ainstein.it:/var/www/ainstein/`
- [ ] Rendi eseguibile: `chmod +x fix-login-production-urgent.sh`
- [ ] Esegui: `./fix-login-production-urgent.sh`
- [ ] Test login: browser incognito → https://ainstein.it/login
- [ ] ✅ LOGIN FUNZIONA? Procedi con Comet!

---

## 🆘 CONTATTI EMERGENZA

Se hai problemi, verifica:
1. Il certificato SSL è valido e attivo
2. Il web server (Nginx/Apache) è in esecuzione
3. PHP-FPM è in esecuzione
4. Il database è accessibile

```bash
# Check services
sudo systemctl status nginx    # o apache2
sudo systemctl status php-fpm  # o php8.x-fpm
sudo systemctl status mysql    # o mariadb
```

---

**NOTA IMPORTANTE**: Questo fix è chirurgico e NON interferisc con il lavoro degli altri agenti. Modifica SOLO le configurazioni di sessione e fa clear cache minimal.
