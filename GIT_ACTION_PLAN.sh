#!/bin/bash

###############################################################################
# AINSTEIN - Git Branch Consolidation Action Plan
# Data: 2025-10-13
# Descrizione: Script per consolidare branch e implementare Git Flow
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project directory
PROJECT_DIR="/c/laragon/www/ainstein-3/ainstein-laravel"

echo -e "${BLUE}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   AINSTEIN - Git Branch Consolidation Script        ║${NC}"
echo -e "${BLUE}║   Version: 1.0                                       ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

###############################################################################
# FASE 1: PRE-FLIGHT CHECKS
###############################################################################

echo -e "${YELLOW}[FASE 1] Pre-flight checks...${NC}"

# Check if we're in the right directory
cd "$PROJECT_DIR" || exit 1
echo -e "${GREEN}✓${NC} Directory: $(pwd)"

# Check for uncommitted changes
if [[ -n $(git status -s) ]]; then
    echo -e "${RED}✗${NC} Uncommitted changes detected!"
    echo -e "${YELLOW}Please commit or stash your changes before running this script.${NC}"
    git status -s
    exit 1
fi
echo -e "${GREEN}✓${NC} Working directory clean"

# Fetch latest from remote
echo -e "${BLUE}Fetching from remote...${NC}"
git fetch --all --tags
echo -e "${GREEN}✓${NC} Remote fetched"

echo ""

###############################################################################
# FASE 2: BACKUP E TAG
###############################################################################

echo -e "${YELLOW}[FASE 2] Creating safety backups...${NC}"

# Create snapshot tags for all branches
TIMESTAMP=$(date +%Y-%m-%d)

echo -e "${BLUE}Creating snapshot tags...${NC}"

# Master
git tag -a "snapshot-master-$TIMESTAMP" master -m "Master snapshot before consolidation - $TIMESTAMP" 2>/dev/null || echo "Tag already exists"
echo -e "${GREEN}✓${NC} Tagged master"

# Production
git tag -a "snapshot-production-$TIMESTAMP" production -m "Production snapshot - $TIMESTAMP" 2>/dev/null || echo "Tag already exists"
echo -e "${GREEN}✓${NC} Tagged production"

# Sviluppo-tool
git tag -a "snapshot-sviluppo-$TIMESTAMP" sviluppo-tool -m "Sviluppo-tool snapshot - $TIMESTAMP" 2>/dev/null || echo "Tag already exists"
echo -e "${GREEN}✓${NC} Tagged sviluppo-tool"

# Hotfix
git tag -a "snapshot-hotfix-$TIMESTAMP" hotfix/security-fixes-2025-10-12 -m "Hotfix snapshot - $TIMESTAMP" 2>/dev/null || echo "Tag already exists"
echo -e "${GREEN}✓${NC} Tagged hotfix"

# Push tags to remote
echo -e "${BLUE}Pushing tags to remote...${NC}"
git push origin --tags
echo -e "${GREEN}✓${NC} Backup tags created and pushed"

echo ""

###############################################################################
# FASE 3: MERGE HOTFIX TO MASTER
###############################################################################

echo -e "${YELLOW}[FASE 3] Merging hotfix to master...${NC}"

# Checkout master
git checkout master
git pull origin master
echo -e "${GREEN}✓${NC} Master updated"

# Show what will be merged
echo -e "${BLUE}Commits to be merged:${NC}"
git log master..hotfix/security-fixes-2025-10-12 --oneline

read -p "Proceed with merge? (yes/no): " CONFIRM
if [[ "$CONFIRM" != "yes" ]]; then
    echo -e "${RED}Merge cancelled.${NC}"
    exit 1
fi

# Merge hotfix
echo -e "${BLUE}Merging hotfix/security-fixes-2025-10-12...${NC}"
git merge hotfix/security-fixes-2025-10-12 --no-ff -m "Merge hotfix: Analytics and Subscriptions fixes

- Fix critical bugs in Analytics dashboard
- Fix Subscriptions display issues
- Add handoff documentation

Merged from: hotfix/security-fixes-2025-10-12"

echo -e "${GREEN}✓${NC} Hotfix merged to master"

# Tag release
git tag -a "v1.0.1" -m "Release v1.0.1 - Analytics and Subscriptions fixes

Bug Fixes:
- Analytics dashboard critical bugs
- Subscriptions display issues

Documentation:
- Added workstation handoff documentation
- Completed branch strategy guide"

echo -e "${GREEN}✓${NC} Tagged v1.0.1"

# Push to remote
git push origin master
git push origin --tags
echo -e "${GREEN}✓${NC} Master and tags pushed to remote"

echo ""

###############################################################################
# FASE 4: CREATE DEVELOP BRANCH
###############################################################################

echo -e "${YELLOW}[FASE 4] Creating develop branch...${NC}"

# Check if develop already exists
if git show-ref --verify --quiet refs/heads/develop; then
    echo -e "${YELLOW}⚠${NC} Branch 'develop' already exists"
    read -p "Delete and recreate? (yes/no): " CONFIRM
    if [[ "$CONFIRM" == "yes" ]]; then
        git branch -D develop
        echo -e "${GREEN}✓${NC} Old develop branch deleted"
    else
        echo -e "${YELLOW}Skipping develop creation${NC}"
    fi
else
    # Create develop from sviluppo-tool (has correct structure)
    echo -e "${BLUE}Creating develop from sviluppo-tool...${NC}"
    git checkout sviluppo-tool
    git pull origin sviluppo-tool
    git checkout -b develop
    git push -u origin develop
    echo -e "${GREEN}✓${NC} Develop branch created and pushed"
fi

echo ""

###############################################################################
# FASE 5: CLEANUP DUPLICATE BRANCHES
###############################################################################

echo -e "${YELLOW}[FASE 5] Cleaning up duplicate branches...${NC}"

echo -e "${BLUE}Branch to delete: emergency/production-500-recovery-2025-10-12${NC}"
echo -e "${BLUE}Reason: Identical to sviluppo-tool${NC}"
read -p "Delete emergency branch? (yes/no): " CONFIRM
if [[ "$CONFIRM" == "yes" ]]; then
    git branch -d emergency/production-500-recovery-2025-10-12 2>/dev/null || git branch -D emergency/production-500-recovery-2025-10-12
    git push origin --delete emergency/production-500-recovery-2025-10-12 2>/dev/null || echo "Remote branch not found"
    echo -e "${GREEN}✓${NC} Emergency branch deleted"
else
    echo -e "${YELLOW}Skipped${NC}"
fi

# Archive hotfix branch (already merged)
echo -e "${BLUE}Hotfix branch already merged to master${NC}"
read -p "Delete hotfix branch? (yes/no): " CONFIRM
if [[ "$CONFIRM" == "yes" ]]; then
    git branch -d hotfix/security-fixes-2025-10-12 2>/dev/null || echo "Already deleted"
    git push origin --delete hotfix/security-fixes-2025-10-12 2>/dev/null || echo "Remote branch not found"
    echo -e "${GREEN}✓${NC} Hotfix branch deleted"
else
    echo -e "${YELLOW}Skipped${NC}"
fi

echo ""

###############################################################################
# FASE 6: UPDATE PRODUCTION
###############################################################################

echo -e "${YELLOW}[FASE 6] Updating production branch...${NC}"

git checkout production
git pull origin production

echo -e "${BLUE}Merging master to production...${NC}"
git merge master --ff-only || {
    echo -e "${RED}✗${NC} Fast-forward merge not possible"
    echo -e "${YELLOW}This might require a force update or manual merge${NC}"
    exit 1
}

# Tag production deployment
git tag -a "production-$TIMESTAMP" -m "Production deployment - $TIMESTAMP"
git push origin production
git push origin --tags
echo -e "${GREEN}✓${NC} Production updated and tagged"

echo ""

###############################################################################
# FASE 7: SUMMARY AND NEXT STEPS
###############################################################################

echo -e "${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║            CONSOLIDATION COMPLETED!                  ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${BLUE}Current branch structure:${NC}"
git branch -vv

echo ""
echo -e "${BLUE}Recent tags:${NC}"
git tag -l "*$TIMESTAMP*"

echo ""
echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}                  NEXT STEPS                           ${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${BLUE}1.${NC} Test the application thoroughly:"
echo -e "   ${GREEN}php artisan test${NC}"
echo ""

echo -e "${BLUE}2.${NC} Verify master branch functionality:"
echo -e "   ${GREEN}php artisan serve${NC}"
echo -e "   Test: Analytics, Subscriptions, Campaign Generator"
echo ""

echo -e "${BLUE}3.${NC} DECISION REQUIRED: Directory structure"
echo -e "   ${YELLOW}⚠${NC} sviluppo-tool has Laravel in root"
echo -e "   ${YELLOW}⚠${NC} master has Laravel in ainstein-laravel/"
echo -e "   Choose: Keep which structure?"
echo ""

echo -e "${BLUE}4.${NC} Migrate CrewAI features from sviluppo-tool:"
echo -e "   ${GREEN}git checkout develop${NC}"
echo -e "   ${GREEN}git checkout -b feature/crewai-integration${NC}"
echo -e "   Cherry-pick or merge specific commits"
echo ""

echo -e "${BLUE}5.${NC} Setup branch protection on GitHub:"
echo -e "   - Protect master and develop"
echo -e "   - Require PR reviews"
echo -e "   - Enable CI/CD checks"
echo ""

echo -e "${BLUE}6.${NC} Update team documentation:"
echo -e "   - New Git workflow"
echo -e "   - Branch naming conventions"
echo -e "   - Release process"
echo ""

echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}For detailed information, see: GIT_ANALYSIS_REPORT.md${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""

# Return to original branch
git checkout master

echo -e "${GREEN}✓ All done! Safe to proceed with development.${NC}"
