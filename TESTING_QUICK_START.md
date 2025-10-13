# Social Login Testing - Quick Start Guide

## Files Created

All test files are located in: `C:\laragon\www\ainstein-3\`

| File | Purpose | Requires OAuth |
|------|---------|----------------|
| `test-social-login.sh` | Automated setup verification | No |
| `test-social-login-mock.php` | Mock user creation tests | No |
| `SOCIAL_LOGIN_TEST_PLAN.md` | Complete testing documentation | No |
| `TEST_RESULTS_SUMMARY.md` | Test deliverables summary | No |
| `ainstein-laravel/tests/Feature/Auth/SocialAuthTest.php` | PHPUnit tests | No |

---

## Run Tests WITHOUT OAuth Credentials

### 1. Bash Verification (Recommended First)
```bash
cd C:\laragon\www\ainstein-3
bash test-social-login.sh
```

**What it checks:**
- .env file exists
- Laravel Socialite installed
- SocialAuthController exists
- Routes registered
- Database schema correct
- User model configured

**Expected output:**
```
[PASS] .env file exists
[PASS] SocialAuthController exists
[PASS] Social routes defined
...
All critical tests passed!
```

---

### 2. Mock PHP Tests
```bash
cd C:\laragon\www\ainstein-3
php test-social-login-mock.php
```

**What it tests:**
- User creation from Google OAuth
- User creation from Facebook OAuth
- Existing user social link
- Tenant auto-creation
- Database records
- User model methods
- Slug uniqueness

**Expected output:**
```
[TEST 1] Create User from Google OAuth (New Email)
  ✓ User created successfully
  ✓ Email matches
  ✓ Provider is Google
...
All tests passed! ✓
```

---

### 3. PHPUnit Tests
```bash
cd C:\laragon\www\ainstein-3\ainstein-laravel
php artisan test --filter SocialAuthTest
```

**What it tests:**
- OAuth redirect responses
- User registration flow
- Existing user login
- Error handling
- API endpoints
- User model features

**Note:** Some tests may fail due to Socialite mocking complexity, but core functionality tests should pass.

---

## Run Tests WITH OAuth Credentials

### Configure .env

Add to `ainstein-laravel/.env`:
```env
# Google OAuth
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback

# Facebook OAuth
FACEBOOK_CLIENT_ID=your-facebook-app-id
FACEBOOK_CLIENT_SECRET=your-facebook-app-secret
FACEBOOK_REDIRECT_URI=http://localhost/auth/facebook/callback
```

### Configure OAuth Providers

**Google Cloud Console:**
1. Go to: https://console.cloud.google.com/
2. Select your project
3. APIs & Services > Credentials
4. Add Authorized redirect URI: `http://localhost/auth/google/callback`

**Facebook Developer:**
1. Go to: https://developers.facebook.com/apps/
2. Select your app
3. Facebook Login > Settings
4. Add Valid OAuth Redirect URI: `http://localhost/auth/facebook/callback`

### Manual Testing Steps

**Test Google Login:**
1. Open browser: `http://localhost/login`
2. Click "Sign in with Google"
3. Select Google account
4. Approve permissions
5. Should redirect to dashboard with success message

**Test Facebook Login:**
1. Open browser: `http://localhost/login`
2. Click "Sign in with Facebook"
3. Log in to Facebook
4. Approve permissions
5. Should redirect to dashboard with success message

**Verify Database:**
```sql
-- Check created users
SELECT id, name, email, social_provider, social_id, created_at
FROM users
WHERE social_provider IS NOT NULL;

-- Check tenants
SELECT u.email, t.name as tenant_name, t.plan_type
FROM users u
JOIN tenants t ON u.tenant_id = t.id
WHERE u.social_provider IS NOT NULL;
```

---

## Troubleshooting

### Error: "Authentication service temporarily unavailable"
**Solution:** OAuth credentials missing or incorrect in .env

### Error: "Invalid state exception"
**Solution:** Clear browser cookies/cache, or session expired

### Error: User created but not logged in
**Solution:** Check user `is_active` field, verify session configuration

### Complete Troubleshooting Guide
See: `SOCIAL_LOGIN_TEST_PLAN.md` > Section 8: Troubleshooting Guide

---

## Documentation

| Document | Content |
|----------|---------|
| `SOCIAL_LOGIN_TEST_PLAN.md` | Complete testing guide (manual procedures, SQL queries, error scenarios, security) |
| `TEST_RESULTS_SUMMARY.md` | Test deliverables overview, features, usage instructions |
| `TESTING_QUICK_START.md` | This file - Quick reference |

---

## Quick Reference

### Routes
```
Web:
GET  /auth/{provider}           - Redirect to OAuth
GET  /auth/{provider}/callback  - Handle OAuth callback

API:
GET  /api/v1/auth/social/{provider}           - Get redirect URL (JSON)
POST /api/v1/auth/social/{provider}/callback  - Handle callback (returns token)
```

### Supported Providers
- `google`
- `facebook`

### Database Fields
Users table has:
- `social_provider` (string, nullable)
- `social_id` (string, nullable)
- `social_avatar` (text, nullable)

---

## Next Steps

1. ✓ Run `bash test-social-login.sh` - Verify setup
2. ✓ Run `php test-social-login-mock.php` - Test logic
3. [ ] Configure OAuth credentials in .env
4. [ ] Test Google OAuth flow manually
5. [ ] Test Facebook OAuth flow manually
6. [ ] Verify database records
7. [ ] Test error scenarios
8. [ ] Review security checklist

---

**Need Help?**
- Check `SOCIAL_LOGIN_TEST_PLAN.md` for detailed documentation
- Review Laravel logs: `ainstein-laravel/storage/logs/laravel.log`
- Check OAuth provider consoles for configuration errors
