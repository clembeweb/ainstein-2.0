# Social Login Testing Plan

## Overview

This document provides a comprehensive testing strategy for the Social Login functionality implemented in Ainstein. It covers automated tests, manual testing procedures, and verification steps.

---

## Table of Contents

1. [Test Scripts](#test-scripts)
2. [Automated Testing](#automated-testing)
3. [Manual Testing](#manual-testing)
4. [Database Verification](#database-verification)
5. [Error Scenarios](#error-scenarios)
6. [Performance Considerations](#performance-considerations)
7. [Security Checklist](#security-checklist)
8. [Troubleshooting Guide](#troubleshooting-guide)

---

## Test Scripts

### Available Test Scripts

1. **test-social-login.sh** - Automated verification script
   - Checks environment configuration
   - Verifies package dependencies
   - Validates routes and controllers
   - Tests database schema
   - No OAuth credentials required

2. **test-social-login-mock.php** - Mock OAuth test
   - Tests user creation logic
   - Verifies tenant auto-creation
   - Tests database operations
   - Validates model methods
   - Uses mock social user data

3. **PHPUnit Tests** - Unit and feature tests
   - Located in `tests/Feature/Auth/SocialAuthTest.php`
   - Uses Laravel's testing framework
   - Includes database rollback

### Running Test Scripts

```bash
# Run automated verification
bash test-social-login.sh

# Run mock OAuth tests
php test-social-login-mock.php

# Run PHPUnit tests
cd ainstein-laravel
php artisan test --filter SocialAuthTest
```

---

## Automated Testing

### 1. Environment Verification

**What it checks:**
- `.env` file exists
- OAuth credentials configured (optional for testing)
- Laravel Socialite installed
- Database connection working

**Expected Results:**
```
[PASS] .env file exists
[WARN] Google Client ID configured (optional)
[PASS] Laravel Socialite installed
[PASS] Database connection working
```

### 2. Code Structure Verification

**What it checks:**
- `SocialAuthController` exists
- Required methods present:
  - `redirectToProvider()`
  - `handleProviderCallback()`
  - `createUserFromSocial()`
  - `updateUserSocialInfo()`
- Routes registered in `web.php` and `api.php`
- User model has social fields

**Expected Results:**
```
[PASS] SocialAuthController exists
[PASS] redirectToProvider method exists
[PASS] handleProviderCallback method exists
[PASS] Social routes registered
```

### 3. Database Schema Verification

**What it checks:**
- Migration file exists
- Social columns added to users table:
  - `social_provider` (nullable string)
  - `social_id` (nullable string)
  - `social_avatar` (nullable text)
- Migration applied to database

**Expected Results:**
```
[PASS] Social auth migration exists
[PASS] social_provider column in migration
[PASS] social_id column in migration
[PASS] Migration applied to database
```

---

## Manual Testing

### Google OAuth Flow

#### Prerequisites
1. Configure Google OAuth credentials in `.env`:
   ```env
   GOOGLE_CLIENT_ID=your-client-id-here
   GOOGLE_CLIENT_SECRET=your-client-secret-here
   GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
   ```

2. Add credentials to Google Cloud Console:
   - Project: Your Ainstein Project
   - Authorized redirect URIs: Match `GOOGLE_REDIRECT_URI`
   - Scopes: email, profile

#### Test Steps

**Test 1: New User Registration via Google**

1. Clear browser cookies/cache
2. Navigate to login page
3. Click "Sign in with Google"
4. Select Google account (not previously used)
5. Approve permissions

**Expected Results:**
- Redirected to Google OAuth consent screen
- After approval, redirected to `/dashboard`
- Success message: "Account created successfully! Welcome to Ainstein."
- User record created in database
- Tenant auto-created
- Welcome email sent (check logs if email service configured)

**Verify in Database:**
```sql
SELECT id, name, email, social_provider, social_id, tenant_id
FROM users
WHERE email = 'your-test-email@gmail.com';

SELECT id, name, slug, plan_type, status
FROM tenants
WHERE id = (SELECT tenant_id FROM users WHERE email = 'your-test-email@gmail.com');
```

**Expected Database State:**
- User exists with `social_provider = 'google'`
- `social_id` matches Google user ID
- `social_avatar` contains Google profile picture URL
- `email_verified_at` is set
- `role = 'owner'`
- `is_active = true`
- Tenant exists with `plan_type = 'free'` and `status = 'active'`

---

**Test 2: Existing User Login via Google**

1. Log out from dashboard
2. Navigate to login page
3. Click "Sign in with Google"
4. Select same Google account used in Test 1

**Expected Results:**
- Redirected to Google (may skip consent if already approved)
- Redirected to `/dashboard`
- Success message: "Welcome back!"
- No new user or tenant created
- `last_login` timestamp updated

**Verify in Database:**
```sql
SELECT id, name, email, last_login
FROM users
WHERE email = 'your-test-email@gmail.com';
```

**Expected Database State:**
- Same user ID as Test 1
- `last_login` updated to current timestamp
- No duplicate user records

---

**Test 3: Link Google to Existing Email Account**

1. Create a user via normal registration with email `existing@example.com`
2. Log out
3. Click "Sign in with Google"
4. Sign in with Google account using same email `existing@example.com`

**Expected Results:**
- User authenticated successfully
- Social provider info added to existing account
- No new user or tenant created
- Redirected to dashboard

**Verify in Database:**
```sql
SELECT id, email, social_provider, social_id, created_at
FROM users
WHERE email = 'existing@example.com';
```

**Expected Database State:**
- Same user ID and `created_at` timestamp
- `social_provider` now set to 'google'
- `social_id` and `social_avatar` populated

---

### Facebook OAuth Flow

#### Prerequisites
1. Configure Facebook OAuth credentials in `.env`:
   ```env
   FACEBOOK_CLIENT_ID=your-facebook-app-id
   FACEBOOK_CLIENT_SECRET=your-facebook-app-secret
   FACEBOOK_REDIRECT_URI=http://localhost/auth/facebook/callback
   ```

2. Configure Facebook App:
   - Valid OAuth Redirect URIs: Match `FACEBOOK_REDIRECT_URI`
   - App Mode: Set to "Live" for production

#### Test Steps

**Test 4: New User Registration via Facebook**

Follow same steps as Google Test 1, but use Facebook login button.

**Expected Results:**
- Similar to Google flow
- `social_provider = 'facebook'`
- `social_id` matches Facebook user ID
- Facebook profile picture in `social_avatar`

---

**Test 5: Existing User Login via Facebook**

Follow same steps as Google Test 2, but use Facebook.

**Expected Results:**
- Same user authenticated
- No duplicate records

---

### API Endpoint Testing

#### Test API Social Auth Redirect

```bash
# Test Google redirect
curl -X GET http://localhost/api/v1/auth/social/google

# Expected Response:
{
  "redirect_url": "https://accounts.google.com/o/oauth2/auth?...",
  "provider": "google"
}
```

#### Test API Social Auth Callback

```bash
# Simulate callback (requires valid OAuth code)
curl -X POST http://localhost/api/v1/auth/social/google/callback \
  -H "Content-Type: application/json" \
  -d '{"code": "valid-oauth-code"}'

# Expected Response:
{
  "message": "Account created successfully",
  "token": "1|abc123...",
  "user": {
    "id": "01HQXYZ...",
    "name": "John Doe",
    "email": "john@example.com",
    "tenant": {
      "id": "01HQXYZ...",
      "name": "John's Workspace",
      "plan_type": "free"
    }
  }
}
```

---

## Database Verification

### SQL Queries for Verification

**1. Check all social login users:**
```sql
SELECT
    id,
    name,
    email,
    social_provider,
    social_id,
    created_at,
    last_login
FROM users
WHERE social_provider IS NOT NULL
ORDER BY created_at DESC;
```

**2. Verify tenant creation for social users:**
```sql
SELECT
    u.id as user_id,
    u.name as user_name,
    u.email,
    u.social_provider,
    t.id as tenant_id,
    t.name as tenant_name,
    t.slug,
    t.plan_type
FROM users u
INNER JOIN tenants t ON u.tenant_id = t.id
WHERE u.social_provider IS NOT NULL;
```

**3. Check for duplicate emails:**
```sql
SELECT email, COUNT(*) as count
FROM users
GROUP BY email
HAVING count > 1;
```

**Expected Result:** No rows (no duplicates)

**4. Verify email verification for social users:**
```sql
SELECT
    email,
    social_provider,
    email_verified_at,
    CASE
        WHEN email_verified_at IS NULL THEN 'NOT VERIFIED'
        ELSE 'VERIFIED'
    END as status
FROM users
WHERE social_provider IS NOT NULL;
```

**Expected Result:** All social users should have `email_verified_at` set.

**5. Check tenant ownership:**
```sql
SELECT
    t.name as tenant_name,
    COUNT(u.id) as user_count,
    SUM(CASE WHEN u.role = 'owner' THEN 1 ELSE 0 END) as owner_count
FROM tenants t
LEFT JOIN users u ON t.id = u.tenant_id
GROUP BY t.id, t.name
HAVING owner_count = 0 OR owner_count > 1;
```

**Expected Result:** No rows (each tenant should have exactly one owner)

---

## Error Scenarios

### Test Error Handling

#### 1. Invalid State Exception

**Simulate:**
- Start OAuth flow
- Clear session/cookies before callback
- Complete OAuth flow

**Expected Behavior:**
- Redirected to home with error message
- Error logged: "Invalid state exception for {provider}"
- User not created

**Verify in Logs:**
```bash
cd ainstein-laravel
tail -f storage/logs/laravel.log | grep "Invalid state"
```

---

#### 2. OAuth Denied by User

**Simulate:**
- Click "Sign in with Google/Facebook"
- Click "Deny" or "Cancel" on OAuth consent screen

**Expected Behavior:**
- Redirected to home with error message: "Authentication failed. Please try again."
- No user created
- Error logged

---

#### 3. Missing OAuth Credentials

**Simulate:**
- Remove or comment out OAuth credentials in `.env`
- Try social login

**Expected Behavior:**
- Error message: "Authentication service temporarily unavailable"
- Logged: "Social auth redirect error for {provider}"

---

#### 4. Invalid Provider

**Test:**
```bash
curl http://localhost/auth/twitter
```

**Expected Response:**
- Redirect to home with error: "Provider not supported"

---

#### 5. Database Connection Lost

**Simulate:**
- Stop database service
- Try social login (if possible to intercept at callback)

**Expected Behavior:**
- Transaction rolled back
- Error logged: "Failed to create user from social auth"
- Generic error shown to user
- No partial data in database

---

## Performance Considerations

### 1. OAuth Redirect Time

**Measure:**
- Time from clicking "Sign in with Google/Facebook" to OAuth consent screen
- Should be < 2 seconds

**Bottlenecks:**
- Network latency to OAuth provider
- Laravel application startup

---

### 2. Callback Processing Time

**Measure:**
- Time from OAuth approval to dashboard redirect
- Should be < 3 seconds

**What happens during callback:**
1. Validate OAuth state
2. Exchange code for access token
3. Fetch user info from provider
4. Database operations:
   - Check existing user
   - Create tenant (if new user)
   - Create user
   - Update social info (if existing)
5. Send welcome email (async recommended)
6. Create session
7. Redirect

**Optimization Tips:**
- Use database indexes on `email` field
- Queue welcome emails instead of sending synchronously
- Cache OAuth provider configuration

---

### 3. Database Query Performance

**Queries to monitor:**

```sql
-- Query 1: Find user by email (runs on every social login)
SELECT * FROM users WHERE email = ?

-- Query 2: Check slug uniqueness (runs when creating tenant)
SELECT COUNT(*) FROM tenants WHERE slug = ?

-- Query 3: Create user and tenant (transaction)
INSERT INTO tenants (...) VALUES (...);
INSERT INTO users (...) VALUES (...);
```

**Ensure indexes exist:**
```sql
-- Check indexes
SHOW INDEXES FROM users;
SHOW INDEXES FROM tenants;

-- Expected indexes
-- users.email (unique)
-- tenants.slug (unique)
```

---

### 4. Load Testing

**Simulate concurrent social logins:**

```bash
# Install Apache Bench
apt-get install apache2-utils

# Test 100 requests, 10 concurrent
ab -n 100 -c 10 http://localhost/auth/google
```

**Monitor:**
- Response times
- Database connections
- Memory usage
- Error rate

---

## Security Checklist

### OAuth Configuration

- [ ] OAuth credentials stored in `.env`, not version controlled
- [ ] Redirect URIs match exactly in `.env` and provider console
- [ ] HTTPS used in production (not http)
- [ ] State parameter validated (Laravel Socialite handles this)
- [ ] CSRF protection enabled (Laravel default)

### User Data

- [ ] Email addresses validated
- [ ] Social IDs stored as strings (can be large)
- [ ] No sensitive OAuth tokens stored in database
- [ ] Password field still required (bcrypt random for social users)
- [ ] User activation status checked before login

### Database

- [ ] Transactions used for user/tenant creation
- [ ] Foreign key constraints in place
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] No plain text passwords

### Session Management

- [ ] Session regenerated after login
- [ ] HTTPS-only cookies in production
- [ ] Session timeout configured
- [ ] Logout revokes tokens

### Rate Limiting

- [ ] Rate limit on OAuth endpoints
- [ ] Prevent rapid-fire login attempts
- [ ] Monitor for unusual patterns

### Logging

- [ ] Failed login attempts logged
- [ ] OAuth errors logged (without tokens)
- [ ] User creation logged
- [ ] No sensitive data in logs

---

## Troubleshooting Guide

### Problem: "Authentication service temporarily unavailable"

**Possible Causes:**
1. OAuth credentials missing or incorrect
2. Laravel Socialite not installed
3. Network issue reaching OAuth provider

**Solutions:**
```bash
# Check credentials
cd ainstein-laravel
php artisan tinker
>>> config('services.google.client_id')
>>> config('services.google.client_secret')

# Reinstall Socialite
composer require laravel/socialite

# Check logs
tail -f storage/logs/laravel.log
```

---

### Problem: "Invalid state exception"

**Possible Causes:**
1. Session expired during OAuth flow
2. Different domain/port in callback
3. Browser blocking cookies

**Solutions:**
- Clear browser cache/cookies
- Ensure `SESSION_DRIVER` is not 'array' in production
- Check `APP_URL` matches actual URL
- Verify `SESSION_DOMAIN` in config/session.php

---

### Problem: User created but not logged in

**Check:**
1. User `is_active` field
2. Email verification requirement
3. Session configuration

**Debug:**
```php
// In handleProviderCallback()
Log::info('User before login', ['user' => $user->toArray()]);
Auth::login($user);
Log::info('User after login', ['authenticated' => Auth::check()]);
```

---

### Problem: Duplicate users created

**Check:**
1. Email comparison is case-insensitive
2. Transaction rollback on errors
3. Race condition in concurrent logins

**Fix:**
```php
// In SocialAuthController, use locking
$existingUser = User::where('email', $socialUser->getEmail())
    ->lockForUpdate()
    ->first();
```

---

### Problem: Welcome email not sent

**Check:**
1. Email service configured
2. `EmailService` exists and has `sendWelcomeEmail()` method
3. Queue workers running (if using queues)
4. Mail logs

**Debug:**
```bash
# Check mail configuration
php artisan tinker
>>> config('mail.mailers')

# Check queue
php artisan queue:work --once

# Check logs
tail -f storage/logs/laravel.log | grep -i email
```

---

### Problem: Tenant not created

**Check:**
1. Transaction not committed
2. Validation errors on tenant fields
3. Database foreign key constraints

**Debug:**
```bash
# Check recent tenants
php artisan tinker
>>> \App\Models\Tenant::latest()->take(5)->get(['id', 'name', 'created_at']);

# Check for errors
tail -f storage/logs/laravel.log | grep -i tenant
```

---

## Testing Checklist

### Before Deployment

- [ ] All automated tests pass (`bash test-social-login.sh`)
- [ ] Mock tests pass (`php test-social-login-mock.php`)
- [ ] PHPUnit tests pass (`php artisan test`)
- [ ] Manual Google OAuth flow tested
- [ ] Manual Facebook OAuth flow tested
- [ ] Error scenarios tested
- [ ] Database verification queries run
- [ ] No duplicate users in database
- [ ] All social users have tenants
- [ ] OAuth credentials configured in production `.env`
- [ ] Redirect URIs updated in Google/Facebook consoles
- [ ] HTTPS enabled in production
- [ ] Rate limiting enabled
- [ ] Monitoring/logging configured

### Post-Deployment

- [ ] Test production Google OAuth flow
- [ ] Test production Facebook OAuth flow
- [ ] Check production logs for errors
- [ ] Verify emails sent successfully
- [ ] Monitor user creation rate
- [ ] Check database for anomalies
- [ ] Test performance under load
- [ ] Verify security headers

---

## Test Data Cleanup

### Remove Test Users

```sql
-- Delete test users and their tenants
DELETE FROM users WHERE email LIKE '%test@example.com';
DELETE FROM users WHERE email LIKE '%@gmail.com' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Delete orphaned tenants (be careful with this!)
DELETE FROM tenants WHERE id NOT IN (SELECT DISTINCT tenant_id FROM users WHERE tenant_id IS NOT NULL);
```

### Reset Test Environment

```bash
# Fresh database
cd ainstein-laravel
php artisan migrate:fresh --seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Monitoring in Production

### Metrics to Track

1. **Social Login Usage**
   - Number of new users via Google
   - Number of new users via Facebook
   - Conversion rate (OAuth started vs completed)

2. **Performance**
   - Average callback processing time
   - Database query times
   - Failed login attempts

3. **Errors**
   - Invalid state exceptions
   - OAuth provider timeouts
   - Tenant creation failures

### Monitoring Queries

```sql
-- Social login statistics (last 7 days)
SELECT
    social_provider,
    COUNT(*) as total_users,
    COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
FROM users
WHERE social_provider IS NOT NULL
GROUP BY social_provider;

-- Failed login attempts (check application logs)
-- Monitor storage/logs/laravel.log for:
-- - "Invalid state exception"
-- - "Social auth callback error"
-- - "Failed to create user from social auth"
```

---

## Conclusion

This testing plan provides comprehensive coverage for the Social Login functionality. Always test in a staging environment before deploying to production, and monitor logs closely during initial deployment.

For questions or issues, refer to:
- Laravel Socialite documentation: https://laravel.com/docs/socialite
- Google OAuth documentation: https://developers.google.com/identity/protocols/oauth2
- Facebook Login documentation: https://developers.facebook.com/docs/facebook-login
