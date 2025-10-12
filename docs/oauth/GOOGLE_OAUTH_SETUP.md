# Google OAuth Configuration Guide - AINSTEIN

**Versione**: 1.1
**Data**: 10 Ottobre 2025
**Applicazione**: AINSTEIN - https://ainstein.it
**Aggiornamento**: Sistema a due livelli con separazione Social Login/API

---

## Overview

AINSTEIN ha già implementato le route e la logica per Google OAuth, ma richiede la configurazione delle credenziali Google Cloud.

**File Coinvolti**:
- `routes/web.php` - Route OAuth (già implementate)
- `app/Http/Controllers/Auth/SocialAuthController.php` - Controller OAuth
- `config/services.php` - Configurazione provider
- `.env` - Credenziali (da aggiungere)

**Route Disponibili**:
- `GET /auth/google` - Redirect a Google
- `GET /auth/google/callback` - Callback dopo autenticazione

---

## Step 1: Creare Progetto Google Cloud

### 1.1 Accedi a Google Cloud Console
1. Vai a https://console.cloud.google.com/
2. Accedi con il tuo account Google
3. Accetta i Terms of Service se richiesto

### 1.2 Crea Nuovo Progetto
1. Click sul dropdown progetto in alto (vicino a "Google Cloud")
2. Click "NEW PROJECT"
3. Compila:
   - **Project name**: `AINSTEIN Production`
   - **Organization**: (lascia default o seleziona la tua)
   - **Location**: (lascia default o seleziona)
4. Click "CREATE"
5. Attendi creazione progetto (1-2 minuti)
6. Seleziona il progetto appena creato dal dropdown

---

## Step 2: Configurare OAuth Consent Screen

### 2.1 Vai alla Schermata di Consenso
1. Menu hamburger → "APIs & Services" → "OAuth consent screen"
2. Oppure: https://console.cloud.google.com/apis/credentials/consent

### 2.2 Configurazione User Type
- **User Type**: Seleziona "External" (per utenti pubblici)
- Click "CREATE"

### 2.3 OAuth Consent Screen Configuration

#### Tab 1: OAuth consent screen
Compila i seguenti campi:

**App information**:
- **App name**: `AINSTEIN`
- **User support email**: `admin@ainstein.com` (o tuo email)
- **App logo**: (opzionale) Upload logo AINSTEIN (120x120px)

**App domain** (opzionale ma raccomandato):
- **Application home page**: `https://ainstein.it`
- **Application privacy policy link**: `https://ainstein.it/privacy` (se presente)
- **Application terms of service link**: `https://ainstein.it/terms` (se presente)

**Authorized domains**:
- Click "ADD DOMAIN"
- Aggiungi: `ainstein.it`

**Developer contact information**:
- **Email addresses**: `admin@ainstein.com` (o tuo email)

Click "SAVE AND CONTINUE"

#### Tab 2: Scopes
1. Click "ADD OR REMOVE SCOPES"
2. Seleziona i seguenti scopes:
   - `.../auth/userinfo.email` - View your email address
   - `.../auth/userinfo.profile` - See your personal info (name, profile picture)
3. Click "UPDATE"
4. Click "SAVE AND CONTINUE"

#### Tab 3: Test users (solo se Publishing Status = Testing)
- **Opzione A**: Aggiungi test users specifici (email)
- **Opzione B**: Pubblica l'app (vai a Step 2.4)
- Click "SAVE AND CONTINUE"

#### Tab 4: Summary
- Rivedi configurazione
- Click "BACK TO DASHBOARD"

### 2.4 Pubblicare l'App (Raccomandato per Produzione)
1. Nella schermata OAuth consent screen
2. Sotto "Publishing status", click "PUBLISH APP"
3. Conferma "CONFIRM"
4. Status diventerà "In production" (nessuna review Google necessaria per scopes basic)

**Nota**: Per scopes basic (email, profile) non serve Google verification. Per scopes sensibili (Drive, Gmail, etc.) serve review.

---

## Step 3: Creare OAuth Credentials

### 3.1 Vai alla Pagina Credentials
1. Menu hamburger → "APIs & Services" → "Credentials"
2. Oppure: https://console.cloud.google.com/apis/credentials

### 3.2 Crea OAuth Client ID
1. Click "CREATE CREDENTIALS" (in alto)
2. Seleziona "OAuth client ID"

### 3.3 Configura Client ID

**Application type**:
- Seleziona: "Web application"

**Name**:
- Inserisci: `AINSTEIN Web Client`

**Authorized JavaScript origins**:
- Click "ADD URI"
- Aggiungi: `https://ainstein.it`

**Authorized redirect URIs**:
- Click "ADD URI"
- Aggiungi: `https://ainstein.it/auth/google/callback`

⚠️ **IMPORTANTE**: L'URL di callback deve essere ESATTAMENTE:
```
https://ainstein.it/auth/google/callback
```
(senza trailing slash, tutto lowercase, HTTPS obbligatorio)

### 3.4 Salva e Copia Credenziali
1. Click "CREATE"
2. Apparirà popup "OAuth client created"
3. **COPIA E SALVA**:
   - **Client ID**: `123456789-abc...xyz.apps.googleusercontent.com`
   - **Client Secret**: `GOCSPX-...`
4. Click "OK"

⚠️ **IMPORTANTE**: Salva queste credenziali in modo sicuro. Il Client Secret è sensibile!

---

## Step 4: Configurare Laravel Application

### 4.1 SSH nel Server
```bash
ssh -i ~/.ssh/ainstein_ploi root@135.181.42.233
cd /var/www/ainstein
```

### 4.2 Modificare File .env

Apri il file `.env`:
```bash
nano .env
```

Aggiungi o modifica le seguenti variabili (sezione OAuth):
```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=123456789-abc...xyz.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-secret-here
GOOGLE_REDIRECT_URI=https://ainstein.it/auth/google/callback
```

**Sostituisci**:
- `GOOGLE_CLIENT_ID` con il tuo Client ID reale
- `GOOGLE_CLIENT_SECRET` con il tuo Client Secret reale

Salva e chiudi (Ctrl+O, Enter, Ctrl+X)

### 4.3 Verificare config/services.php

Verifica che il file `config/services.php` contenga:
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
],
```

Se non presente, aggiungi questa sezione.

### 4.4 Installare Laravel Socialite (se non già installato)

Verifica se Socialite è installato:
```bash
composer show laravel/socialite
```

Se non installato:
```bash
composer require laravel/socialite
```

### 4.5 Clear Cache
```bash
php artisan config:cache
php artisan cache:clear
php artisan route:cache
```

### 4.6 Verificare Tabella Users

Assicurati che la tabella `users` abbia le colonne necessarie:
- `google_id` (nullable, string)
- `avatar` (nullable, string) - per profile picture Google

Se mancano, crea una migration:
```bash
php artisan make:migration add_google_fields_to_users_table
```

Modifica la migration:
```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('google_id')->nullable()->unique()->after('email');
        $table->string('avatar')->nullable()->after('google_id');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['google_id', 'avatar']);
    });
}
```

Esegui migration:
```bash
php artisan migrate
```

### 4.7 Verificare Model User

Apri `app/Models/User.php` e verifica che `fillable` includa:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'google_id',
    'avatar',
    'tenant_id',
    'role',
    // ... altri campi
];
```

---

## Step 5: Verificare Controller OAuth

### 5.1 Verifica SocialAuthController

Il file `app/Http/Controllers/Auth/SocialAuthController.php` dovrebbe gestire:

**Metodo redirectToProvider**:
```php
public function redirectToProvider($provider)
{
    return Socialite::driver($provider)->redirect();
}
```

**Metodo handleProviderCallback**:
```php
public function handleProviderCallback($provider)
{
    try {
        $socialUser = Socialite::driver($provider)->user();

        // Logica per trovare o creare user
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Crea nuovo user
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'google_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => now(),
                // Assegna tenant (logica da definire)
            ]);
        } else {
            // Aggiorna google_id se mancante
            if (!$user->google_id) {
                $user->update(['google_id' => $socialUser->getId()]);
            }
        }

        Auth::login($user);

        return redirect('/dashboard');

    } catch (\Exception $e) {
        return redirect('/login')->with('error', 'Login Google fallito: ' . $e->getMessage());
    }
}
```

⚠️ **IMPORTANTE**: Devi definire la logica per assegnare il `tenant_id` ai nuovi user OAuth.

**Opzioni**:
1. Creare automaticamente un nuovo tenant per ogni nuovo user
2. Richiedere tenant_id durante onboarding post-OAuth
3. Permettere solo OAuth a user già esistenti (no registrazione)

---

## Step 6: Aggiungere Button "Login con Google"

### 6.1 Modifica Login View

Apri `resources/views/auth/login.blade.php` e aggiungi:

```blade
<!-- Dopo il form di login normale -->
<div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">Oppure</span>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('social.redirect', 'google') }}"
           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continua con Google
        </a>
    </div>
</div>
```

### 6.2 Modifica Register View

Opzionalmente, aggiungi lo stesso button anche in `resources/views/auth/register.blade.php`.

---

## Step 7: Testing

### 7.1 Test in Browser

1. Vai a https://ainstein.it/login
2. Click "Continua con Google"
3. Dovresti essere reindirizzato a Google
4. Seleziona account Google
5. Approva permessi (email, profile)
6. Dovresti essere reindirizzato a https://ainstein.it/auth/google/callback
7. Dovresti essere autenticato e reindirizzato a /dashboard

### 7.2 Verifica Database

Controlla che il nuovo user sia stato creato:
```bash
php artisan tinker
```

```php
User::where('google_id', '!=', null)->get();
// Dovrebbe mostrare l'utente appena creato con google_id popolato
```

### 7.3 Test Errori Comuni

**Errore: redirect_uri_mismatch**
- Causa: L'URL di callback non corrisponde esattamente a quello configurato in Google Cloud
- Fix: Verifica che sia esattamente `https://ainstein.it/auth/google/callback`

**Errore: invalid_client**
- Causa: Client ID o Client Secret errati
- Fix: Verifica credenziali in `.env`

**Errore: access_denied**
- Causa: User ha negato permessi
- Fix: Normal flow, gestisci con messaggio user-friendly

**Errore: 500 - Call to a member function user() on null**
- Causa: Socialite non configurato correttamente
- Fix: Verifica `config/services.php` e `composer require laravel/socialite`

---

## Step 8: Sicurezza & Best Practices

### 8.1 Proteggere Client Secret
- **MAI** committare `.env` in Git
- `.env` dovrebbe essere in `.gitignore`
- Usa variabili d'ambiente o secret manager

### 8.2 HTTPS Obbligatorio
- Google OAuth richiede HTTPS in produzione
- ✅ Già configurato: https://ainstein.it

### 8.3 Validare Email Domain (Opzionale)
Se vuoi limitare l'accesso solo a certi domini email:
```php
// In SocialAuthController
$email = $socialUser->getEmail();
$allowedDomains = ['tuodominio.com', 'altrodominio.com'];
$domain = substr(strrchr($email, "@"), 1);

if (!in_array($domain, $allowedDomains)) {
    return redirect('/login')->with('error', 'Email domain non autorizzato');
}
```

### 8.4 Rate Limiting
Aggiungi rate limiting alle route OAuth:
```php
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirectToProvider'])
    ->middleware('throttle:10,1')
    ->name('social.redirect');
```

### 8.5 CSRF Protection
Le route OAuth sono già protette da Laravel CSRF per POST/PUT/DELETE.
GET routes sono safe (nessuna state mutation).

---

## Step 9: Admin Panel Configuration

### 9.1 Sistema di Configurazione a Due Livelli

AINSTEIN implementa un sistema a due livelli per massima flessibilità:

#### Livello 1: Super Admin (Configurazione Globale)

1. Vai a `/admin` (pannello Super Admin)
2. Clicca su **Platform Settings** nel menu laterale
3. Seleziona il tab **Social Login** (primo tab)
4. Nella sezione Google, compila:
   - **Google Social Client ID**: [il tuo client ID per social login]
   - **Google Social Client Secret**: [il tuo secret per social login]
5. Usa il pulsante 📋 per copiare il Callback URL corretto
6. Clicca **Save Google Settings**

**Nota**: Il tab "API Integrations" è separato e contiene credenziali per Google Ads, Search Console, etc.

#### Livello 2: Tenant (Override Configurazione)

Ogni tenant può configurare le proprie credenziali OAuth:

1. Accedi al dashboard del tenant
2. Vai su **Settings** → **OAuth Settings**
3. Configura Google con credenziali specifiche del tenant
4. Testa e abilita la configurazione

### 9.2 Campi Database Aggiornati

La tabella `platform_settings` ora distingue tra:

**Social Login** (per autenticazione utenti):
- `google_social_client_id`
- `google_social_client_secret`

**API Integrations** (per servizi esterni):
- `google_ads_client_id`
- `google_ads_client_secret`
- `google_console_client_id`
- `google_console_client_secret`

### 9.3 Priorità di Risoluzione

Il sistema risolve le credenziali nel seguente ordine:
1. **Tenant Config** (se configurata e abilitata)
2. **Platform Config** (fallback globale)
3. **Non disponibile** (provider non mostrato)

---

## Step 10: Multi-Tenant OAuth Considerations

### 10.1 Assegnazione Tenant per Nuovi User

Quando un user fa OAuth per la prima volta, AINSTEIN deve:

**Opzione A: Crea Tenant Automaticamente**
```php
// In SocialAuthController
if (!$user) {
    // Crea tenant
    $tenant = Tenant::create([
        'name' => $socialUser->getName() . "'s Workspace",
        'slug' => Str::slug($socialUser->getName()) . '-' . Str::random(6),
        'plan_type' => 'free',
        'tokens_monthly_limit' => 100000,
    ]);

    // Crea user e assegna tenant
    $user = User::create([
        'name' => $socialUser->getName(),
        'email' => $socialUser->getEmail(),
        'google_id' => $socialUser->getId(),
        'avatar' => $socialUser->getAvatar(),
        'tenant_id' => $tenant->id,
        'role' => 'owner',
        'email_verified_at' => now(),
    ]);
}
```

**Opzione B: Onboarding Post-OAuth**
```php
// Crea user senza tenant_id
$user = User::create([...]);

// Redirect a onboarding per creare/scegliere tenant
return redirect('/onboarding/tenant-setup');
```

**Opzione C: Solo Login (No Registrazione)**
```php
// Non permettere nuovi account via OAuth
if (!$user) {
    return redirect('/login')->with('error', 'Account non trovato. Registrati prima.');
}
```

### 10.2 Collegare Account Esistente

Se un user ha account con email+password e poi fa OAuth:
```php
$user = User::where('email', $socialUser->getEmail())->first();

if ($user) {
    // Aggiorna con google_id per future login
    $user->update([
        'google_id' => $socialUser->getId(),
        'avatar' => $socialUser->getAvatar(),
        'email_verified_at' => $user->email_verified_at ?? now(),
    ]);
}
```

---

## Step 11: Monitoring & Analytics

### 11.1 Log OAuth Events
```php
Log::info('Google OAuth login', [
    'email' => $socialUser->getEmail(),
    'google_id' => $socialUser->getId(),
    'tenant_id' => $user->tenant_id ?? null,
]);
```

### 11.2 Track OAuth Usage
Aggiungi in `ActivityLog`:
```php
ActivityLog::create([
    'user_id' => $user->id,
    'tenant_id' => $user->tenant_id,
    'action' => 'oauth_login',
    'description' => 'Login via Google OAuth',
    'metadata' => ['provider' => 'google'],
]);
```

---

## Checklist Completa Configurazione

### Google Cloud Console:
- [ ] Progetto creato
- [ ] OAuth Consent Screen configurato
- [ ] App pubblicata (o test users aggiunti)
- [ ] OAuth Client ID creato
- [ ] JavaScript origins: `https://ainstein.it`
- [ ] Redirect URI: `https://ainstein.it/auth/google/callback`
- [ ] Client ID e Secret copiati

### Laravel Application:
- [ ] `.env` aggiornato con credenziali
- [ ] `config/services.php` configurato
- [ ] Laravel Socialite installato
- [ ] `users` table ha colonne `google_id` e `avatar`
- [ ] `User` model fillable aggiornato
- [ ] `SocialAuthController` implementato
- [ ] Route OAuth registrate (`routes/web.php`)
- [ ] Login view ha button "Continua con Google"
- [ ] Config cache cleared
- [ ] Multi-tenant logic definita

### Testing:
- [ ] Test OAuth flow completo
- [ ] Verifica user creato in DB
- [ ] Verifica tenant assegnato
- [ ] Verifica avatar salvato
- [ ] Test errori common (redirect_uri_mismatch, etc.)
- [ ] Test user esistente + OAuth
- [ ] Test logout e re-login via OAuth

---

## Troubleshooting Guide

### Issue: "redirect_uri_mismatch"
**Causa**: URL di callback non corrisponde
**Fix**:
1. Verifica in Google Cloud Console: `https://ainstein.it/auth/google/callback`
2. Verifica in `.env`: `GOOGLE_REDIRECT_URI=https://ainstein.it/auth/google/callback`
3. Clear cache: `php artisan config:cache`
4. NO trailing slash
5. HTTPS obbligatorio

### Issue: "invalid_client"
**Causa**: Credenziali errate
**Fix**:
1. Verifica Client ID e Secret in `.env`
2. Copia esattamente da Google Cloud Console
3. No spazi extra, no quote
4. Clear cache: `php artisan config:cache`

### Issue: "User not found in session"
**Causa**: Session middleware non applicato
**Fix**:
1. Verifica che route OAuth abbiano middleware `web`
2. Verifica `app/Http/Kernel.php` → `$middlewareGroups['web']`

### Issue: "SQLSTATE[23000]: Integrity constraint violation"
**Causa**: Email duplicata o google_id già esistente
**Fix**:
1. Check se user esiste prima di create
2. Usa `updateOrCreate` invece di `create`
3. Handle exception con user-friendly message

### Issue: Avatar non si carica
**Causa**: Google avatar URL è temporaneo
**Fix**:
1. Scarica avatar e salva localmente:
```php
$avatarContents = file_get_contents($socialUser->getAvatar());
$avatarPath = 'avatars/' . $user->id . '.jpg';
Storage::disk('public')->put($avatarPath, $avatarContents);
$user->update(['avatar' => Storage::url($avatarPath)]);
```

---

## Resources & References

- **Laravel Socialite Docs**: https://laravel.com/docs/11.x/socialite
- **Google OAuth Docs**: https://developers.google.com/identity/protocols/oauth2
- **Google Cloud Console**: https://console.cloud.google.com/
- **OAuth Consent Screen**: https://console.cloud.google.com/apis/credentials/consent

---

## Support

Per problemi o domande:
1. Check Laravel logs: `/var/www/ainstein/storage/logs/laravel.log`
2. Check browser console per JavaScript errors
3. Check network tab per API errors
4. Review Google Cloud Console per Quotas & Limits

---

**Fine Guida**

Configurazione completata con successo quando:
✅ User può fare login via Google
✅ Account viene creato o collegato correttamente
✅ Tenant viene assegnato
✅ Dashboard si carica dopo OAuth
✅ Nessun errore nei log
