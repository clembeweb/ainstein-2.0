<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentGenerationController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PromptController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\TenantPageController;
use App\Http\Controllers\TenantApiKeyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API version prefix
Route::prefix('v1')->group(function () {

    // Public authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
        Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');

        // Password Reset API routes
        Route::post('/password/email', [PasswordResetController::class, 'apiSendResetLinkEmail'])->name('api.password.email');
        Route::post('/password/reset', [PasswordResetController::class, 'apiReset'])->name('api.password.reset');

        // Social Authentication API routes
        Route::get('/{provider}', [SocialAuthController::class, 'apiRedirectToProvider'])->name('api.social.redirect')
            ->where('provider', 'google|facebook');
        Route::post('/{provider}/callback', [SocialAuthController::class, 'apiHandleProviderCallback'])->name('api.social.callback')
            ->where('provider', 'google|facebook');
    });

    // Protected routes requiring authentication
    Route::middleware(['auth:sanctum'])->group(function () {

        // Authentication routes (authenticated)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
            Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        });

        // Tenant management routes
        Route::apiResource('tenants', TenantController::class);

        // Page management routes (tenant-scoped)
        Route::apiResource('pages', PageController::class);

        // Prompt management routes (tenant-scoped)
        Route::apiResource('prompts', PromptController::class);

        // Content generation routes (tenant-scoped)
        Route::apiResource('content-generations', ContentGenerationController::class, [
            'parameters' => [
                'content-generations' => 'contentGeneration'
            ]
        ]);

        // Tenant-specific routes with tenant isolation middleware
        Route::middleware([\App\Http\Middleware\EnsureTenantAccess::class])->prefix('tenant')->group(function () {
            // Dashboard and Analytics
            Route::get('/dashboard', function (Request $request) {
                return app(\App\Http\Controllers\TenantDashboardController::class)->index();
            })->name('api.tenant.dashboard');

            Route::get('/analytics', function (Request $request) {
                return app(\App\Http\Controllers\TenantDashboardController::class)->analytics($request);
            })->name('api.tenant.analytics');

            // Pages API endpoints
            Route::get('/pages', [TenantPageController::class, 'index'])->name('api.tenant.pages.index');
            Route::post('/pages', [TenantPageController::class, 'store'])->name('api.tenant.pages.store');
            Route::get('/pages/{page}', [TenantPageController::class, 'show'])->name('api.tenant.pages.show');
            Route::put('/pages/{page}', [TenantPageController::class, 'update'])->name('api.tenant.pages.update');
            Route::delete('/pages/{page}', [TenantPageController::class, 'destroy'])->name('api.tenant.pages.destroy');
            Route::patch('/pages/bulk-status', [TenantPageController::class, 'bulkUpdateStatus'])->name('api.tenant.pages.bulk-status');
            Route::delete('/pages/bulk-delete', [TenantPageController::class, 'bulkDelete'])->name('api.tenant.pages.bulk-delete');

            // API Keys endpoints
            Route::get('/api-keys', [TenantApiKeyController::class, 'index'])->name('api.tenant.api-keys.index');
            Route::post('/api-keys/generate', [TenantApiKeyController::class, 'generate'])->name('api.tenant.api-keys.generate');
            Route::get('/api-keys/{apiKey}', [TenantApiKeyController::class, 'show'])->name('api.tenant.api-keys.show');
            Route::put('/api-keys/{apiKey}', [TenantApiKeyController::class, 'update'])->name('api.tenant.api-keys.update');
            Route::patch('/api-keys/{apiKey}/revoke', [TenantApiKeyController::class, 'revoke'])->name('api.tenant.api-keys.revoke');
            Route::patch('/api-keys/{apiKey}/activate', [TenantApiKeyController::class, 'activate'])->name('api.tenant.api-keys.activate');
            Route::delete('/api-keys/{apiKey}', [TenantApiKeyController::class, 'destroy'])->name('api.tenant.api-keys.destroy');
            Route::get('/api-keys/{apiKey}/usage', [TenantApiKeyController::class, 'usage'])->name('api.tenant.api-keys.usage');
        });

        // Additional utility routes
        Route::prefix('utils')->group(function () {

            // Get current user's tenant information
            Route::get('/tenant', function (Request $request) {
                $user = $request->user();
                $tenant = $user->tenant()->with([
                    'users:id,name,email,role,tenant_id',
                    'pages:id,url_path,keyword,status,tenant_id',
                    'prompts:id,name,category,is_active,tenant_id'
                ])->first();

                return response()->json([
                    'data' => $tenant
                ]);
            })->name('api.utils.tenant');

            // Get statistics for current tenant
            Route::get('/stats', function (Request $request) {
                $user = $request->user();
                $tenantId = $user->tenant_id;

                $stats = [
                    'pages_count' => \App\Models\Page::where('tenant_id', $tenantId)->count(),
                    'prompts_count' => \App\Models\Prompt::where('tenant_id', $tenantId)->count(),
                    'generations_count' => \App\Models\ContentGeneration::where('tenant_id', $tenantId)->count(),
                    'tokens_used_this_month' => \App\Models\ContentGeneration::where('tenant_id', $tenantId)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->sum('tokens_used'),
                    'recent_generations' => \App\Models\ContentGeneration::where('tenant_id', $tenantId)
                        ->with(['page:id,url_path,keyword'])
                        ->latest()
                        ->limit(5)
                        ->get(['id', 'page_id', 'prompt_type', 'status', 'created_at']),
                ];

                return response()->json([
                    'data' => $stats
                ]);
            })->name('api.utils.stats');

            // Health check endpoint
            Route::get('/health', function () {
                return response()->json([
                    'status' => 'healthy',
                    'timestamp' => now()->toISOString(),
                    'version' => '1.0.0'
                ]);
            })->name('api.utils.health');

        });

        // Super admin only routes
        Route::middleware(['can:admin-access'])->group(function () {

            // System-wide statistics (super admin only)
            Route::get('/admin/stats', function () {
                $stats = [
                    'total_tenants' => \App\Models\Tenant::count(),
                    'active_tenants' => \App\Models\Tenant::where('status', 'active')->count(),
                    'total_users' => \App\Models\User::count(),
                    'active_users' => \App\Models\User::where('is_active', true)->count(),
                    'total_pages' => \App\Models\Page::count(),
                    'total_generations' => \App\Models\ContentGeneration::count(),
                    'total_tokens_used' => \App\Models\ContentGeneration::sum('tokens_used'),
                    'tokens_this_month' => \App\Models\ContentGeneration::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->sum('tokens_used'),
                ];

                return response()->json([
                    'data' => $stats
                ]);
            })->name('api.admin.stats');

            // System prompts management
            Route::prefix('admin/system-prompts')->group(function () {
                Route::get('/', function (Request $request) {
                    $prompts = \App\Models\Prompt::where('is_system', true)
                        ->paginate($request->get('per_page', 15));

                    return response()->json([
                        'data' => $prompts->items(),
                        'meta' => [
                            'current_page' => $prompts->currentPage(),
                            'last_page' => $prompts->lastPage(),
                            'per_page' => $prompts->perPage(),
                            'total' => $prompts->total(),
                        ]
                    ]);
                });
            });

        });

    });

    // Catch-all route for undefined API endpoints
    Route::fallback(function () {
        return response()->json([
            'message' => 'API endpoint not found. Please check the documentation for available endpoints.',
            'error' => 'Not Found'
        ], 404);
    });

});

// Public health check endpoint (no authentication required)
Route::get('/health', function () {
    try {
        // Basic application health checks
        $checks = [
            'database' => false,
            'redis' => false,
            'storage' => false,
        ];

        // Database check
        try {
            \DB::connection()->getPdo();
            $checks['database'] = true;
        } catch (\Exception $e) {
            // Database connection failed
        }

        // Redis check (if configured)
        try {
            if (config('cache.default') === 'redis') {
                \Cache::store('redis')->get('health-check');
                $checks['redis'] = true;
            } else {
                $checks['redis'] = 'n/a';
            }
        } catch (\Exception $e) {
            // Redis connection failed
        }

        // Storage check
        try {
            \Storage::disk('local')->exists('.');
            $checks['storage'] = true;
        } catch (\Exception $e) {
            // Storage access failed
        }

        $healthy = $checks['database'] && ($checks['redis'] === true || $checks['redis'] === 'n/a') && $checks['storage'];

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'checks' => $checks,
            'uptime' => now()->diffInSeconds(\Carbon\Carbon::createFromTimestamp(LARAVEL_START ?? time()))
        ], $healthy ? 200 : 503);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => now()->toISOString(),
            'error' => 'Health check failed'
        ], 503);
    }
})->name('api.health');

// Root API information (no versioning)
Route::get('/', function () {
    return response()->json([
        'name' => 'Ainstein API',
        'version' => '1.0.0',
        'documentation' => url('/api/docs'),
        'endpoints' => [
            'auth' => url('/api/v1/auth'),
            'tenants' => url('/api/v1/tenants'),
            'pages' => url('/api/v1/pages'),
            'prompts' => url('/api/v1/prompts'),
            'content_generations' => url('/api/v1/content-generations'),
            'tenant_pages' => url('/api/v1/tenant/pages'),
            'tenant_api_keys' => url('/api/v1/tenant/api-keys'),
            'tenant_dashboard' => url('/api/v1/tenant/dashboard'),
        ]
    ]);
})->name('api.root');