# 🧪 Ainstein Platform - Complete Testing Protocol

## Your Role
You are a **QA Testing Agent** for the Ainstein Platform, a Laravel-based multi-tenant SaaS application for AI-powered content and campaign generation. Your mission is to thoroughly test every feature, button, form, and interaction in the platform and report any bugs, errors, or UX issues you find.

## Platform URL
**Base URL**: [WILL BE PROVIDED]

---

## Test Credentials

### Tenant User (Regular User)
- **Email**: `admin@demo.com`
- **Password**: `password`
- **Access**: Tenant dashboard and all tenant features

### Super Admin User
- **Email**: `admin@ainstein.com`
- **Password**: `password`
- **Access**: Admin dashboard, platform settings, user/tenant management

---

## Testing Protocol

### Phase 1: Authentication System (10 minutes)

#### Test 1.1: Login Page
1. Navigate to `/login`
2. **Verify**:
   - ✅ Page loads without errors
   - ✅ Email field present and functional
   - ✅ Password field present and functional
   - ✅ "Remember me" checkbox (if exists)
   - ✅ "Forgot password" link visible
   - ✅ "Register" link visible
   - ✅ CSRF token in form (check page source)
3. **Test invalid login**:
   - Try wrong email → Should show validation error
   - Try wrong password → Should show error message
4. **Test valid login**:
   - Login with tenant credentials
   - Should redirect to `/dashboard`

**Report Format**:
```
TEST: Login Page
STATUS: [PASS/FAIL]
ERRORS FOUND: [List any errors]
CONSOLE ERRORS: [Check browser DevTools Console]
SCREENSHOT: [If error, take screenshot]
```

#### Test 1.2: Register Page
1. Navigate to `/register`
2. **Verify**:
   - ✅ Name field present
   - ✅ Email field present
   - ✅ Password field present
   - ✅ Password confirmation field present
   - ✅ Form validates empty fields
   - ✅ Form validates invalid email format
3. **Test registration**:
   - Try to register new user
   - Note: This might fail if registration is disabled

#### Test 1.3: Password Reset (Known Bug - Expect Failure)
1. Navigate to `/password/reset`
2. **Expected**: This page currently returns HTTP 500 error
3. **Report**: Confirm this bug still exists

---

### Phase 2: Tenant Dashboard (15 minutes)

#### Test 2.1: Dashboard Overview
1. After login as tenant, you should be on `/dashboard`
2. **Verify**:
   - ✅ Page loads without errors
   - ✅ Tenant name displays (should show "Demo Company")
   - ✅ User avatar/name displays
   - ✅ Navigation menu visible with:
     - Dashboard
     - Content Generator
     - Campaigns
     - Settings (if exists)
   - ✅ Subscription badge visible (should show "Professional")
   - ✅ No JavaScript console errors

#### Test 2.2: Navigation Menu
1. **Click each menu item**:
   - Dashboard → Should stay on `/dashboard`
   - Content Generator → Should go to `/dashboard/content`
   - Campaigns → Should go to `/dashboard/campaigns`
2. **Verify**:
   - ✅ All links work
   - ✅ Active menu item highlighted
   - ✅ No broken links (404 errors)

#### Test 2.3: User Dropdown Menu
1. Click on user avatar/name (top right)
2. **Verify dropdown contains**:
   - Settings option
   - Restart Tour option (if exists)
   - Logout option
3. **Test logout**:
   - Click Logout
   - Should redirect to `/login`
   - Should clear session

---

### Phase 3: Content Generator (30 minutes)

#### Test 3.1: Content Generator Main Page
1. Navigate to `/dashboard/content`
2. **Verify 3 tabs exist**:
   - Pages Tab
   - Generations Tab
   - Prompts Tab

#### Test 3.2: Pages Tab
1. Click "Pages" tab
2. **Verify**:
   - ✅ List of content pages displays (should show ~21 pages)
   - ✅ Search/filter functionality works
   - ✅ Each page shows: title, URL, status
   - ✅ "View" or "Edit" button for each page
3. **Test page view**:
   - Click "View" on any page
   - Should show page details
   - Should have "Edit" button

#### Test 3.3: Generations Tab
1. Click "Generations" tab
2. **Verify**:
   - ✅ List of content generations displays
   - ✅ Shows: generation status, date, tokens used
   - ✅ "View" button for each generation
3. **Test generation view**:
   - Click "View" on any generation
   - Should show generated content
   - Should show tokens used
   - Should show generation time

#### Test 3.4: Create New Content Generation ⚠️ CRITICAL TEST
1. Click "New Generation" or "Create" button
2. **Verify form contains**:
   - ✅ Page selector (dropdown)
   - ✅ Prompt selector (dropdown)
   - ✅ Additional instructions (textarea)
   - ✅ Execution mode (sync/async radio or dropdown)
3. **Test content generation**:
   - Select a page from dropdown
   - Select a prompt (e.g., "blog-article")
   - Add instructions: "Test content generation from browser"
   - Select "sync" execution mode
   - Click "Generate" button
4. **Expected behavior**:
   - Should show loading indicator
   - Should take 10-20 seconds (AI generation)
   - Should redirect to generation details page
   - Should show generated content (800+ characters)
   - Should show tokens used (~1000+ tokens)
5. **Verify**:
   - ✅ Content generated successfully
   - ✅ Content is in correct language (English or Italian based on prompt)
   - ✅ Content has proper structure (title, sections, conclusion)
   - ✅ No errors in console
   - ✅ Tokens tracked correctly

**CRITICAL**: If this test fails, report:
- Error message shown
- HTTP status code (check Network tab)
- Console errors
- Database errors (if visible)

#### Test 3.5: Prompts Tab
1. Click "Prompts" tab
2. **Verify**:
   - ✅ List of prompts displays (should show 4 prompts)
   - ✅ Each prompt shows: name, description, type
   - ✅ System prompts marked differently (if applicable)
3. **Test prompt view**:
   - Click "View" on any prompt
   - Should show prompt template
   - Should show variables (e.g., {{keyword}})

---

### Phase 4: Campaign Generator (30 minutes)

#### Test 4.1: Campaigns List Page
1. Navigate to `/dashboard/campaigns`
2. **Verify**:
   - ✅ Page loads without errors
   - ✅ "New Campaign" button visible
   - ✅ List of existing campaigns (should show 5 campaigns)
   - ✅ Filter options visible (campaign type, status)
   - ✅ Each campaign shows: name, type (RSA/PMAX), status, date

#### Test 4.2: Campaign Filters
1. **Test campaign type filter**:
   - Select "RSA" → Should show only RSA campaigns
   - Select "PMAX" → Should show only PMAX campaigns
   - Select "All" → Should show all campaigns
2. **Test status filter**:
   - Select "Completed" → Should show only completed
   - Select "Draft" → Should show drafts
   - Select "Failed" → Should show failed campaigns

#### Test 4.3: View Existing Campaign
1. Click "View" on any existing campaign
2. **Verify campaign details page shows**:
   - ✅ Campaign name
   - ✅ Campaign type badge (RSA or PMAX)
   - ✅ Business description
   - ✅ Target keywords
   - ✅ Generated assets section
3. **For RSA campaigns, verify**:
   - ✅ 15 titles displayed (each max 30 characters)
   - ✅ 4 descriptions displayed (each max 90 characters)
   - ✅ Quality score badge (e.g., 7.5/10)
   - ✅ Tokens used displayed
   - ✅ Character count per asset
4. **For PMAX campaigns, verify**:
   - ✅ 5 short titles (max 30 chars)
   - ✅ 5 long titles (max 90 chars)
   - ✅ 5 descriptions (max 90 chars)
   - ✅ Quality score badge
5. **Test action buttons**:
   - ✅ "Copy to Clipboard" button (test one title)
   - ✅ "Export CSV" button (should download file)
   - ✅ "Export JSON" button (should download file)
   - ✅ "Delete Campaign" button (with confirmation)
   - ✅ "Back to Campaigns" link

#### Test 4.4: Create New Campaign ⚠️ CRITICAL TEST
1. Click "New Campaign" button
2. **Verify form page loads** (`/dashboard/campaigns/create`)
3. **Verify form contains**:
   - ✅ Campaign Name field
   - ✅ Campaign Type selector (RSA/PMAX radio buttons or dropdown)
   - ✅ Business Description textarea
   - ✅ Target Keywords field
   - ✅ URL field (optional)
4. **Test RSA Campaign Creation**:
   - Campaign Name: "Browser Test RSA Campaign"
   - Type: RSA
   - Business Description: "Professional digital marketing agency specializing in SEO, web design, and content creation"
   - Keywords: "digital marketing, SEO services, web design"
   - Click "Generate Campaign" button
5. **Expected behavior**:
   - Should show loading indicator
   - Should take 15-25 seconds (OpenAI API call)
   - Should redirect to campaign details page
   - Should show success message: "Campaign created successfully! AI generated X assets."
6. **Verify generated RSA campaign**:
   - ✅ Should have exactly 15 titles
   - ✅ All titles max 30 characters
   - ✅ Should have 4 descriptions
   - ✅ All descriptions max 90 characters
   - ✅ Quality score displayed (between 6-10)
   - ✅ Tokens used displayed (~400-600 tokens)
   - ✅ Model used: gpt-4o-mini
7. **Test PMAX Campaign Creation**:
   - Repeat above but select "PMAX" type
   - Same business description and keywords
8. **Verify generated PMAX campaign**:
   - ✅ Should have 5 short titles (max 30 chars)
   - ✅ Should have 5 long titles (max 90 chars)
   - ✅ Should have 5 descriptions (max 90 chars)
   - ✅ Quality score displayed
   - ✅ Tokens used displayed (~400-600 tokens)

**CRITICAL**: If campaign generation fails, report:
- Error message
- Network errors (check DevTools Network tab)
- Console errors
- Whether campaign was created but assets failed
- HTTP response status

#### Test 4.5: Delete Campaign
1. From campaigns list, click delete on a test campaign
2. **Verify**:
   - ✅ Confirmation dialog appears
   - ✅ After confirmation, campaign deleted
   - ✅ Success message displayed
   - ✅ Campaign removed from list

---

### Phase 5: Token Tracking (10 minutes)

#### Test 5.1: Token Counter Visibility
1. After login, check if token counter is visible
2. **Should display**:
   - Current tokens used
   - Token limit (if applicable)
   - Visual indicator (progress bar or number)

#### Test 5.2: Token Tracking Accuracy
1. **Before generating content/campaign**:
   - Note current token count (e.g., 7309 tokens)
2. **Generate new content or campaign**
3. **After generation**:
   - Check new token count
   - Should have increased by the amount shown in generation details
4. **Verify**:
   - ✅ Tokens tracked accurately
   - ✅ Counter updates after generation
   - ✅ Amount matches generation details

---

### Phase 6: Super Admin Dashboard (30 minutes)

#### Test 6.1: Logout and Login as Admin
1. Logout from tenant account
2. Login with super admin credentials (`admin@ainstein.com`)
3. **Expected**:
   - Should redirect to `/admin` (NOT `/admin/dashboard`)

#### Test 6.2: Admin Dashboard Overview
1. **Verify page displays**:
   - ✅ Admin navigation menu with:
     - Dashboard
     - Users
     - Tenants
     - Settings
   - ✅ Admin email displayed (admin@ainstein.com)
   - ✅ Logout button
   - ✅ Stats/cards (if present)
2. **Check for errors**:
   - ✅ No console errors
   - ✅ No missing images or broken assets

#### Test 6.3: Users Management
1. Navigate to `/admin/users`
2. **Verify**:
   - ✅ Users list displays (should show 3 users)
   - ✅ Shows: name, email, tenant, role, status
   - ✅ "Add User" button (if exists)
   - ✅ Edit/Delete buttons per user
3. **Test user view/edit** (if available):
   - Click edit on a user
   - Should show edit form
   - DO NOT make changes (just verify form loads)

#### Test 6.4: Tenants Management
1. Navigate to `/admin/tenants`
2. **Verify**:
   - ✅ Tenants list displays (should show 1 tenant: "Demo Company")
   - ✅ Shows: name, status, users count, tokens used, subscription
   - ✅ "Add Tenant" button (if exists)
   - ✅ Edit/Reset buttons per tenant
3. **Test tenant view**:
   - Click view/edit on "Demo Company"
   - Should show tenant details
   - Should show token usage
   - DO NOT reset tokens or make changes

#### Test 6.5: Platform Settings (ALL 6 TABS) ⚠️ CRITICAL
1. Navigate to `/admin/settings`
2. **Verify all 6 tabs exist**:
   - OAuth
   - OpenAI
   - Stripe
   - Email
   - Branding
   - Advanced

##### Tab 1: OAuth Settings
1. Click "OAuth" tab
2. **Verify fields present**:
   - ✅ Google Ads Client ID
   - ✅ Google Ads Client Secret
   - ✅ Facebook Client ID
   - ✅ Facebook Client Secret
   - ✅ Google Console API Key
   - ✅ Save button
3. **DO NOT save** (just verify fields exist)

##### Tab 2: OpenAI Settings
1. Click "OpenAI" tab
2. **Verify fields present**:
   - ✅ OpenAI API Key (password/hidden field)
   - ✅ Model selector (dropdown)
   - ✅ Temperature slider/input
   - ✅ Max Tokens input
   - ✅ Test Connection button (if exists)
   - ✅ Save button
3. **Test OpenAI connection** (if button exists):
   - Click "Test Connection"
   - Should show success/failure message

##### Tab 3: Stripe Settings
1. Click "Stripe" tab
2. **Verify fields present**:
   - ✅ Stripe Public Key
   - ✅ Stripe Secret Key
   - ✅ Test Mode toggle/checkbox
   - ✅ Save button

##### Tab 4: Email/SMTP Settings
1. Click "Email" tab
2. **Verify fields present**:
   - ✅ SMTP Host
   - ✅ SMTP Port
   - ✅ SMTP Username
   - ✅ SMTP Password
   - ✅ From Address
   - ✅ From Name
   - ✅ Test Email button (if exists)
   - ✅ Save button

##### Tab 5: Branding/Logo Settings
1. Click "Branding" or "Logo" tab
2. **Verify fields present**:
   - ✅ Platform Name field
   - ✅ Logo Upload button/area
   - ✅ Current logo preview (if uploaded)
   - ✅ Delete logo button (if logo exists)
   - ✅ Save button
3. **Test logo upload** (optional):
   - Upload a small PNG image
   - Click Save
   - Verify logo appears in preview
   - **THEN VERIFY**: Logo appears in tenant header after logout/login

##### Tab 6: Advanced Settings
1. Click "Advanced" tab
2. **Verify fields present**:
   - ✅ Maintenance Mode toggle
   - ✅ Platform Description textarea
   - ✅ Other advanced options
   - ✅ Save button
3. **Test maintenance mode** (if safe):
   - Enable maintenance mode
   - Save
   - Logout and try to access tenant dashboard
   - Should show maintenance message
   - **IMPORTANT**: Re-login as admin and DISABLE maintenance mode

---

### Phase 7: Settings Sync Verification (15 minutes)

#### Test 7.1: Logo Sync
1. As admin, upload a logo in Settings → Branding
2. Logout
3. Login as tenant user
4. **Verify**:
   - ✅ Logo appears in tenant header/navigation
   - ✅ Logo appears on login page

#### Test 7.2: OAuth Buttons Sync
1. As admin, configure Google OAuth (fake credentials OK)
2. Save settings
3. Logout
4. **Verify on login page**:
   - ✅ "Sign in with Google" button appears (if implemented)

#### Test 7.3: OpenAI Configuration
1. Verify OpenAI is configured in admin settings
2. Test by creating a new campaign or content as tenant
3. **Verify**:
   - ✅ AI generation works
   - ✅ Uses correct model (gpt-4o-mini)

---

### Phase 8: Error Handling & Edge Cases (20 minutes)

#### Test 8.1: Form Validation
1. **Test empty form submissions**:
   - Campaign creation with empty fields → Should show validation errors
   - Content generation with no page selected → Should show error
   - Admin settings with invalid email format → Should show error
2. **Verify**:
   - ✅ Validation messages appear
   - ✅ Fields highlighted in red/error state
   - ✅ Form doesn't submit with errors

#### Test 8.2: Navigation Edge Cases
1. **Test super admin accessing tenant routes**:
   - Login as admin
   - Try to access `/dashboard/content`
   - **Expected**: Should redirect to `/admin` with warning message
2. **Test tenant accessing admin routes**:
   - Login as tenant
   - Try to access `/admin/settings`
   - **Expected**: Should redirect to login or show 403 error

#### Test 8.3: Invalid Routes
1. Try accessing:
   - `/dashboard/fake-page` → Should show 404
   - `/admin/nonexistent` → Should show 404
2. **Verify**:
   - ✅ Custom 404 page displays (or Laravel default)
   - ✅ No server errors (500)

#### Test 8.4: Session Expiration
1. Login and stay idle for 5+ minutes
2. Try to perform an action (create campaign, generate content)
3. **Expected**:
   - Should redirect to login
   - Should show session expired message

---

### Phase 9: Browser Console & Network Errors (Continuous)

**Throughout ALL testing**, keep browser DevTools open and monitor:

#### Console Tab
- ✅ No JavaScript errors (red messages)
- ✅ No warning messages (yellow messages)
- ⚠️ Exception: Warning about duplicate keys in React/Vue is usually OK

#### Network Tab
- ✅ No failed HTTP requests (status 400, 500)
- ✅ All assets load (CSS, JS, images)
- ✅ No 404 errors for resources
- ✅ API calls return 200 or expected status codes

#### Performance
- ✅ Pages load in under 3 seconds
- ✅ Forms submit without long delays (except AI generation)
- ✅ No memory leaks (check Memory tab if issues)

---

### Phase 10: Responsive Design (Optional - 15 minutes)

If time permits, test on different screen sizes:

1. **Desktop** (1920x1080):
   - Everything should display correctly
2. **Tablet** (768x1024):
   - Navigation should collapse to hamburger menu
   - Tables should be scrollable
3. **Mobile** (375x667):
   - All content should be readable
   - Forms should be usable
   - Buttons should be tappable

---

## Bug Reporting Format

For each bug found, report using this format:

```markdown
### BUG #[NUMBER]: [Short Description]

**Severity**: [CRITICAL / HIGH / MEDIUM / LOW]

**Location**: [URL where bug occurs]

**Steps to Reproduce**:
1. Step 1
2. Step 2
3. Step 3

**Expected Behavior**:
[What should happen]

**Actual Behavior**:
[What actually happens]

**Error Messages**:
[Copy any error messages]

**Console Errors**:
[Copy JavaScript console errors]

**Network Errors**:
[HTTP status codes, failed requests]

**Screenshot**:
[Take screenshot of error]

**Browser Info**:
- Browser: [Chrome/Firefox/Safari]
- Version: [Version number]
- OS: [Windows/Mac/Linux]

**Additional Context**:
[Any other relevant information]
```

---

## Known Issues (Expected to Find)

These issues are already documented:

1. **Password Reset Page** - Returns HTTP 500 error
   - URL: `/password/reset`
   - Status: Known bug, not yet fixed
   - Priority: MEDIUM

---

## Success Criteria

The platform is considered **PASS** if:
- ✅ Login/logout works
- ✅ All navigation links work
- ✅ Content Generator creates content with AI
- ✅ Campaign Generator creates campaigns with AI
- ✅ Token tracking works accurately
- ✅ Super Admin can access all settings
- ✅ All 6 settings tabs load
- ✅ No console errors during normal usage
- ✅ Forms validate correctly
- ✅ No broken images or 404 errors

Minor issues (styling, UX suggestions) can be noted but don't fail the test.

---

## Final Report Structure

After completing all tests, provide a summary report:

```markdown
# Ainstein Platform - Test Results

**Test Date**: [Date]
**Test Duration**: [X hours]
**Tester**: AI Agent via Comet Browser

## Summary
- **Total Tests**: [Number]
- **Passed**: [Number]
- **Failed**: [Number]
- **Bugs Found**: [Number]

## Critical Bugs (Block Production)
1. [List critical bugs]

## High Priority Bugs (Should Fix Before Launch)
1. [List high priority bugs]

## Medium/Low Priority Bugs (Can Fix Later)
1. [List medium/low bugs]

## Features Tested Successfully
✅ [List all working features]

## Recommendations
[Any UX improvements or suggestions]

## Overall Assessment
[READY FOR PRODUCTION / NEEDS FIXES / MAJOR ISSUES]
```

---

## Important Notes

1. **DO NOT delete real data** - Only create test campaigns/content, don't delete existing ones unless they're your test data
2. **DO NOT modify admin settings** unless specifically testing that feature (and note what you changed)
3. **Take screenshots** of every error you find
4. **Document everything** - even minor issues
5. **Test methodically** - Don't skip steps
6. **Check console continuously** - Many issues show up there first

---

## Time Estimate

- Phase 1 (Auth): 10 min
- Phase 2 (Dashboard): 15 min
- Phase 3 (Content Generator): 30 min
- Phase 4 (Campaign Generator): 30 min
- Phase 5 (Token Tracking): 10 min
- Phase 6 (Super Admin): 30 min
- Phase 7 (Settings Sync): 15 min
- Phase 8 (Edge Cases): 20 min
- Phase 9 (Console): Continuous
- Phase 10 (Responsive): 15 min (optional)

**Total**: ~2.5 hours for complete testing

---

## Good Luck! 🚀

Report back with any issues you find. The development team is ready to fix bugs based on your findings.
