# Social Login Quick Start Guide

**For developers who need to set up social login quickly**

**Version:** 1.1
**Last Updated:** October 13, 2025 (Critical Fix Applied)

---

## 🆕 IMPORTANT UPDATE (October 13, 2025)

**Critical OAuth Fix Applied:**
- Admin interface now has **TWO SEPARATE SECTIONS** (Blue and Purple backgrounds)
- **Section A (Blue)**: Social Login - USE THIS FOR USER AUTHENTICATION
- **Section B (Purple)**: API Integrations - NOT for Social Login
- Callback URLs now **displayed in admin interface**
- Field naming issue **FIXED** - Social Login works correctly now

This is a condensed version. For complete details, see [SOCIAL_LOGIN_SETUP_GUIDE.md](./SOCIAL_LOGIN_SETUP_GUIDE.md)

---

## Prerequisites

- Google Account
- Facebook Account
- Admin access to Ainstein platform
- 45-60 minutes of time

---

## Google OAuth (20 minutes)

### 1. Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project: "Ainstein Social Login"
3. Enable "Google+ API" or "People API"

### 2. OAuth Consent Screen

- User Type: **External**
- App name: **Ainstein Platform**
- Scopes: `userinfo.email`, `userinfo.profile`, `openid`
- Add test users (your email)

### 3. Create Credentials

- Type: **OAuth client ID**
- Application: **Web application**
- Authorized redirect URIs:
  - Production: `https://yourdomain.com/auth/google/callback`
  - Local: `http://localhost:8000/auth/google/callback`
  - Laragon: `http://ainstein-3.test/auth/google/callback`

### 4. Save Credentials

```
Client ID: 123456789012-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com
Client Secret: GOCSPX-xxxxxxxxxxxxxxxxxxxx
```

---

## Facebook OAuth (25 minutes)

### 1. Facebook Developers Setup

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Register as developer (if first time)
3. Create App > "Authenticate and request data" > Consumer

### 2. Add Facebook Login Product

- Platform: **Web**
- Valid OAuth Redirect URIs:
  ```
  https://yourdomain.com/auth/facebook/callback
  http://localhost:8000/auth/facebook/callback
  http://ainstein-3.test/auth/facebook/callback
  ```

### 3. Configure App Settings

- Settings > Basic
- App Domains: `yourdomain.com`, `localhost`
- Privacy Policy URL: `https://yourdomain.com/privacy` (required)

### 4. Save Credentials

```
App ID: 1234567890123456
App Secret: xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 5. Add Test Users (Development Mode)

- Roles > Test Users > Add

---

## Configuration

### Option A: .env File (Local Dev)

Edit `C:\laragon\www\ainstein-3\ainstein-laravel\.env`:

```bash
APP_URL=http://localhost:8000

# Google OAuth
GOOGLE_CLIENT_ID=123456789012-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxx

# Facebook OAuth
FACEBOOK_CLIENT_ID=1234567890123456
FACEBOOK_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Clear cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### Option B: Admin Dashboard (Production) - UPDATED October 13, 2025

**The admin interface now has dedicated Social Login fields!**

1. Login as Admin (`admin@ainstein.com` / `password`)
2. Go to: Admin Dashboard > Settings > OAuth Integrations
3. **Locate Section A with BLUE BACKGROUND** (Social Login section)
4. **For Google Social Login:**
   - Google Client ID: Paste your Google OAuth Client ID
   - Google Client Secret: Paste your Google OAuth Client Secret
   - Callback URL is displayed: `{{APP_URL}}/auth/google/callback`
5. **For Facebook Social Login:**
   - Facebook Client ID: Paste your Facebook App ID
   - Facebook Client Secret: Paste your Facebook App Secret
   - Callback URL is displayed: `{{APP_URL}}/auth/facebook/callback`
6. Click "Save OAuth Settings"

> **Important**: Do NOT use Section B (Purple background) - that's for API integrations (Campaign Generator, SEO Tools), NOT for Social Login!

---

## Testing (5 minutes)

### 1. Check Login Page

Go to: `http://yourdomain.com/login`

Should see:
- "Or continue with" divider
- Google button
- Facebook button

### 2. Test Google Login

1. Click "Google" button
2. Select Google account
3. Allow permissions
4. Should redirect to `/dashboard`
5. Success message appears

### 3. Test Facebook Login

1. Logout
2. Click "Facebook" button
3. Login with Facebook
4. Allow permissions
5. Should redirect to `/dashboard`

### 4. Verify Database

```sql
SELECT id, name, email, social_provider, social_id
FROM users
WHERE social_provider IS NOT NULL
ORDER BY created_at DESC
LIMIT 5;
```

---

## Troubleshooting

### Buttons not showing

```bash
php artisan config:clear
php artisan cache:clear
```

Check database:
```sql
SELECT google_console_client_id, facebook_app_id FROM platform_settings;
```

### Error 400: redirect_uri_mismatch

- Verify `APP_URL` in `.env` matches exactly
- Check Google/Facebook console redirect URIs match
- Protocol (http/https) must match
- No trailing slashes

### "App Not Set Up" (Facebook)

- Add yourself as test user: Roles > Test Users
- OR switch app to Live mode (production only)

### "Invalid State" exception

- Clear browser cookies
- Clear application cache
- Verify session driver is working

---

## Common Mistakes

1. **Trailing slash in APP_URL** - ❌ Don't use: `http://localhost:8000/`
2. **Wrong protocol** - ❌ Mixed http/https
3. **Not a test user** - ❌ Facebook app in dev mode but user not added
4. **Forgot to clear cache** - ❌ After changing .env, always clear cache
5. **Wrong OAuth type** - ❌ Using Google Ads credentials for social login

---

## Security Checklist

- ✓ Never commit `.env` to Git
- ✓ Use HTTPS in production
- ✓ Rotate secrets every 6-12 months
- ✓ Remove test URLs before going live
- ✓ Enable rate limiting on OAuth endpoints
- ✓ Monitor failed login attempts
- ✓ Keep Laravel Socialite package updated

---

## Need More Details?

See the complete guide: **[SOCIAL_LOGIN_SETUP_GUIDE.md](./SOCIAL_LOGIN_SETUP_GUIDE.md)**

Includes:
- Detailed step-by-step with screenshots descriptions
- Complete troubleshooting section
- Security best practices
- API endpoint documentation
- FAQ section

---

## Quick Commands Reference

```bash
# Clear caches
php artisan config:clear && php artisan cache:clear

# Check routes
php artisan route:list | grep social

# Check config
php artisan tinker
>>> config('services.google.client_id')
>>> \App\Models\PlatformSetting::first()

# View logs
tail -f storage/logs/laravel.log
```

---

---

## 📝 What Changed (October 13, 2025)

**Critical OAuth Configuration Fix:**
- Admin interface split into TWO visual sections (Blue/Purple)
- Fixed field names: Now uses `google_client_id`, `facebook_client_id` for Social Login
- Callback URLs now displayed directly in admin panel
- Clear separation prevents configuration errors

**Migration added:** `2025_10_13_164310_add_oauth_api_integration_fields_to_platform_settings.php`

**Files updated:**
- `config/services.php` (FIXED field name lookups)
- `app/Http/Controllers/Admin/PlatformSettingsController.php`
- `app/Models/PlatformSetting.php`
- `resources/views/admin/settings/index.blade.php`

---

Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
Last Updated: October 13, 2025 - Critical OAuth Fix Applied
