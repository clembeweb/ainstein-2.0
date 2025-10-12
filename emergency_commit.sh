#!/bin/bash
# EMERGENCY COMMIT SCRIPT - Save all local changes before workstation switch
# Date: 2025-10-12
# Purpose: Preserve security fixes that caused production 500 error

echo "========================================="
echo "EMERGENCY COMMIT - PRODUCTION DOWN"
echo "========================================="
echo ""
echo "This script will commit all local changes"
echo "Production currently showing 500 error"
echo "These changes need review before redeployment"
echo ""

# Check current status
echo "Current Git Status:"
git status --short
echo ""

# Add all relevant files
echo "Adding security-related files..."
git add bootstrap/app.php
git add routes/web.php
git add app/Http/Middleware/SecurityHeaders.php
git add tests/Feature/ProductionLoginTest.php
git add tests/Feature/ProductionLoginSimulationTest.php
git add database/migrations/2025_10_12_000001_add_remember_token_to_users_table.php

# Add documentation files
echo "Adding documentation files..."
git add HANDOFF_RESUME.md
git add DEPLOYMENT_STATUS.md
git add DEPLOYMENT_ACTIONS_LOG.md
git add emergency_commit.sh

# Check if there are files to commit
if [ -z "$(git status --porcelain)" ]; then
    echo "No changes to commit"
    exit 0
fi

# Create commit with detailed message
echo "Creating emergency commit..."
git commit -m "EMERGENCY: Security fixes and production recovery documentation

CRITICAL: Production showing 500 error after deployment
- Added SecurityHeaders middleware for security compliance
- Implemented rate limiting on login routes (throttle:6,1)
- Added remember_token migration for Laravel auth
- Created comprehensive production tests
- Added emergency handoff documentation

FILES DEPLOYED VIA SCP (INCORRECT METHOD):
- bootstrap/app.php
- routes/web.php
- app/Http/Middleware/SecurityHeaders.php

PRODUCTION STATUS:
- URL: https://ainstein.it
- Status: 500 Internal Server Error
- Server: 135.181.42.233
- Needs immediate rollback

DOCUMENTATION ADDED:
- HANDOFF_RESUME.md: Complete recovery instructions
- DEPLOYMENT_STATUS.md: Current deployment status
- DEPLOYMENT_ACTIONS_LOG.md: Detailed action timeline

NEXT STEPS:
1. Rollback production to working state
2. Properly test these changes in staging
3. Redeploy using Git workflow
4. Never use SCP for production deployment

Committed during emergency response to preserve changes before workstation switch."

# Push to remote
echo ""
echo "Pushing to remote repository..."
git push origin sviluppo-tool

echo ""
echo "========================================="
echo "EMERGENCY COMMIT COMPLETE"
echo "========================================="
echo ""
echo "Changes have been saved to Git"
echo "You can now safely switch workstations"
echo ""
echo "TO RESUME WORK:"
echo "1. Clone repository on new machine"
echo "2. Checkout sviluppo-tool branch"
echo "3. Read HANDOFF_RESUME.md"
echo "4. Fix production immediately"
echo ""
echo "Production recovery commands available in:"
echo "- HANDOFF_RESUME.md"
echo ""