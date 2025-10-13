# Handoff & Resume Instructions - AINSTEIN 3.0

**Critical Date:** 2025-10-12
**Situation:** Emergency production 500 error during workstation switch
**Priority:** High - Production recovery needed

## Quick Start - New Workstation Setup

### Step 1: Clone and Setup Repository
```bash
# Clone the repository
git clone https://github.com/clembeweb/ainstein-2.0.git ainstein-3
cd ainstein-3

# Fetch all branches and tags
git fetch --all --tags --prune

# View all available branches
git branch -a
```

### Step 2: Choose Your Starting Point

#### Option A: Resume from Last Stable State (RECOMMENDED)
```bash
# Checkout the last known stable state before emergency
git checkout pre-emergency-2025-10-12

# Create a new working branch
git checkout -b recovery/stabilize-production
```

#### Option B: Continue Emergency Fix
```bash
# If you need to continue debugging the 500 error
git checkout emergency/production-500-recovery-2025-10-12

# This branch contains the emergency state with the 500 error
# DO NOT deploy this to production!
```

#### Option C: Apply Clean Security Fixes
```bash
# For applying only validated security fixes
git checkout hotfix/security-fixes-2025-10-12

# This is a clean branch from master, ready for cherry-picking
```

#### Option D: Resume Normal Development (AFTER emergency resolved)
```bash
# Only use this after production is stable
git checkout sviluppo-tool
git pull origin sviluppo-tool
```

## Current Repository State

### Branch Status Summary
| Branch | Purpose | Status | Safe to Deploy |
|--------|---------|---------|----------------|
| `master` | Main stable branch | 41 commits behind sviluppo-tool | Yes (but outdated) |
| `production` | Production deployment | 2 commits ahead (Laravel root) | NO - needs fixes |
| `sviluppo-tool` | Active development | Contains emergency error | NO - has 500 error |
| `emergency/production-500-recovery-2025-10-12` | Emergency snapshot | 500 error state | NO - debug only |
| `hotfix/security-fixes-2025-10-12` | Clean hotfix branch | Empty, ready for fixes | Yes (after adding fixes) |

### Important Tags
- `pre-emergency-2025-10-12` - Last stable commit (bdd63fe5)
- `emergency-state-2025-10-12` - Emergency state with 500 error (24e09d4b)

## Environment Setup

### 1. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env
```

### 2. Configure Environment
```bash
# Generate application key
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ainstein
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### 3. Setup Database
```bash
# Run migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed
```

### 4. Configure Storage
```bash
# Create storage link
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 755 storage bootstrap/cache
```

## Emergency Recovery Procedures

### If Production is Down (500 Error)

#### 1. Immediate Rollback
```bash
# Switch to production branch
git checkout production

# Rollback to last stable tag
git reset --hard pre-emergency-2025-10-12

# Force push (ONLY in emergency)
git push --force-with-lease origin production
```

#### 2. Debug the Issue
```bash
# Checkout emergency state for debugging
git checkout emergency/production-500-recovery-2025-10-12

# Check Laravel logs
tail -f storage/logs/laravel.log

# Check PHP error logs
tail -f /var/log/php/error.log

# Test specific routes
php artisan route:list
php artisan config:cache
php artisan view:clear
```

#### 3. Apply Fix
```bash
# Create fix in hotfix branch
git checkout hotfix/security-fixes-2025-10-12

# Apply your fixes
# ... make changes ...

# Test thoroughly
php artisan test

# Commit fix
git add .
git commit -m "fix: Resolve production 500 error"

# Merge to master
git checkout master
git merge hotfix/security-fixes-2025-10-12

# Deploy to production
git checkout production
git merge master
git push origin production
```

## Understanding the Current Issues

### 1. Production 500 Error
- **Location:** `emergency/production-500-recovery-2025-10-12` branch
- **Commit:** 24e09d4b
- **Description:** Emergency state with security fixes that caused 500 error
- **Files Changed:** Multiple security-related files (check git diff)

### 2. Branch Divergence
- `sviluppo-tool` is 41 commits ahead of master
- Contains mix of features and emergency fixes
- Needs careful review before merging

### 3. Production Branch Structure
- Production branch has Laravel in root directory
- Different from development structure
- Requires special deployment consideration

## Recovery Checklist

### Pre-Deployment Verification
- [ ] All tests pass (`php artisan test`)
- [ ] No PHP errors (`php -l` on all changed files)
- [ ] Routes work (`php artisan route:list`)
- [ ] Database migrations run cleanly
- [ ] Configuration cached properly (`php artisan config:cache`)
- [ ] Views compile (`php artisan view:clear && php artisan view:cache`)
- [ ] No 500 errors in staging environment

### Post-Deployment Verification
- [ ] Homepage loads without errors
- [ ] Login functionality works
- [ ] Admin dashboard accessible
- [ ] API endpoints responding
- [ ] No errors in Laravel log
- [ ] No errors in PHP error log
- [ ] Database queries executing properly

## Contact & Resources

### Repository Information
- **GitHub:** https://github.com/clembeweb/ainstein-2.0.git
- **Main Branch:** master
- **Production Branch:** production
- **Development Branch:** sviluppo-tool

### Key Files to Check
1. `storage/logs/laravel.log` - Application errors
2. `.env` - Environment configuration
3. `config/app.php` - Application configuration
4. `routes/web.php` - Route definitions
5. `app/Exceptions/Handler.php` - Error handling

### Emergency Commands
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reset opcache (if enabled)
php artisan opcache:reset

# Check application health
php artisan up
php artisan down --message="Emergency maintenance"
```

## Next Steps Priority

### High Priority (Do First)
1. **Stabilize Production**
   - Rollback to `pre-emergency-2025-10-12` if still broken
   - Or apply minimal fixes from `hotfix/security-fixes-2025-10-12`

2. **Identify Root Cause**
   - Review changes in commit 24e09d4b
   - Check error logs for specific issues
   - Test in local environment

### Medium Priority (After Stable)
3. **Clean Up Branches**
   - Review 41 commits in sviluppo-tool
   - Create proper PR for master
   - Document all changes

4. **Update Documentation**
   - Document the emergency and resolution
   - Update deployment procedures
   - Create post-mortem report

### Low Priority (When Time Permits)
5. **Improve CI/CD**
   - Add automated tests for critical paths
   - Implement staging environment checks
   - Add rollback automation

## Warning Signs to Watch For

### Critical Errors
- 500 Internal Server Error
- "Class not found" errors
- Database connection failures
- Permission denied errors

### Performance Issues
- Slow page loads (>3 seconds)
- High memory usage
- Database query timeouts
- Cache failures

### Security Concerns
- Exposed .env file
- Debug mode enabled in production
- Weak authentication
- SQL injection vulnerabilities

---

**Remember:**
- Always test in staging before production
- Keep backups before major changes
- Document everything you do
- Ask for help if unsure

**Current Status:** EMERGENCY - Production needs immediate attention
**Last Updated:** 2025-10-12