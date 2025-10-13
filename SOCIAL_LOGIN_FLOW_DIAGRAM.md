# Social Login Flow Diagram

Visual representation of the OAuth 2.0 authentication flow for Google and Facebook login in Ainstein platform.

---

## Complete Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         USER INITIATES LOGIN                                 │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
                    ┌──────────────────────────────┐
                    │   User visits /login page    │
                    │                              │
                    │  [Email/Password]            │
                    │  [Continue with Google]      │◄──── Social Login Buttons
                    │  [Continue with Facebook]    │      (conditional display)
                    └──────────────────────────────┘
                                    │
                                    │ User clicks
                                    │ "Continue with Google"
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         STEP 1: REDIRECT TO PROVIDER                         │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────────────────────┐
                    │  Route: /auth/google          │
                    │  Controller:                  │
                    │  SocialAuthController         │
                    │  ->redirectToProvider()       │
                    └───────────────────────────────┘
                                    │
                                    │ Laravel Socialite
                                    │ generates OAuth URL
                                    ▼
                    ┌───────────────────────────────┐
                    │   Redirect to Google:         │
                    │                               │
                    │   https://accounts.google.com │
                    │   /o/oauth2/v2/auth?          │
                    │   client_id=...               │
                    │   redirect_uri=...            │
                    │   scope=email+profile         │
                    │   state=...                   │
                    └───────────────────────────────┘
                                    │
┌─────────────────────────────────────────────────────────────────────────────┐
│                      STEP 2: USER AUTHENTICATES                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────────────────────┐
                    │   Google Login Screen         │
                    │                               │
                    │   - Select Account            │
                    │   - Enter Password (if needed)│
                    │   - Review Permissions        │
                    │   - Click "Allow"             │
                    └───────────────────────────────┘
                                    │
                                    │ User grants permission
                                    ▼
                    ┌───────────────────────────────┐
                    │   Google generates            │
                    │   authorization code          │
                    └───────────────────────────────┘
                                    │
┌─────────────────────────────────────────────────────────────────────────────┐
│                       STEP 3: CALLBACK TO APP                                │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────────────────────┐
                    │   Redirect to:                │
                    │   /auth/google/callback       │
                    │   ?code=...&state=...         │
                    └───────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │  SocialAuthController         │
                    │  ->handleProviderCallback()   │
                    └───────────────────────────────┘
                                    │
                                    │ Laravel Socialite
                                    │ exchanges code for token
                                    ▼
                    ┌───────────────────────────────┐
                    │  Socialite::driver('google')  │
                    │  ->user()                     │
                    │                               │
                    │  Returns:                     │
                    │  - Email                      │
                    │  - Name                       │
                    │  - Google ID                  │
                    │  - Avatar URL                 │
                    └───────────────────────────────┘
                                    │
┌─────────────────────────────────────────────────────────────────────────────┐
│                    STEP 4: USER LOOKUP / CREATION                            │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────────────────────┐
                    │  Check database:              │
                    │  User::where('email', ...)    │
                    └───────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
        ┌─────────────────────┐       ┌─────────────────────┐
        │   User EXISTS       │       │   User NOT FOUND    │
        │   (Existing User)   │       │   (New User)        │
        └─────────────────────┘       └─────────────────────┘
                    │                               │
                    │                               │
                    ▼                               ▼
        ┌─────────────────────┐       ┌─────────────────────┐
        │  Update user:       │       │  DB Transaction:    │
        │  - social_provider  │       │                     │
        │  - social_id        │       │  1. Create Tenant   │
        │  - social_avatar    │       │     - name          │
        │                     │       │     - slug          │
        │  Log in user        │       │     - plan: free    │
        └─────────────────────┘       │                     │
                    │                 │  2. Create User     │
                    │                 │     - name          │
                    │                 │     - email         │
                    │                 │     - tenant_id     │
                    │                 │     - role: owner   │
                    │                 │     - social fields │
                    │                 │                     │
                    │                 │  3. Send email      │
                    │                 │     - Welcome email │
                    │                 │                     │
                    │                 │  Log in user        │
                    │                 └─────────────────────┘
                    │                               │
                    └───────────────┬───────────────┘
                                    │
┌─────────────────────────────────────────────────────────────────────────────┐
│                     STEP 5: CREATE SESSION & REDIRECT                        │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────────────────────┐
                    │   Auth::login($user)          │
                    │                               │
                    │   - Create session            │
                    │   - Set auth cookie           │
                    └───────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │   Redirect to /dashboard      │
                    │                               │
                    │   With flash message:         │
                    │   "Welcome back!" or          │
                    │   "Account created!"          │
                    └───────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │   User Dashboard              │
                    │   - Full access               │
                    │   - Authenticated session     │
                    │   - Tenant workspace loaded   │
                    └───────────────────────────────┘
```

---

## Configuration Flow

Shows how OAuth credentials are loaded and used:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      APPLICATION STARTS / CONFIG LOADED                      │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │   config/services.php         │
                    │   is evaluated                │
                    └───────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
        ┌─────────────────────┐       ┌─────────────────────┐
        │   GOOGLE CONFIG     │       │   FACEBOOK CONFIG   │
        └─────────────────────┘       └─────────────────────┘
                    │                               │
                    │                               │
┌───────────────────▼───────────────────────────────▼───────────────────┐
│                    CREDENTIAL LOOKUP PRIORITY                          │
│                                                                        │
│  For each provider, check in order:                                   │
│                                                                        │
│  1. DATABASE (Highest Priority)                                       │
│     ┌────────────────────────────────────────────┐                   │
│     │  PlatformSetting::first()                  │                   │
│     │  - google_client_id         (dedicated)    │                   │
│     │  - google_console_client_id (fallback)     │                   │
│     │  - facebook_client_id       (dedicated)    │                   │
│     │  - facebook_app_id          (fallback)     │                   │
│     └────────────────────────────────────────────┘                   │
│                           │                                            │
│                           │ If empty, try next...                     │
│                           ▼                                            │
│  2. ENVIRONMENT VARIABLES (.env file)                                 │
│     ┌────────────────────────────────────────────┐                   │
│     │  env('GOOGLE_CLIENT_ID')                   │                   │
│     │  env('GOOGLE_CLIENT_SECRET')               │                   │
│     │  env('FACEBOOK_CLIENT_ID')                 │                   │
│     │  env('FACEBOOK_CLIENT_SECRET')             │                   │
│     └────────────────────────────────────────────┘                   │
│                           │                                            │
│                           │ If empty, fallback to null                │
│                           ▼                                            │
│  3. NULL (No credentials configured)                                  │
│     - Social login buttons will NOT appear                            │
│     - OAuth routes will return error                                  │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │   Config values cached        │
                    │   - Available to app          │
                    │   - Used by Socialite         │
                    └───────────────────────────────┘
```

---

## Database Schema

Shows the data flow and storage:

```
┌──────────────────────────────────────────────────────────────────┐
│                       PLATFORM_SETTINGS TABLE                     │
│                   (Admin-configured OAuth credentials)            │
├──────────────────────────────────────────────────────────────────┤
│  id                                 ULID (Primary Key)           │
│                                                                   │
│  SOCIAL LOGIN CREDENTIALS:                                       │
│  ├─ google_client_id                VARCHAR (nullable)           │
│  ├─ google_client_secret            TEXT (encrypted)             │
│  ├─ facebook_client_id              VARCHAR (nullable)           │
│  └─ facebook_client_secret          TEXT (encrypted)             │
│                                                                   │
│  API INTEGRATION CREDENTIALS (separate from login):              │
│  ├─ google_ads_client_id            VARCHAR (nullable)           │
│  ├─ google_ads_client_secret        TEXT (encrypted)             │
│  ├─ google_console_client_id        VARCHAR (nullable)           │
│  ├─ google_console_client_secret    TEXT (encrypted)             │
│  ├─ facebook_app_id                 VARCHAR (nullable)           │
│  └─ facebook_app_secret             TEXT (encrypted)             │
│                                                                   │
│  created_at, updated_at                                          │
└──────────────────────────────────────────────────────────────────┘
                                    │
                                    │ Referenced by
                                    ▼
┌──────────────────────────────────────────────────────────────────┐
│                           USERS TABLE                             │
│                    (Tenant user accounts)                         │
├──────────────────────────────────────────────────────────────────┤
│  id                             INT (Primary Key)                │
│  tenant_id                      INT (Foreign Key → tenants)      │
│  name                           VARCHAR                          │
│  email                          VARCHAR (unique)                 │
│  email_verified_at              TIMESTAMP (nullable)             │
│  password_hash                  VARCHAR                          │
│                                                                   │
│  SOCIAL LOGIN FIELDS:                                            │
│  ├─ social_provider             VARCHAR (nullable)               │
│  │                              Values: 'google', 'facebook'     │
│  ├─ social_id                   VARCHAR (nullable)               │
│  │                              Provider's user ID               │
│  └─ social_avatar               TEXT (nullable)                  │
│                                 URL to profile picture            │
│                                                                   │
│  role                           ENUM ('owner', 'admin', ...)     │
│  is_active                      BOOLEAN                          │
│  created_at, updated_at                                          │
└──────────────────────────────────────────────────────────────────┘
                                    │
                                    │ Belongs to
                                    ▼
┌──────────────────────────────────────────────────────────────────┐
│                          TENANTS TABLE                            │
│                      (Workspace/Organization)                     │
├──────────────────────────────────────────────────────────────────┤
│  id                             INT (Primary Key)                │
│  name                           VARCHAR                          │
│  slug                           VARCHAR (unique)                 │
│  plan_type                      ENUM ('free', 'pro', ...)        │
│  status                         ENUM ('active', 'suspended')     │
│  tokens_monthly_limit           INT                              │
│  tokens_used_current            INT                              │
│  theme_config                   JSON (nullable)                  │
│  created_at, updated_at                                          │
└──────────────────────────────────────────────────────────────────┘
```

---

## Error Handling Flow

Shows how errors are caught and handled:

```
                    ┌───────────────────────────────┐
                    │   User clicks OAuth button    │
                    └───────────────────────────────┘
                                    │
                    ┌───────────────▼───────────────┐
                    │  Try: Redirect to provider    │
                    └───────────────┬───────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
        ┌─────────────────────┐       ┌─────────────────────┐
        │   SUCCESS           │       │   EXCEPTION         │
        │   - Redirect user   │       │   - Invalid config  │
        └─────────────────────┘       │   - Network error   │
                                      └─────────────────────┘
                                                  │
                                                  ▼
                                      ┌─────────────────────┐
                                      │   Log error         │
                                      │   storage/logs/     │
                                      │   laravel.log       │
                                      └─────────────────────┘
                                                  │
                                                  ▼
                                      ┌─────────────────────┐
                                      │   Redirect to /     │
                                      │   With error:       │
                                      │   "Service          │
                                      │   unavailable"      │
                                      └─────────────────────┘

                    ┌───────────────────────────────┐
                    │   Provider callback received  │
                    └───────────────────────────────┘
                                    │
                    ┌───────────────▼───────────────┐
                    │  Try: Get user from provider  │
                    └───────────────┬───────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
        ┌─────────────────────┐       ┌─────────────────────┐
        │   SUCCESS           │       │   EXCEPTION         │
        │   - Create/update   │       │                     │
        │     user            │       ├─ InvalidStateEx     │
        │   - Log in          │       │  (session issue)    │
        │   - Redirect        │       │                     │
        └─────────────────────┘       ├─ Generic Exception  │
                                      │  (any other error)  │
                                      └─────────────────────┘
                                                  │
                                                  ▼
                                      ┌─────────────────────┐
                                      │   Log detailed error│
                                      │   - Provider        │
                                      │   - Error message   │
                                      │   - Stack trace     │
                                      └─────────────────────┘
                                                  │
                                                  ▼
                                      ┌─────────────────────┐
                                      │   Redirect to /     │
                                      │   With error:       │
                                      │   "Authentication   │
                                      │   failed. Try again"│
                                      └─────────────────────┘
```

---

## Security Considerations

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           SECURITY LAYERS                                    │
└─────────────────────────────────────────────────────────────────────────────┘

1. CREDENTIAL SECURITY
   ┌────────────────────────────────────────────────┐
   │  - Client secrets encrypted in database        │
   │  - .env file not in version control            │
   │  - APP_KEY used for encryption                 │
   │  - No secrets in frontend/JavaScript           │
   └────────────────────────────────────────────────┘

2. TRANSPORT SECURITY
   ┌────────────────────────────────────────────────┐
   │  - HTTPS enforced in production                │
   │  - OAuth tokens transmitted securely           │
   │  - SESSION_SECURE_COOKIE=true in production    │
   └────────────────────────────────────────────────┘

3. STATE PARAMETER
   ┌────────────────────────────────────────────────┐
   │  - Laravel Socialite generates random state    │
   │  - Validated on callback                       │
   │  - Prevents CSRF attacks                       │
   └────────────────────────────────────────────────┘

4. REDIRECT URI VALIDATION
   ┌────────────────────────────────────────────────┐
   │  - Exact match required                        │
   │  - Configured in Google/Facebook console       │
   │  - Prevents authorization code theft           │
   └────────────────────────────────────────────────┘

5. RATE LIMITING
   ┌────────────────────────────────────────────────┐
   │  - Throttle OAuth routes                       │
   │  - Prevent brute force attempts                │
   │  - 10 requests per minute per IP               │
   └────────────────────────────────────────────────┘

6. EMAIL VERIFICATION
   ┌────────────────────────────────────────────────┐
   │  - OAuth providers verify email                │
   │  - email_verified_at set automatically         │
   │  - Trusted identity providers                  │
   └────────────────────────────────────────────────┘

7. DATABASE SECURITY
   ┌────────────────────────────────────────────────┐
   │  - Prepared statements (SQL injection safe)    │
   │  - Encrypted sensitive fields                  │
   │  - Proper indexing on email (unique)           │
   └────────────────────────────────────────────────┘

8. SESSION MANAGEMENT
   ┌────────────────────────────────────────────────┐
   │  - Secure session cookies                      │
   │  - Session rotation after login                │
   │  - Configurable session lifetime               │
   └────────────────────────────────────────────────┘
```

---

## Testing Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            TESTING CHECKLIST                                 │
└─────────────────────────────────────────────────────────────────────────────┘

TEST 1: Configuration Verification
   │
   ├─ Check .env or database for credentials
   ├─ Run: php artisan config:clear
   ├─ Run: php artisan tinker
   └─ >>> config('services.google.client_id')
       Expected: Returns your Client ID or function closure

TEST 2: UI Verification
   │
   ├─ Navigate to /login
   ├─ Look for "Or continue with" section
   ├─ Verify Google button appears (if configured)
   └─ Verify Facebook button appears (if configured)

TEST 3: Google OAuth Flow
   │
   ├─ Click "Google" button
   ├─ Redirected to accounts.google.com
   ├─ Select account and allow
   ├─ Redirected back to app
   ├─ Should see /dashboard
   └─ Success message appears

TEST 4: Database Verification
   │
   ├─ Query users table
   ├─ Find newly created user
   ├─ Verify fields:
   │   ├─ email (from Google)
   │   ├─ name (from Google)
   │   ├─ social_provider = "google"
   │   ├─ social_id (Google user ID)
   │   └─ social_avatar (profile pic URL)
   └─ Verify tenant was created

TEST 5: Existing User Flow
   │
   ├─ Create user: test@example.com (email/password)
   ├─ Logout
   ├─ Login with Google using same email
   ├─ Should succeed
   ├─ User account updated with Google info
   └─ Can now login via both methods

TEST 6: Error Scenarios
   │
   ├─ Test with invalid credentials
   ├─ Test with wrong redirect URI
   ├─ Test in development mode (not added as test user)
   ├─ Verify error messages appear
   └─ Check logs for detailed errors
```

---

## Files Reference Map

Shows which files are involved in the OAuth flow:

```
PROJECT ROOT
│
├─ .env                                     ⚙️ Environment configuration
│   ├─ APP_URL                                (Callback URL base)
│   ├─ GOOGLE_CLIENT_ID                       (Fallback config)
│   ├─ GOOGLE_CLIENT_SECRET
│   ├─ FACEBOOK_CLIENT_ID
│   └─ FACEBOOK_CLIENT_SECRET
│
├─ config/
│   └─ services.php                         ⚙️ OAuth provider configuration
│       ├─ google.client_id                   (DB → .env fallback)
│       ├─ google.client_secret
│       ├─ google.redirect                    (Callback URL)
│       ├─ facebook.client_id
│       ├─ facebook.client_secret
│       └─ facebook.redirect
│
├─ app/
│   ├─ Models/
│   │   ├─ User.php                         📊 User model
│   │   │   ├─ $fillable: social_provider, social_id, social_avatar
│   │   │   └─ Relationships: tenant()
│   │   │
│   │   ├─ Tenant.php                       📊 Tenant model
│   │   │   └─ Relationships: users()
│   │   │
│   │   └─ PlatformSetting.php              📊 Settings model
│   │       ├─ $fillable: google_client_id, facebook_client_id, etc.
│   │       └─ $casts: Encrypted secrets
│   │
│   └─ Http/Controllers/Auth/
│       └─ SocialAuthController.php         🎮 Main OAuth controller
│           ├─ redirectToProvider()          (Initiates OAuth)
│           ├─ handleProviderCallback()      (Processes callback)
│           ├─ createUserFromSocial()        (Creates new user)
│           └─ updateUserSocialInfo()        (Updates existing user)
│
├─ routes/
│   ├─ web.php                              🛣️ Web routes
│   │   ├─ GET  /auth/{provider}            → redirectToProvider
│   │   └─ GET  /auth/{provider}/callback   → handleProviderCallback
│   │
│   └─ api.php                              🛣️ API routes (optional)
│       ├─ GET  /api/auth/{provider}        → apiRedirectToProvider
│       └─ POST /api/auth/{provider}/callback → apiHandleProviderCallback
│
├─ resources/views/auth/
│   └─ login.blade.php                      🎨 Login page UI
│       ├─ Email/password form
│       ├─ Social login buttons (conditional)
│       │   ├─ @if(platform_setting('google_console_client_id'))
│       │   └─ @if(platform_setting('facebook_app_id'))
│       └─ Route links: route('social.redirect', 'google')
│
├─ database/migrations/
│   ├─ *_add_social_auth_columns_to_users_table.php
│   │   ├─ social_provider (varchar)
│   │   ├─ social_id (varchar)
│   │   └─ social_avatar (text)
│   │
│   └─ *_expand_platform_settings_oauth.php
│       ├─ google_client_id, google_client_secret
│       ├─ facebook_client_id, facebook_client_secret
│       └─ ... other OAuth fields
│
└─ storage/logs/
    └─ laravel.log                          📝 Application logs
        ├─ Social auth successes
        ├─ OAuth errors
        └─ User creation logs
```

---

## Related Documentation

- **[SOCIAL_LOGIN_SETUP_GUIDE.md](SOCIAL_LOGIN_SETUP_GUIDE.md)** - Complete setup guide (36 KB)
- **[SOCIAL_LOGIN_QUICK_START.md](SOCIAL_LOGIN_QUICK_START.md)** - Quick reference (6 KB)
- **[OAUTH-SETTINGS-ANALYSIS.md](OAUTH-SETTINGS-ANALYSIS.md)** - Technical analysis of OAuth configuration

---

Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
