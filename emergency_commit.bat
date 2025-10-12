@echo off
REM EMERGENCY COMMIT SCRIPT - Save all local changes before workstation switch
REM Date: 2025-10-12
REM Purpose: Preserve security fixes that caused production 500 error

echo =========================================
echo EMERGENCY COMMIT - PRODUCTION DOWN
echo =========================================
echo.
echo This script will commit all local changes
echo Production currently showing 500 error
echo These changes need review before redeployment
echo.

REM Check current status
echo Current Git Status:
git status --short
echo.

REM Add all relevant files
echo Adding security-related files...
git add bootstrap/app.php
git add routes/web.php
git add app/Http/Middleware/SecurityHeaders.php
git add tests/Feature/ProductionLoginTest.php
git add tests/Feature/ProductionLoginSimulationTest.php
git add database/migrations/2025_10_12_000001_add_remember_token_to_users_table.php

REM Add documentation files
echo Adding documentation files...
git add HANDOFF_RESUME.md
git add DEPLOYMENT_STATUS.md
git add DEPLOYMENT_ACTIONS_LOG.md
git add emergency_commit.sh
git add emergency_commit.bat

REM Create commit with detailed message
echo Creating emergency commit...
git commit -m "EMERGENCY: Security fixes and production recovery documentation" -m "" -m "CRITICAL: Production showing 500 error after deployment" -m "- Added SecurityHeaders middleware for security compliance" -m "- Implemented rate limiting on login routes (throttle:6,1)" -m "- Added remember_token migration for Laravel auth" -m "- Created comprehensive production tests" -m "- Added emergency handoff documentation" -m "" -m "FILES DEPLOYED VIA SCP (INCORRECT METHOD):" -m "- bootstrap/app.php" -m "- routes/web.php" -m "- app/Http/Middleware/SecurityHeaders.php" -m "" -m "PRODUCTION STATUS:" -m "- URL: https://ainstein.it" -m "- Status: 500 Internal Server Error" -m "- Server: 135.181.42.233" -m "- Needs immediate rollback" -m "" -m "NEXT STEPS:" -m "1. Rollback production to working state" -m "2. Properly test these changes in staging" -m "3. Redeploy using Git workflow" -m "4. Never use SCP for production deployment"

REM Push to remote
echo.
echo Pushing to remote repository...
git push origin sviluppo-tool

echo.
echo =========================================
echo EMERGENCY COMMIT COMPLETE
echo =========================================
echo.
echo Changes have been saved to Git
echo You can now safely switch workstations
echo.
echo TO RESUME WORK:
echo 1. Clone repository on new machine
echo 2. Checkout sviluppo-tool branch
echo 3. Read HANDOFF_RESUME.md
echo 4. Fix production immediately
echo.
echo Production recovery commands available in:
echo - HANDOFF_RESUME.md
echo.
pause