@echo off
REM COMMIT AND PUSH ALL CHANGES - INCLUDING SECURITY FILES
REM Run this before switching workstations!

echo =========================================
echo COMMITTING ALL LOCAL CHANGES
echo =========================================
echo.

REM First check what we have
echo Current status:
git status --short
echo.

REM Add the security files that exist
echo Adding existing security files...
git add app/Http/Middleware/SecurityHeaders.php
git add tests/Feature/ProductionLoginTest.php
git add tests/Feature/ProductionLoginSimulationTest.php
git add database/migrations/2025_10_12_000001_add_remember_token_to_users_table.php

REM Add modified files
echo Adding modified files...
git add bootstrap/app.php 2>nul
git add routes/web.php 2>nul

REM Add all documentation
echo Adding documentation...
git add *.md
git add emergency_commit.bat
git add emergency_commit.sh
git add COMMIT_AND_PUSH.bat
git add verify_security_fixes.php

REM Show what will be committed
echo.
echo Files to be committed:
git status --short --cached
echo.

REM Commit
git commit -m "EMERGENCY: Complete handoff documentation and security fixes" -m "" -m "PRODUCTION STATUS: Currently showing 500 error" -m "" -m "This commit contains:" -m "1. Security middleware and tests created locally" -m "2. Comprehensive handoff documentation" -m "3. Emergency recovery procedures" -m "4. Deployment logs and status" -m "" -m "CRITICAL: Production needs immediate rollback" -m "See HANDOFF_RESUME.md for recovery instructions"

REM Push
echo.
echo Pushing to remote...
git push origin sviluppo-tool

echo.
echo =========================================
echo DONE! You can now switch workstations
echo =========================================
echo.
echo On the new machine:
echo 1. git clone [repo]
echo 2. git checkout sviluppo-tool
echo 3. Read HANDOFF_RESUME.md
echo 4. FIX PRODUCTION IMMEDIATELY!
echo.
pause