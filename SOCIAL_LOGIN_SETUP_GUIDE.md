# Social Login Setup Guide - Complete OAuth Configuration

**Version:** 1.0
**Last Updated:** October 2025
**Estimated Total Setup Time:** 45-60 minutes (Google: 20-30 min, Facebook: 25-30 min)

---

## Table of Contents

1. [Overview](#overview)
2. [Google OAuth Setup](#google-oauth-setup)
3. [Facebook OAuth Setup](#facebook-oauth-setup)
4. [Environment Configuration](#environment-configuration)
5. [Admin Dashboard Configuration](#admin-dashboard-configuration)
6. [Testing Guide](#testing-guide)
7. [Troubleshooting](#troubleshooting)
8. [Security Best Practices](#security-best-practices)

---

## Overview

This guide walks you through setting up social login (OAuth 2.0) for Google and Facebook in your Ainstein platform. After completing this setup, users will be able to register and log in using their Google or Facebook accounts.

### What You'll Need

- Access to Google Cloud Console (Google account required)
- Access to Facebook Developer Portal (Facebook account required)
- Admin access to your Ainstein platform
- Your application's public URL (e.g., `https://yourdomain.com` or `http://localhost:8000` for local development)

### Important Distinction

**This guide covers Social Login ONLY** (allowing users to sign in with Google/Facebook). This is different from:
- **Google Ads API**: Used by Campaign Generator to manage Google Ads campaigns
- **Facebook Ads API**: Used by Campaign Generator to manage Facebook Ads campaigns
- **Google Search Console API**: Used by SEO Tools for search analytics

These are configured separately in the Admin Dashboard under "OAuth Integrations".

---

## Google OAuth Setup

**Estimated Time:** 20-30 minutes

### Step 1: Access Google Cloud Console

1. Navigate to [Google Cloud Console](https://console.cloud.google.com/)
2. Sign in with your Google account
3. If you don't have a project yet, you'll be prompted to create one

### Step 2: Create or Select a Project

1. Click on the project dropdown in the top navigation bar (next to "Google Cloud")
2. Click **"NEW PROJECT"** button
3. Enter project details:
   - **Project Name**: `Ainstein Social Login` (or your preferred name)
   - **Organization**: Leave as default (if applicable)
   - **Location**: Leave as default or select your organization
4. Click **"CREATE"**
5. Wait for the project to be created (takes 10-20 seconds)
6. Select your newly created project from the project dropdown

### Step 3: Enable Required APIs

1. In the left sidebar, click on **"APIs & Services" > "Library"**
2. In the search bar, type **"Google+ API"** (or search for "People API")
3. Click on **"Google+ API"** or **"Google People API"**
4. Click the **"ENABLE"** button
5. Wait for the API to be enabled (takes a few seconds)

> **Note**: The Google+ API is deprecated but still works for basic profile info. Alternatively, you can enable the "Google People API" which provides the same functionality.

### Step 4: Configure OAuth Consent Screen

1. In the left sidebar, go to **"APIs & Services" > "OAuth consent screen"**
2. Select **User Type**:
   - **Internal**: Only for Google Workspace users (skip if you don't have Workspace)
   - **External**: For all Google users (recommended)
3. Click **"CREATE"**

#### OAuth Consent Screen Configuration

**App Information:**
- **App name**: `Ainstein Platform` (or your platform name)
- **User support email**: Select your email from dropdown
- **App logo**: (Optional) Upload your logo (120x120px PNG or JPG)

**App domain (Optional but recommended):**
- **Application home page**: `https://yourdomain.com`
- **Application privacy policy link**: `https://yourdomain.com/privacy`
- **Application terms of service link**: `https://yourdomain.com/terms`

**Authorized domains:**
- Add your domain: `yourdomain.com` (without https:// or www)
- For local development: `localhost` (if testing locally)

**Developer contact information:**
- Enter your email address

4. Click **"SAVE AND CONTINUE"**

### Step 5: Configure Scopes

1. On the "Scopes" page, click **"ADD OR REMOVE SCOPES"**
2. Select the following scopes (use the filter to search):
   - ✓ `.../auth/userinfo.email` - View your email address
   - ✓ `.../auth/userinfo.profile` - See your personal info
   - ✓ `openid` - Associate you with your personal info on Google

3. Scroll down and click **"UPDATE"**
4. Click **"SAVE AND CONTINUE"**

### Step 6: Add Test Users (For Development)

If you selected "External" user type, your app will be in testing mode by default:

1. On the "Test users" page, click **"ADD USERS"**
2. Enter email addresses of users who should be able to test login (including your own)
3. Click **"ADD"**
4. Click **"SAVE AND CONTINUE"**

> **Important**: While in testing mode, only added test users can log in. To make it public, you'll need to submit your app for verification (can be done later).

### Step 7: Create OAuth 2.0 Credentials

1. In the left sidebar, go to **"APIs & Services" > "Credentials"**
2. Click **"CREATE CREDENTIALS"** (top button)
3. Select **"OAuth client ID"**

#### OAuth Client Configuration

**Application type:** Select **"Web application"**

**Name:** `Ainstein Web Client` (or your preferred name)

**Authorized JavaScript origins:**
- Click **"ADD URI"**
- For production: `https://yourdomain.com`
- For local dev: `http://localhost:8000`
- For Laragon: `http://ainstein-3.test` (if using .test domain)

**Authorized redirect URIs:**
- Click **"ADD URI"**
- For production: `https://yourdomain.com/auth/google/callback`
- For local dev: `http://localhost:8000/auth/google/callback`
- For Laragon: `http://ainstein-3.test/auth/google/callback`

> **Important**: The callback URL must EXACTLY match your APP_URL + `/auth/google/callback`

4. Click **"CREATE"**

### Step 8: Save Your Credentials

A popup will appear with your credentials:

```
Client ID: 123456789012-abcdefghijklmnopqrstuvwxyz123456.apps.googleusercontent.com
Client Secret: GOCSPX-aBcDeFgHiJkLmNoPqRsTuVwXyZ
```

1. **Copy the Client ID** - You'll need this for configuration
2. **Copy the Client Secret** - You'll need this for configuration
3. Click **"OK"**

> **Security Note**: Treat the Client Secret like a password. Never commit it to public repositories.

### Where to Find Your Credentials Later

If you need to retrieve your credentials later:
1. Go to **"APIs & Services" > "Credentials"**
2. Find your OAuth 2.0 Client ID in the list
3. Click the name to view details
4. Client ID is visible, Client Secret can be viewed by clicking the eye icon

---

## Facebook OAuth Setup

**Estimated Time:** 25-30 minutes

### Step 1: Access Facebook Developers

1. Navigate to [Facebook Developers](https://developers.facebook.com/)
2. Sign in with your Facebook account
3. If this is your first time, you may need to register as a developer:
   - Click **"Get Started"**
   - Complete the registration process
   - Verify your email and/or phone number

### Step 2: Create a New App

1. Click **"My Apps"** in the top navigation
2. Click **"Create App"** button
3. Select a use case:
   - Choose **"Authenticate and request data from users with Facebook Login"**
   - Or select **"Other"** if you don't see the above option
4. Click **"Next"**

### Step 3: Configure App Details

**App Type:** Select **"Consumer"** (for user authentication)

**App Information:**
- **App name**: `Ainstein Platform` (or your platform name)
- **App contact email**: Your email address
- **Business account** (optional): Skip unless you have a Facebook Business account

5. Click **"Create App"**
6. You may be asked to verify your account (password or security check)

### Step 4: Add Facebook Login Product

1. On your app dashboard, look for **"Add products to your app"** section
2. Find **"Facebook Login"** product
3. Click **"Set up"** button

**Facebook Login Type:**
- Select **"Web"** as the platform
- Click **"Next"**

### Step 5: Configure Facebook Login Settings

1. In the left sidebar, go to **"Facebook Login" > "Settings"**

#### Client OAuth Settings

**Valid OAuth Redirect URIs:**
Enter your callback URLs (one per line):
```
https://yourdomain.com/auth/facebook/callback
http://localhost:8000/auth/facebook/callback
http://ainstein-3.test/auth/facebook/callback
```

> **Important**: Each URL must be on a new line and must EXACTLY match your APP_URL + `/auth/facebook/callback`

**Login from Devices:** Leave unchecked (not needed for web)

**Enforce HTTPS:** Enable for production (can disable for local testing)

**Valid OAuth Redirect URI Patterns:** Leave empty (not needed)

**Allowed Domains for the JS SDK:**
```
yourdomain.com
localhost
ainstein-3.test
```

**Deauthorize Callback URL:** (Optional) Leave empty for now

**Data Deletion Request URL:** (Optional) Leave empty for now

2. Click **"Save Changes"** at the bottom

### Step 6: Configure App Settings

1. In the left sidebar, go to **"Settings" > "Basic"**

#### App Information

**App Display Name:** `Ainstein Platform` (should already be set)

**App Contact Email:** Your email (should already be set)

**App Domains:** Add your domains (one per line):
```
yourdomain.com
localhost
```

**Privacy Policy URL:** `https://yourdomain.com/privacy` (required for going live)

**Terms of Service URL:** `https://yourdomain.com/terms` (optional but recommended)

**User Data Deletion:** Instructions or URL for users to request data deletion

**App Icon:** (Optional) Upload a 1024x1024px PNG icon

2. Scroll down to the **"App Secret"** section

### Step 7: Get Your App Credentials

On the **Settings > Basic** page:

1. **App ID**: Copy this number (e.g., `1234567890123456`)
2. **App Secret**:
   - Click **"Show"** button
   - Enter your Facebook password to reveal
   - Copy the secret (e.g., `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`)

```
App ID: 1234567890123456
App Secret: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

> **Security Note**: The App Secret is highly sensitive. Never share it or commit it to public repositories.

### Step 8: Configure Permissions

1. In the left sidebar, go to **"App Review" > "Permissions and Features"**
2. Verify these permissions are available (should be by default):
   - ✓ `public_profile` - Access public profile information
   - ✓ `email` - Access email address

> **Note**: These basic permissions don't require review. If you need additional permissions later, you'll need to submit for App Review.

### Step 9: App Mode Settings

Your app starts in **Development Mode**:
- Only test users (admins, developers, testers) can log in
- The app is NOT visible to the public

#### Add Test Users (Development Mode)

1. Go to **"Roles" > "Test Users"**
2. Click **"Add"** button
3. Configure test users:
   - Number of test users: 1-5
   - Set password: Enable (recommended)
   - Automatically install app: Enable
4. Click **"Create"**
5. You'll see test accounts created with login credentials

#### Switch to Live Mode (When Ready)

To make your app public:

1. Go to **"Settings" > "Basic"**
2. At the top, you'll see a toggle: **"App Mode"**
3. Before switching to Live:
   - Complete all required settings (Privacy Policy URL is mandatory)
   - Add a valid business verification (if required)
   - Ensure you've tested with test users
4. Toggle to **"Live"**
5. Confirm the switch

> **Warning**: Only switch to Live mode when your app is production-ready and you have all required policies in place.

### Where to Find Your Credentials Later

To retrieve your credentials:
1. Go to **"Settings" > "Basic"**
2. **App ID** is visible at the top
3. **App Secret** - Click "Show" and enter your Facebook password

---

## Environment Configuration

Now that you have your OAuth credentials from Google and Facebook, you need to configure them in your application.

### Important: Two Configuration Methods

The Ainstein platform supports TWO ways to configure OAuth credentials:

1. **Admin Dashboard** (Recommended) - Stored in database, can be changed without redeploying
2. **.env File** (Fallback) - Used if database settings are not configured

The app will use database settings first, falling back to .env if not found.

### Method 1: Using .env File (Local Development)

If you're setting up a local development environment or want fallback values:

1. Navigate to your project root: `C:\laragon\www\ainstein-3\ainstein-laravel`
2. Open the `.env` file in your text editor
3. Add or update these lines:

```bash
# Application URL (MUST be set correctly!)
APP_URL=http://localhost:8000

# Google OAuth (for Social Login)
GOOGLE_CLIENT_ID=123456789012-abcdefghijklmnopqrstuvwxyz123456.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-aBcDeFgHiJkLmNoPqRsTuVwXyZ

# Facebook OAuth (for Social Login)
FACEBOOK_CLIENT_ID=1234567890123456
FACEBOOK_CLIENT_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

4. Save the file

> **Critical**: The `APP_URL` must match exactly with the URLs you configured in Google and Facebook consoles.

#### Local vs Production URLs

**Local Development (Laragon):**
```bash
APP_URL=http://ainstein-3.test
# OR
APP_URL=http://localhost:8000
```

**Production:**
```bash
APP_URL=https://yourdomain.com
```

### Method 2: Using Admin Dashboard (Recommended for Production)

See the [Admin Dashboard Configuration](#admin-dashboard-configuration) section below.

### Clear Configuration Cache

After updating `.env`, clear the configuration cache:

```bash
cd C:\laragon\www\ainstein-3\ainstein-laravel
php artisan config:clear
php artisan cache:clear
```

---

## Admin Dashboard Configuration

The recommended way to configure OAuth settings is through the Admin Dashboard. This allows you to change settings without redeploying or editing .env files.

### Access Admin Settings

1. Log in to your Ainstein platform as an administrator
2. Navigate to **Admin Dashboard** (usually `/admin`)
3. Click on **"Settings"** or **"Platform Settings"**
4. Select the **"OAuth Integrations"** tab

### Configure Google Login

In the Admin Dashboard OAuth section:

**Note**: Currently, the OAuth Integrations tab shows "Google Ads OAuth" and "Google Search Console OAuth", which are for API integrations (Campaign Generator and SEO Tools), NOT for social login.

#### Current Implementation Status

As of now, the admin interface shows:
- ✓ Google Ads OAuth (for Campaign Generator)
- ✓ Google Search Console OAuth (for SEO Tools)
- ✓ Facebook OAuth (for Campaign Generator)

**The admin interface does NOT yet have separate fields for Social Login OAuth.**

#### Temporary Workaround

Until separate social login fields are added to the admin interface, you have two options:

**Option A: Use .env Configuration**
Configure Google and Facebook OAuth in your `.env` file as described in the [Environment Configuration](#environment-configuration) section.

**Option B: Use Google Console OAuth for Login**
The system is configured to fall back to `google_console_client_id` and `google_console_client_secret` for social login if the dedicated social login credentials are not set.

1. In the Admin Dashboard, go to **OAuth Integrations** tab
2. Under **"Google Search Console OAuth"** section:
   - **Client ID**: Paste your Google OAuth Client ID
   - **Client Secret**: Paste your Google OAuth Client Secret
3. Click **"Save OAuth Settings"**

**For Facebook:**
1. Under **"Facebook OAuth"** section:
   - **App ID**: Paste your Facebook App ID
   - **App Secret**: Paste your Facebook App Secret
2. Click **"Save OAuth Settings"**

> **Note**: This is a temporary workaround. The recommendation is to ask your developer to add dedicated "Social Login" fields to the admin interface, separate from the API integration fields.

#### Future Enhancement Recommendation

For clarity and proper separation, consider adding these fields to the admin interface:

**Social Login Providers Section:**
- Google Login OAuth
  - Client ID
  - Client Secret
- Facebook Login OAuth
  - App ID
  - App Secret

This would be separate from the existing API integration fields.

### How the Configuration Works

The system checks credentials in this order:

**For Google:**
1. Database: `google_client_id` and `google_client_secret` (dedicated social login fields)
2. Database: `google_console_client_id` and `google_console_client_secret` (fallback)
3. .env: `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` (final fallback)

**For Facebook:**
1. Database: `facebook_client_id` and `facebook_client_secret` (dedicated social login fields)
2. Database: `facebook_app_id` and `facebook_app_secret` (fallback)
3. .env: `FACEBOOK_CLIENT_ID` and `FACEBOOK_CLIENT_SECRET` (final fallback)

You can verify this in: `C:\laragon\www\ainstein-3\ainstein-laravel\config\services.php`

### Verify Configuration

After saving settings in the Admin Dashboard:

1. Log out of your admin account
2. Go to the login page
3. You should see "Continue with Google" and "Continue with Facebook" buttons below the login form
4. If you don't see the buttons, check that:
   - Credentials are saved correctly in Admin Dashboard
   - Configuration cache is cleared: `php artisan config:clear`

---

## Testing Guide

### Pre-Testing Checklist

Before testing, verify:

- ✓ Google OAuth credentials are configured (via .env or Admin Dashboard)
- ✓ Facebook OAuth credentials are configured (via .env or Admin Dashboard)
- ✓ APP_URL in .env matches your current domain
- ✓ OAuth redirect URLs in Google/Facebook match your APP_URL
- ✓ Configuration cache is cleared (`php artisan config:clear`)
- ✓ You're added as a test user (if apps are in development mode)

### Test 1: Verify Social Login Buttons Appear

1. Navigate to your login page: `http://yourdomain.com/login`
2. Below the email/password form, you should see:
   - "Or continue with" divider
   - "Google" button (with Google icon)
   - "Facebook" button (with Facebook icon)

**If buttons don't appear:**
- Check that credentials are configured (settings table in database or .env)
- Clear config cache: `php artisan config:clear`
- Check browser console for JavaScript errors
- Verify the helper function `platform_setting()` is working

### Test 2: Google Login Flow

1. Click the **"Google"** button
2. You should be redirected to Google's consent screen
3. **Expected flow:**
   - Select or enter your Google account
   - Review permissions requested (email and profile)
   - Click "Continue" or "Allow"
   - You'll be redirected back to your app
4. **Successful login:**
   - You should be redirected to `/dashboard`
   - A success message appears: "Welcome back!" (if existing user) or "Account created successfully! Welcome to Ainstein." (if new user)
   - Your account is logged in

5. **Verify in Database:**
   - Check the `users` table
   - Find your user record by email
   - Verify these fields are populated:
     - `email`: Your Google email
     - `name`: Your Google display name
     - `email_verified_at`: Timestamp (auto-verified)
     - `social_provider`: "google"
     - `social_id`: Your Google user ID
     - `social_avatar`: URL to your Google profile picture
   - A corresponding `tenant` record should exist with your user as owner

### Test 3: Facebook Login Flow

1. Log out from your current session
2. Go to login page
3. Click the **"Facebook"** button
4. **Expected flow:**
   - Facebook login dialog appears
   - Enter Facebook credentials (or select existing account)
   - Review permissions (public profile and email)
   - Click "Continue as [Your Name]"
   - You'll be redirected back to your app
5. **Successful login:**
   - Redirected to `/dashboard`
   - Success message appears
   - Account is logged in

6. **Verify in Database:**
   - Check the `users` table
   - Find your user by Facebook email
   - Verify fields:
     - `social_provider`: "facebook"
     - `social_id`: Your Facebook user ID
     - `social_avatar`: URL to Facebook profile picture

### Test 4: Email Conflict Handling

Test what happens when a user tries to log in with Google/Facebook using an email that already exists:

1. Create a regular account: `testuser@example.com` (via email/password registration)
2. Log out
3. Try to log in with Google using the same email: `testuser@example.com`
4. **Expected behavior:**
   - Login succeeds
   - The existing user account is updated with Google OAuth info
   - `social_provider` is updated to "google"
   - `social_id` and `social_avatar` are added
   - User can now log in with EITHER email/password OR Google

> **Important**: This is the current behavior. Email is used as the unique identifier, so one email = one account, regardless of registration method.

### Test 5: New User Registration

1. Use a brand new email address that doesn't exist in your system
2. Log in with Google or Facebook using this new email
3. **Expected behavior:**
   - A new user account is created automatically
   - A new tenant workspace is created (e.g., "John's Workspace")
   - User is logged in immediately
   - User receives a welcome email (if email service is configured)
   - User is redirected to `/dashboard`

### Test 6: API Endpoints (Optional)

If you're using the API for social login (for a mobile app or SPA):

**Get OAuth Redirect URL:**
```bash
GET /api/auth/google
GET /api/auth/facebook
```

Response:
```json
{
  "redirect_url": "https://accounts.google.com/o/oauth2/v2/auth?...",
  "provider": "google"
}
```

**Handle Callback (after user authorizes):**
```bash
POST /api/auth/google/callback
POST /api/auth/facebook/callback
```

Response:
```json
{
  "message": "Login successful",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "tenant": {
      "id": 1,
      "name": "John's Workspace",
      "plan_type": "free"
    }
  }
}
```

---

## Troubleshooting

### Issue 1: "Error 400: redirect_uri_mismatch" (Google)

**Error Message:**
```
Error 400: redirect_uri_mismatch
The redirect URI in the request: http://yourdomain.com/auth/google/callback
does not match the ones authorized for the OAuth client.
```

**Cause:** The callback URL in your app doesn't match what's configured in Google Cloud Console.

**Solution:**
1. Check your `APP_URL` in `.env` - should be exactly: `http://yourdomain.com` (no trailing slash)
2. Go to Google Cloud Console > Credentials > Your OAuth Client
3. Under "Authorized redirect URIs", verify you have: `http://yourdomain.com/auth/google/callback`
4. The protocol (http vs https), domain, and path must match EXACTLY
5. After fixing, clear config cache: `php artisan config:clear`

### Issue 2: "App Not Set Up" (Facebook)

**Error Message:**
```
App Not Set Up: This app is still in development mode and you don't have access to it.
```

**Cause:** You're not added as a test user, and the app is in Development Mode.

**Solution:**
1. Go to Facebook Developers > Your App > Roles > Test Users
2. Add your Facebook account as a test user
3. OR switch the app to Live Mode (if ready for production)

### Issue 3: "redirect_uri_mismatch" (Facebook)

**Error Message:**
```
Can't Load URL: The domain of this URL isn't included in the app's domains.
```

**Cause:** The callback URL or domain isn't properly configured in Facebook settings.

**Solution:**
1. Go to Facebook Developers > Your App > Facebook Login > Settings
2. Under "Valid OAuth Redirect URIs", verify: `http://yourdomain.com/auth/facebook/callback`
3. Go to Settings > Basic > App Domains
4. Add your domain: `yourdomain.com` (without http://)
5. Save all changes

### Issue 4: Social Login Buttons Not Appearing

**Symptoms:** Login page loads but no Google/Facebook buttons are visible.

**Causes & Solutions:**

**A. Credentials Not Configured**
- Check if OAuth credentials are set (Admin Dashboard or .env)
- Verify in database: `SELECT * FROM platform_settings;`
- Check columns: `google_console_client_id`, `facebook_app_id`

**B. Configuration Cache**
- Clear cache: `php artisan config:clear && php artisan cache:clear`

**C. Helper Function Issue**
- Check if `platform_setting()` helper exists
- Verify in blade template: `C:\laragon\www\ainstein-3\ainstein-laravel\resources\views\auth\login.blade.php`
- Look for: `@if(platform_setting('google_console_client_id'))`

**D. CSS Display Issue**
- Check browser console for errors
- Verify Tailwind CSS is loading
- Inspect element to see if buttons are rendered but hidden

### Issue 5: "Authentication service temporarily unavailable"

**Symptoms:** Error message on login page after clicking Google/Facebook button.

**Causes & Solutions:**

**A. Socialite Not Installed**
```bash
cd C:\laragon\www\ainstein-3\ainstein-laravel
composer require laravel/socialite
```

**B. Invalid Credentials**
- Check Laravel logs: `storage/logs/laravel.log`
- Look for detailed error messages
- Verify Client ID and Secret are correct

**C. API Not Enabled (Google)**
- Go to Google Cloud Console > APIs & Services > Library
- Enable "Google+ API" or "People API"

### Issue 6: "Invalid State" Exception

**Error Message:** `Laravel\Socialite\Two\InvalidStateException`

**Cause:** Session mismatch, often due to:
- Session cookies not working
- Testing from different domain/protocol
- Multiple login attempts without completing

**Solution:**
1. Clear browser cookies for your domain
2. Clear application cache: `php artisan cache:clear`
3. Verify session driver in `.env`: `SESSION_DRIVER=file` (or `database`, `redis`)
4. Ensure cookies are working (same domain for app and OAuth redirect)
5. For HTTPS sites, check `SESSION_SECURE_COOKIE` setting

### Issue 7: User Created but Email Not Sent

**Symptoms:** User successfully registers but no welcome email received.

**Cause:** Email service (SMTP) not configured.

**Solution:**
1. This is NOT an OAuth issue - social login is working correctly
2. Configure email settings in Admin Dashboard > Email SMTP tab
3. Or set up SMTP in `.env`:
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Ainstein Platform"
```

### Issue 8: "This app hasn't been verified" (Google - Production)

**Warning Screen:**
```
This app hasn't been verified yet by Google.
Continue to [App Name] (unsafe)
```

**Cause:** Your app is still in testing mode or hasn't completed Google verification.

**For Development/Testing:**
- This is normal. Click "Advanced" > "Go to [App Name] (unsafe)"
- Only test users you added can do this

**For Production:**
1. Go to Google Cloud Console > OAuth consent screen
2. Click "PUBLISH APP" to remove testing status
3. For apps requesting sensitive scopes, submit for verification:
   - Click "Prepare for verification"
   - Complete verification questionnaire
   - Provide required documentation
   - Wait for Google approval (can take 1-2 weeks)

> **Note**: For basic scopes (email, profile), verification is not required, but you may still see the warning until you click "PUBLISH APP".

### Debugging Tips

**Enable Debug Mode (Local Dev Only):**

In `.env`:
```bash
APP_DEBUG=true
APP_ENV=local
```

**Check Logs:**
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# or on Windows
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

**Test OAuth Routes:**
```bash
php artisan route:list | grep social
```

Expected output:
```
GET|HEAD  auth/{provider} ........................... social.redirect
GET|HEAD  auth/{provider}/callback ................ social.callback
GET|HEAD  api/auth/{provider} ................. api.social.redirect
POST      api/auth/{provider}/callback ...... api.social.callback
```

**Verify Database:**
```sql
-- Check platform settings
SELECT google_console_client_id, facebook_app_id FROM platform_settings;

-- Check users table structure
DESCRIBE users;

-- Check recent social logins
SELECT id, name, email, social_provider, created_at
FROM users
WHERE social_provider IS NOT NULL
ORDER BY created_at DESC
LIMIT 10;
```

---

## Security Best Practices

### 1. Protect Your Credentials

**Never commit secrets to Git:**

Ensure `.env` is in your `.gitignore`:
```gitignore
.env
.env.backup
.env.production
```

**For team sharing, use .env.example:**
```bash
# .env.example (commit this)
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
FACEBOOK_CLIENT_ID=your_facebook_app_id_here
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret_here
```

### 2. Use HTTPS in Production

**Always use HTTPS for production OAuth:**
- Protects OAuth tokens in transit
- Required by some providers (Facebook enforces HTTPS)
- Prevents man-in-the-middle attacks

In production `.env`:
```bash
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

### 3. Validate Redirect URLs

**Be strict with OAuth redirect URLs:**
- Only add URLs you control
- Use exact URLs (no wildcards)
- Remove test URLs before going live
- Regularly audit authorized URLs

### 4. Limit Permissions

**Request only necessary scopes:**

For social login, you only need:
- Email address
- Basic profile info (name, avatar)

Don't request additional permissions unless absolutely necessary.

### 5. Implement Rate Limiting

Protect OAuth endpoints from abuse:

```php
// In routes/web.php (already implemented)
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::get('/{provider}', [SocialAuthController::class, 'redirectToProvider']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);
});
```

This limits OAuth attempts to 10 per minute per IP.

### 6. Rotate Secrets Regularly

**Best practices:**
- Rotate OAuth secrets every 6-12 months
- Rotate immediately if compromised
- Keep old secrets for 24 hours during rotation (for in-flight requests)

### 7. Monitor OAuth Activity

**Set up logging and monitoring:**

```php
// Already implemented in SocialAuthController
Log::info('New user created via social auth', [
    'user_id' => $user->id,
    'provider' => $provider,
    'email' => $user->email
]);
```

Monitor for:
- Unusual spike in social logins
- Failed authentication attempts
- OAuth errors

### 8. Handle Account Linking Carefully

**Current implementation:**
- One email = one account (email is unique identifier)
- Social login updates existing account if email matches
- User can have multiple providers linked (future enhancement)

**Security consideration:**
- Ensure email verification from OAuth provider
- Consider requiring email confirmation for account merging
- Implement account unlinking functionality

### 9. Secure Session Management

**Recommendations:**
```bash
# .env
SESSION_DRIVER=database  # or redis (more secure than file)
SESSION_LIFETIME=120     # 2 hours
SESSION_ENCRYPT=true     # Encrypt session data
```

### 10. Database Security

**The platform encrypts sensitive fields automatically:**

From `C:\laragon\www\ainstein-3\ainstein-laravel\app\Models\PlatformSetting.php`:
```php
protected $casts = [
    'google_client_secret' => 'encrypted',
    'facebook_client_secret' => 'encrypted',
    // ... other encrypted fields
];
```

**Ensure:**
- `APP_KEY` is set in `.env` (used for encryption)
- Database backups are encrypted
- Access to database is restricted

---

## Quick Reference

### Callback URLs by Environment

| Environment | Google Callback | Facebook Callback |
|------------|----------------|-------------------|
| **Local (Laragon)** | `http://ainstein-3.test/auth/google/callback` | `http://ainstein-3.test/auth/facebook/callback` |
| **Local (PHP Built-in)** | `http://localhost:8000/auth/google/callback` | `http://localhost:8000/auth/facebook/callback` |
| **Production** | `https://yourdomain.com/auth/google/callback` | `https://yourdomain.com/auth/facebook/callback` |

### Required Scopes

| Provider | Scope | Purpose |
|----------|-------|---------|
| **Google** | `userinfo.email` | Get user's email address |
| **Google** | `userinfo.profile` | Get user's name and avatar |
| **Google** | `openid` | OpenID Connect authentication |
| **Facebook** | `email` | Get user's email address |
| **Facebook** | `public_profile` | Get user's name and avatar |

### Configuration Locations

| Configuration | Location | Priority |
|--------------|----------|----------|
| **Admin Dashboard** | Database `platform_settings` table | 1st (Highest) |
| **.env File** | `C:\laragon\www\ainstein-3\ainstein-laravel\.env` | 2nd (Fallback) |
| **Config File** | `config/services.php` | Defines lookup logic |

### Useful Commands

```bash
# Clear configuration cache
php artisan config:clear

# Clear all caches
php artisan cache:clear

# View routes
php artisan route:list | grep social

# Check environment variables
php artisan tinker
>>> env('GOOGLE_CLIENT_ID')
>>> config('services.google.client_id')

# Check database settings
php artisan tinker
>>> \App\Models\PlatformSetting::first()
```

---

## Getting Help

### Support Resources

1. **Official Documentation:**
   - [Laravel Socialite Documentation](https://laravel.com/docs/10.x/socialite)
   - [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
   - [Facebook Login Documentation](https://developers.facebook.com/docs/facebook-login)

2. **Check Logs:**
   - Laravel: `storage/logs/laravel.log`
   - Apache: `C:\laragon\bin\apache\apache-2.4.54\logs\error.log`
   - PHP: `C:\laragon\bin\php\php-8.2.13\logs\error.log`

3. **Community Support:**
   - Laravel Forums: https://laracasts.com/discuss
   - Stack Overflow: Tag `laravel-socialite`

### Common Questions

**Q: Can users link multiple social accounts to one email?**
A: Currently, one email = one account. The system updates `social_provider` when a new social login is used. Future enhancement: Support multiple providers per account.

**Q: What happens if a user changes their email on Google/Facebook?**
A: Next login will create a new account with the new email, unless you implement email change detection and migration.

**Q: Can users unlink social accounts?**
A: Not yet implemented. Consider adding "Disconnect Google/Facebook" options in user account settings.

**Q: Do I need both Google Ads AND Google Login OAuth?**
A: **Yes, they are different:**
- **Google Login**: For users to sign in (this guide)
- **Google Ads API**: For Campaign Generator to create ads campaigns
- **Google Search Console**: For SEO Tools to access search data

Each requires separate OAuth credentials and serves different purposes.

**Q: Why are there multiple Google OAuth settings in the admin?**
A: The admin currently shows:
- Google Ads OAuth (for ads campaigns)
- Google Search Console OAuth (for SEO data)
- But NOT dedicated Social Login OAuth fields yet

Recommendation: Add separate "Social Login" section in admin for clarity.

---

## Changelog

**v1.0 - October 2025**
- Initial guide created
- Google OAuth setup documented
- Facebook OAuth setup documented
- Environment configuration explained
- Admin Dashboard configuration documented
- Testing procedures defined
- Troubleshooting section added
- Security best practices included

---

## License

This guide is part of the Ainstein Platform documentation.

---

**Need More Help?**

If you encounter issues not covered in this guide:
1. Check the [Troubleshooting](#troubleshooting) section
2. Review the [logs](#debugging-tips)
3. Consult your development team
4. Review the source code: `app/Http/Controllers/Auth/SocialAuthController.php`

---

Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
