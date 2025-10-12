# 🔐 OAuth Documentation - AINSTEIN

**Last Updated**: 2025-10-10
**Status**: ✅ Complete and Operational

---

## 📚 OAuth Documentation Index

### Core Documentation
1. **[OAuth Architecture](OAUTH_ARCHITECTURE.md)** - Complete multi-tenant OAuth system architecture
2. **[OAuth Setup Guide](OAUTH_SETUP_GUIDE.md)** - Step-by-step configuration guide for OAuth providers
3. **[Google OAuth Setup](GOOGLE_OAUTH_SETUP.md)** - Detailed Google OAuth configuration instructions

## 🎯 OAuth System Overview

AINSTEIN implements a sophisticated multi-tenant OAuth system with three distinct OAuth integrations:

### 1. Social Login OAuth
**Purpose**: User authentication via social providers
- Google Login
- Facebook Login
- Per-tenant configuration
- Encrypted credential storage

### 2. Marketing API OAuth
**Purpose**: Integration with advertising platforms
- Google Ads API (Campaign Generator)
- Facebook Ads API (future)

### 3. SEO Tools OAuth
**Purpose**: Integration with SEO services
- Google Search Console API
- Google Analytics API (planned)

## 🏗️ System Architecture

```
┌──────────────────────────┐
│    User Login Request    │
└────────────┬─────────────┘
             │
             ▼
┌──────────────────────────┐
│  TenantOAuthService      │
│  (Check Configuration)    │
└────────────┬─────────────┘
             │
    ┌────────┴────────┐
    │                 │
    ▼                 ▼
┌─────────┐    ┌──────────┐
│ Tenant  │    │ Platform │
│ Config  │    │ Fallback │
└─────────┘    └──────────┘
```

## 📊 Configuration Levels

| Level | Storage | Priority | Use Case |
|-------|---------|----------|----------|
| **Tenant** | `tenant_oauth_providers` | 1 (Highest) | Tenant-specific OAuth apps |
| **Platform** | `platform_settings` | 2 (Fallback) | Shared OAuth for all tenants |
| **Environment** | `.env` file | 3 (Lowest) | Development/default config |

## 🔧 Quick Setup Guide

### For Tenant Administrators

1. **Access OAuth Settings**
   ```
   Dashboard → Settings → OAuth Settings
   ```

2. **Configure Provider**
   - Enter Client ID
   - Enter Client Secret
   - Test Configuration
   - Enable Provider

3. **Test Login**
   - Logout
   - Try "Login with Google/Facebook"

### For Super Administrators

1. **Configure Platform Fallback**
   ```
   Admin Panel → Platform Settings → OAuth Integrations
   ```

2. **Set Global OAuth Apps**
   - Configure default Google OAuth
   - Configure default Facebook OAuth

## 🔒 Security Features

- ✅ **Encryption at Rest**: All credentials encrypted with Laravel Crypt
- ✅ **HTTPS Required**: OAuth only works over secure connections
- ✅ **State Validation**: CSRF protection via Socialite
- ✅ **Scope Limitation**: Minimal permissions requested
- ✅ **Tenant Isolation**: Each tenant's OAuth config is isolated

## 📈 Implementation Status

| Feature | Status | Notes |
|---------|--------|-------|
| Google Login | ✅ Implemented | Multi-tenant ready |
| Facebook Login | ✅ Implemented | Multi-tenant ready |
| Per-Tenant Config | ✅ Implemented | With encryption |
| Platform Fallback | ✅ Implemented | Global defaults |
| UI Configuration | ✅ Implemented | Tenant dashboard |
| Test Function | ✅ Implemented | Built-in testing |
| LinkedIn Login | ❌ Not Implemented | Future enhancement |
| Twitter/X Login | ❌ Not Implemented | Future enhancement |

## 🛠️ Troubleshooting

### Common Issues

#### "Invalid Client" Error
- **Cause**: Incorrect Client ID or Secret
- **Solution**: Verify credentials in provider console

#### "Redirect URI Mismatch"
- **Cause**: Callback URL doesn't match
- **Solution**: Add exact URL to provider settings:
  - Google: `https://yourdomain.com/auth/google/callback`
  - Facebook: `https://yourdomain.com/auth/facebook/callback`

#### No Login Buttons Visible
- **Cause**: No OAuth providers configured
- **Solution**: Configure at least one provider in settings

#### Decryption Error
- **Cause**: APP_KEY changed after saving credentials
- **Solution**: Reconfigure OAuth credentials

## 📝 Database Schema

### tenant_oauth_providers
```sql
- id (primary key)
- tenant_id (foreign key)
- provider (google/facebook)
- client_id (encrypted)
- client_secret (encrypted)
- enabled (boolean)
- test_status
- test_message
- tested_at
```

## 🧪 Testing OAuth

### Manual Testing
1. Configure provider in dashboard
2. Use "Test" button to verify
3. Logout and test social login

### Automated Testing
```bash
php artisan test --filter=OAuthMultiTenantTest
```

## 📚 Related Documentation

- [Main README](../../README.md)
- [Testing Guide](../testing/README.md)
- [API Documentation](../api/README.md)
- [Deployment Guide](../../DEPLOYMENT.md)

## 🚀 Future Enhancements

### Planned Features
- [ ] Additional providers (LinkedIn, Twitter)
- [ ] OAuth for API integrations
- [ ] Tenant-specific callback URLs
- [ ] OAuth token refresh automation
- [ ] SAML support for enterprise

### Under Consideration
- [ ] OpenID Connect compliance
- [ ] Multi-factor authentication
- [ ] Social account linking/unlinking
- [ ] OAuth provider statistics

---

**Need Help?** Check the troubleshooting section or review the detailed guides above.