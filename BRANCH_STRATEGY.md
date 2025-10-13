# Branch Strategy Documentation - AINSTEIN 3.0

**Last Updated:** 2025-10-12
**Critical:** This document defines the branch structure for production deployment and recovery.

## Current Branch Structure

### Primary Branches

#### 1. `master` (Main Branch)
- **Purpose:** Stable production-ready code
- **Status:** Base branch, all features merge here
- **Current State:** 41 commits behind sviluppo-tool
- **Deployment:** Not directly deployed to production
- **Protection:** Should have branch protection rules

#### 2. `production`
- **Purpose:** Production deployment branch with Laravel in root
- **Status:** 2 commits ahead of master (Laravel root setup)
- **Current State:** Contains production-specific configuration
- **Deployment:** This is the ACTUAL production branch
- **Key Differences:**
  - Laravel installed in root directory (not /public)
  - Production-specific documentation added

#### 3. `sviluppo-tool` (Development Branch)
- **Purpose:** Active development branch
- **Status:** 41 commits ahead of master
- **Current State:** Contains all latest features + emergency fixes
- **Important:** Contains EMERGENCY 500 error state (commit 24e09d4b)
- **Usage:** Main development branch, needs cleanup before merging to master

### Emergency & Recovery Branches

#### 4. `emergency/production-500-recovery-2025-10-12`
- **Purpose:** Snapshot of production emergency state
- **Created:** 2025-10-12
- **Contains:** Production 500 error + attempted security fixes
- **Status:** EMERGENCY STATE - Do not deploy
- **Usage:** Reference for debugging and recovery

#### 5. `hotfix/security-fixes-2025-10-12`
- **Purpose:** Clean hotfix branch for security patches
- **Created:** 2025-10-12 (from master)
- **Status:** Skeleton branch ready for cherry-picking fixes
- **Usage:** Apply only working security fixes here

## Important Tags

### `pre-emergency-2025-10-12`
- **Commit:** bdd63fe5
- **Description:** Last known stable state before production emergency
- **Usage:** Fallback point if recovery fails

### `emergency-state-2025-10-12`
- **Commit:** 24e09d4b
- **Description:** Emergency state with 500 error and security fixes
- **Usage:** Reference for debugging the production issue

### `v1.0-before-refactoring`
- **Description:** Historical tag from previous refactoring

## Branch Workflow

### Normal Development Flow
```
feature/* → sviluppo-tool → master → production
```

### Hotfix Flow (Emergency)
```
master → hotfix/* → master → production (fast-track)
         ↓
    sviluppo-tool (backport)
```

### Current Emergency Recovery Flow
1. Identify working fixes in `emergency/production-500-recovery-2025-10-12`
2. Cherry-pick ONLY working fixes to `hotfix/security-fixes-2025-10-12`
3. Test thoroughly in staging
4. Merge hotfix to master
5. Deploy master to production branch
6. Backport fixes to sviluppo-tool

## Deployment Strategy

### Production Deployment
**CRITICAL:** Production uses the `production` branch, NOT master!

```bash
# Standard production deployment
git checkout production
git merge master --no-ff
git push origin production
# Deploy from production branch
```

### Emergency Deployment
```bash
# For emergency fixes
git checkout hotfix/security-fixes-2025-10-12
# Apply fixes
git checkout master
git merge hotfix/security-fixes-2025-10-12 --no-ff
git checkout production
git merge master --no-ff
git push origin production
```

## Critical Issues to Address

### Immediate Actions Needed
1. **Resolve Production 500 Error**
   - Current state in `emergency/production-500-recovery-2025-10-12`
   - Needs investigation and proper fix

2. **Sync sviluppo-tool with master**
   - 41 commits need review and merge
   - Clean up emergency commits
   - Create proper PR for review

3. **Update production branch**
   - Currently outdated
   - Needs security fixes once validated

### Branch Cleanup Recommendations
1. After emergency resolution, delete:
   - `emergency/production-500-recovery-2025-10-12` (after documentation)

2. Consider creating:
   - `release/*` branches for staged deployments
   - `feature/*` branches for new development

## Recovery Instructions for New Workstation

### Initial Setup
```bash
# Clone repository
git clone https://github.com/clembeweb/ainstein-2.0.git
cd ainstein-2.0

# Fetch all branches and tags
git fetch --all --tags

# Check out the safe pre-emergency state
git checkout pre-emergency-2025-10-12
```

### To Continue Emergency Fix
```bash
# If continuing emergency work
git checkout emergency/production-500-recovery-2025-10-12

# Or for clean fixes
git checkout hotfix/security-fixes-2025-10-12
```

### To Resume Normal Development
```bash
# For normal development (AFTER emergency resolved)
git checkout sviluppo-tool
git pull origin sviluppo-tool
```

## Branch Protection Rules (Recommended)

### master
- Require pull request reviews
- Require status checks to pass
- Require branches to be up to date
- Include administrators

### production
- Require pull request reviews (2 reviewers)
- Require status checks to pass
- Restrict who can push
- No force pushes allowed

## Emergency Contacts
- Repository: https://github.com/clembeweb/ainstein-2.0.git
- Production URL: [Add production URL]
- Staging URL: [Add staging URL]

## Notes
- The `sviluppo-tool` branch contains significant work that needs careful review
- Production branch uses different directory structure (Laravel in root)
- Emergency state needs immediate attention before any production deployment
- All security fixes should be validated in staging first

---
**Remember:** Never deploy directly to production without proper testing and validation!