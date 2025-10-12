# Policy Implementation Guide

**Quick Reference Guide for Implementing Authorization Policies**

---

## Quick Start: Controller Updates

### Pattern to Replace

**BEFORE** (Manual Authorization):
```php
public function show(Request $request, Page $page): JsonResponse
{
    $user = $request->user();

    // ❌ REMOVE: Manual inline authorization
    if (!$user->is_super_admin && $user->tenant_id !== $page->tenant_id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $page->load(['tenant:id,name']);
    return response()->json(['data' => $page]);
}
```

**AFTER** (Policy-Based Authorization):
```php
public function show(Request $request, Page $page): JsonResponse
{
    // ✅ ADD: Single line policy check
    $this->authorize('view', $page);

    $page->load(['tenant:id,name']);
    return response()->json(['data' => $page]);
}
```

---

## Controller Update Examples

### 1. API PageController

**File**: `app/Http/Controllers/Api/PageController.php`

#### show() - Line 87-109
**Replace lines 92-96**:
```php
// REMOVE THESE LINES:
if (!$user->is_super_admin && $user->tenant_id !== $page->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only view pages from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('view', $page);
```

#### update() - Line 114-132
**Replace lines 119-123**:
```php
// REMOVE THESE LINES:
if (!$user->is_super_admin && $user->tenant_id !== $page->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only update pages from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('update', $page);
```

#### destroy() - Line 137-160
**Replace lines 142-146**:
```php
// REMOVE THESE LINES:
if (!$user->is_super_admin && $user->tenant_id !== $page->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only delete pages from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('delete', $page);
```

---

### 2. API PromptController

**File**: `app/Http/Controllers/Api/PromptController.php`

#### show() - Line 89-108
**Replace lines 95-100**:
```php
// REMOVE THESE LINES:
if (!$prompt->is_system &&
    !$user->is_super_admin &&
    $user->tenant_id !== $prompt->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only view prompts from your tenant or system prompts.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('view', $prompt);
```

#### update() - Line 113-144
**Replace lines 118-131**:
```php
// REMOVE THESE LINES:
if ($prompt->is_system && !$user->is_super_admin) {
    return response()->json([
        'message' => 'Unauthorized. System prompts can only be modified by super admins.'
    ], 403);
}

if (!$prompt->is_system &&
    !$user->is_super_admin &&
    $user->tenant_id !== $prompt->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only update prompts from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('update', $prompt);
```

#### destroy() - Line 149-172
**Replace lines 161-165**:
```php
// REMOVE THESE LINES:
if (!$user->is_super_admin && $user->tenant_id !== $prompt->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only delete prompts from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('delete', $prompt);
```

---

### 3. API ContentGenerationController

**File**: `app/Http/Controllers/Api/ContentGenerationController.php`

#### show() - Line 97-116
**Replace lines 102-106**:
```php
// REMOVE THESE LINES:
if (!$user->is_super_admin && $user->tenant_id !== $generation->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only view content generations from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('view', $generation);
```

#### update() - Line 121-146
**Replace lines 126-130**:
```php
// REMOVE THESE LINES:
if (!$user->is_super_admin && $user->tenant_id !== $generation->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only update content generations from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('update', $generation);
```

#### destroy() - Line 151-174
**Replace lines 156-160**:
```php
// REMOVE THESE LINES:
if (!$user->is_super_admin && $user->tenant_id !== $generation->tenant_id) {
    return response()->json([
        'message' => 'Unauthorized. You can only delete content generations from your tenant.'
    ], 403);
}

// ADD THIS LINE:
$this->authorize('delete', $generation);
```

---

### 4. TenantPageController (Web)

**File**: `app/Http/Controllers/TenantPageController.php`

#### show() - Line 228-276
**Replace lines 233-238**:
```php
// REMOVE THESE LINES:
if ($page->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this page');
}

// ADD THIS LINE:
$this->authorize('view', $page);
```

#### edit() - Line 281-304
**Replace lines 286-288**:
```php
// REMOVE THESE LINES:
if ($page->tenant_id !== $user->tenant_id) {
    abort(403, 'Unauthorized access to this page');
}

// ADD THIS LINE:
$this->authorize('update', $page);
```

#### update() - Line 309-401
**Replace lines 314-318**:
```php
// REMOVE THESE LINES:
if ($page->tenant_id !== $user->tenant_id) {
    if ($request->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this page');
}

// ADD THIS LINE:
$this->authorize('update', $page);
```

#### destroy() - Line 406-465
**Replace lines 411-416**:
```php
// REMOVE THESE LINES:
if ($page->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this page');
}

// ADD THIS LINE:
$this->authorize('delete', $page);
```

#### bulkUpdateStatus() - Line 470-527
**ADD at line 487** (after validation):
```php
// ADD THIS LINE:
$this->authorize('bulkAction', Page::class);
```

#### bulkDelete() - Line 532-602
**ADD at line 547** (after validation):
```php
// ADD THIS LINE:
$this->authorize('bulkAction', Page::class);
```

---

### 5. TenantPromptController (Web)

**File**: `app/Http/Controllers/TenantPromptController.php`

#### show() - Line 234-270
**Replace lines 239-244**:
```php
// REMOVE THESE LINES:
if (!$prompt->is_system && $prompt->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this prompt');
}

// ADD THIS LINE:
$this->authorize('view', $prompt);
```

#### edit() - Line 275-297
**Replace lines 280-282**:
```php
// REMOVE THESE LINES:
if ($prompt->is_system || $prompt->tenant_id !== $user->tenant_id) {
    abort(403, 'Unauthorized access to this prompt');
}

// ADD THIS LINE:
$this->authorize('update', $prompt);
```

#### update() - Line 302-393
**Replace lines 307-312**:
```php
// REMOVE THESE LINES:
if ($prompt->is_system || $prompt->tenant_id !== $user->tenant_id) {
    if ($request->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this prompt');
}

// ADD THIS LINE:
$this->authorize('update', $prompt);
```

#### destroy() - Line 398-448
**Replace lines 403-408**:
```php
// REMOVE THESE LINES:
if ($prompt->is_system || $prompt->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this prompt');
}

// ADD THIS LINE:
$this->authorize('delete', $prompt);
```

#### duplicate() - Line 453-508
**Replace lines 458-463**:
```php
// REMOVE THESE LINES:
if (!$prompt->is_system && $prompt->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this prompt');
}

// ADD THIS LINE:
$this->authorize('duplicate', $prompt);
```

---

### 6. TenantApiKeyController (Web)

**File**: `app/Http/Controllers/TenantApiKeyController.php`

#### generate() - Line 107-222
**ADD at line 126** (after validation):
```php
// ADD THIS LINE:
$this->authorize('create', ApiKey::class);
```

#### show() - Line 227-268
**Replace lines 232-237**:
```php
// REMOVE THESE LINES:
if ($apiKey->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this API key');
}

// ADD THIS LINE:
$this->authorize('view', $apiKey);
```

#### update() - Line 273-354
**Replace lines 278-283**:
```php
// REMOVE THESE LINES:
if ($apiKey->tenant_id !== $user->tenant_id) {
    if ($request->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this API key');
}

// ADD THIS LINE:
$this->authorize('update', $apiKey);
```

#### revoke() - Line 359-411
**Replace lines 364-369**:
```php
// REMOVE THESE LINES:
if ($apiKey->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this API key');
}

// ADD THIS LINE:
$this->authorize('revoke', $apiKey);
```

#### activate() - Line 416-481
**Replace lines 421-426**:
```php
// REMOVE THESE LINES:
if ($apiKey->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this API key');
}

// ADD THIS LINE:
$this->authorize('activate', $apiKey);
```

#### destroy() - Line 486-534
**Replace lines 491-496**:
```php
// REMOVE THESE LINES:
if ($apiKey->tenant_id !== $user->tenant_id) {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    abort(403, 'Unauthorized access to this API key');
}

// ADD THIS LINE:
$this->authorize('delete', $apiKey);
```

#### usage() - Line 539-575
**Replace lines 544-546**:
```php
// REMOVE THESE LINES:
if ($apiKey->tenant_id !== $user->tenant_id) {
    return response()->json(['error' => 'Unauthorized'], 403);
}

// ADD THIS LINE:
$this->authorize('viewUsage', $apiKey);
```

---

## Form Request Updates

### Pattern to Replace

**BEFORE** (Inline Authorization):
```php
public function authorize(): bool
{
    $user = $this->user();
    $page = $this->route('page');

    // ❌ REMOVE: Duplicate logic
    return $user && (
        $user->is_super_admin ||
        $user->tenant_id === $page->tenant_id
    );
}
```

**AFTER** (Policy-Based):
```php
public function authorize(): bool
{
    // ✅ ADD: Use policy
    $page = $this->route('page');
    return Gate::allows('update', $page);
}
```

### Form Requests to Update

1. **StorePageRequest** (`app/Http/Requests/Api/StorePageRequest.php`):
```php
// Lines 12-15 - REPLACE
public function authorize(): bool
{
    return Gate::allows('create', \App\Models\Page::class);
}
```

2. **UpdatePageRequest** (`app/Http/Requests/Api/UpdatePageRequest.php`):
```php
// Lines 12-21 - REPLACE
public function authorize(): bool
{
    $page = $this->route('page');
    return Gate::allows('update', $page);
}
```

3. **StorePromptRequest** (`app/Http/Requests/Api/StorePromptRequest.php`):
```php
// Lines 12-15 - REPLACE
public function authorize(): bool
{
    return Gate::allows('create', \App\Models\Prompt::class);
}
```

4. **UpdatePromptRequest** - Similar pattern

5. **StoreContentGenerationRequest** - Similar pattern

6. **UpdateContentGenerationRequest** - Similar pattern

---

## Testing Your Changes

### 1. Unit Test Authorization

```bash
php artisan test --filter=PolicyTest
```

### 2. Test Cross-Tenant Access Prevention

```bash
# In Tinker
php artisan tinker

# Create test data
$tenant1 = Tenant::find('01J9...');
$tenant2 = Tenant::find('01J9...');
$user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
$page2 = Page::factory()->create(['tenant_id' => $tenant2->id]);

# Test authorization
$user1->can('view', $page2); // Should return false
```

### 3. Test API Endpoints

```bash
# Test unauthorized access
curl -X GET http://localhost/api/v1/pages/{other-tenant-page-id} \
  -H "Authorization: Bearer {your-token}"

# Should return 403 Unauthorized
```

---

## Verification Checklist

After implementing policy authorization in each controller:

- [ ] Remove all inline `tenant_id` checks
- [ ] Remove all manual `is_super_admin` checks
- [ ] Add `$this->authorize()` calls at the beginning of each method
- [ ] Ensure error responses are consistent (Laravel handles automatically)
- [ ] Remove redundant `$user = Auth::user()` or `$user = $request->user()` if only used for authorization
- [ ] Test each endpoint with:
  - [ ] Same tenant user (should succeed)
  - [ ] Different tenant user (should fail with 403)
  - [ ] Super admin (should succeed for all tenants)
  - [ ] Member role (should fail for admin-only operations)

---

## Common Issues & Solutions

### Issue: "This action is unauthorized" message

**Cause**: Policy method returning false

**Solution**: Check policy logic:
```php
// In policy
public function view(User $user, Page $page): bool
{
    // Add dd() to debug
    dd([
        'user_tenant' => $user->tenant_id,
        'page_tenant' => $page->tenant_id,
        'is_super_admin' => $user->is_super_admin,
    ]);

    return $user->tenant_id === $page->tenant_id;
}
```

### Issue: Policy not being called

**Cause**: Policy not registered in AuthServiceProvider

**Solution**: Verify registration:
```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    \App\Models\Page::class => \App\Policies\PagePolicy::class,
];
```

### Issue: Super admin access denied

**Cause**: Policy doesn't check `is_super_admin`

**Solution**: Add super admin bypass:
```php
public function view(User $user, Page $page): bool
{
    // Super admin can view all
    if ($user->is_super_admin) {
        return true;
    }

    return $user->tenant_id === $page->tenant_id;
}
```

---

## Quick Reference: Policy Methods

| Controller Action | Policy Method | Model/Class |
|-------------------|---------------|-------------|
| `index()` | `viewAny(User $user)` | `Model::class` |
| `show($model)` | `view(User $user, Model $model)` | `$model` |
| `store()` | `create(User $user)` | `Model::class` |
| `update($model)` | `update(User $user, Model $model)` | `$model` |
| `destroy($model)` | `delete(User $user, Model $model)` | `$model` |
| `bulkDelete()` | `bulkAction(User $user)` | `Model::class` |
| Custom actions | Custom method in policy | `$model` or `Model::class` |

---

## Need Help?

**Policy Not Working?**
1. Clear cache: `php artisan optimize:clear`
2. Check policy registration in `AuthServiceProvider`
3. Verify model and policy class names match exactly
4. Check for typos in policy method names

**Authorization Failing?**
1. Check user has `tenant_id` set
2. Check resource has correct `tenant_id`
3. Verify super_admin flag if needed
4. Add `dd()` statements in policy to debug

**Good Luck with Implementation!**
