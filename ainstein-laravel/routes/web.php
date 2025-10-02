<?php

use App\Http\Controllers\TenantDashboardController;
use App\Http\Controllers\TenantPageController;
use App\Http\Controllers\TenantApiKeyController;
use App\Http\Controllers\TenantPromptController;
use App\Http\Controllers\TenantContentController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Landing page
Route::get('/', function () {
    return view('landing');
})->middleware(\App\Http\Middleware\CheckMaintenanceMode::class);

// Authentication routes
Route::get('/login', [App\Http\Controllers\Auth\AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::get('/register', [App\Http\Controllers\Auth\AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');

// Password Reset routes
Route::prefix('password')->group(function () {
    Route::get('/reset', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset', [PasswordResetController::class, 'reset'])->name('password.update');
});

// Email Verification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->name('verification.send');
});
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');

// Social Authentication routes
Route::prefix('auth')->group(function () {
    Route::get('/{provider}', [SocialAuthController::class, 'redirectToProvider'])->name('social.redirect')
        ->where('provider', 'google|facebook');
    Route::get('/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])->name('social.callback')
        ->where('provider', 'google|facebook');
});

// Direct dashboard route (without prefix) - redirects to tenant dashboard
Route::get('/dashboard', [TenantDashboardController::class, 'index'])->middleware(['auth', \App\Http\Middleware\CheckMaintenanceMode::class, \App\Http\Middleware\EnsureTenantAccess::class])->name('dashboard');

// Tenant Dashboard Routes
Route::middleware(['auth', \App\Http\Middleware\CheckMaintenanceMode::class, \App\Http\Middleware\EnsureTenantAccess::class])->prefix('dashboard')->name('tenant.')->group(function () {
    // Dashboard
    Route::get('/', [TenantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [TenantDashboardController::class, 'analytics'])->name('analytics');

    // Pages Management
    Route::get('/pages', [TenantPageController::class, 'index'])->name('pages.index');
    Route::get('/pages/create', [TenantPageController::class, 'create'])->name('pages.create');
    Route::post('/pages', [TenantPageController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}', [TenantPageController::class, 'show'])->name('pages.show');
    Route::get('/pages/{page}/edit', [TenantPageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [TenantPageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [TenantPageController::class, 'destroy'])->name('pages.destroy');
    Route::patch('/pages/bulk-status', [TenantPageController::class, 'bulkUpdateStatus'])->name('pages.bulk-status');
    Route::delete('/pages/bulk-delete', [TenantPageController::class, 'bulkDelete'])->name('pages.bulk-delete');

    // Prompts Management
    Route::get('/prompts', [TenantPromptController::class, 'index'])->name('prompts.index');
    Route::get('/prompts/create', [TenantPromptController::class, 'create'])->name('prompts.create');
    Route::post('/prompts', [TenantPromptController::class, 'store'])->name('prompts.store');
    Route::get('/prompts/{prompt}', [TenantPromptController::class, 'show'])->name('prompts.show');
    Route::get('/prompts/{prompt}/edit', [TenantPromptController::class, 'edit'])->name('prompts.edit');
    Route::put('/prompts/{prompt}', [TenantPromptController::class, 'update'])->name('prompts.update');
    Route::delete('/prompts/{prompt}', [TenantPromptController::class, 'destroy'])->name('prompts.destroy');
    Route::post('/prompts/{prompt}/duplicate', [TenantPromptController::class, 'duplicate'])->name('prompts.duplicate');

    // Content Generation
    Route::get('/content/create', [TenantContentController::class, 'create'])->name('content.create');
    Route::post('/content', [TenantContentController::class, 'store'])->name('content.store');
    Route::get('/content', [TenantContentController::class, 'index'])->name('content.index');
    Route::get('/content/{generation}', [TenantContentController::class, 'show'])->name('content.show');
    Route::get('/prompts/{prompt}/details', [TenantContentController::class, 'getPromptDetails'])->name('prompts.details');

    // API Keys Management
    Route::get('/api-keys', [TenantApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys/generate', [TenantApiKeyController::class, 'generate'])->name('api-keys.generate');
    Route::get('/api-keys/{apiKey}', [TenantApiKeyController::class, 'show'])->name('api-keys.show');
    Route::put('/api-keys/{apiKey}', [TenantApiKeyController::class, 'update'])->name('api-keys.update');
    Route::patch('/api-keys/{apiKey}/revoke', [TenantApiKeyController::class, 'revoke'])->name('api-keys.revoke');
    Route::patch('/api-keys/{apiKey}/activate', [TenantApiKeyController::class, 'activate'])->name('api-keys.activate');
    Route::delete('/api-keys/{apiKey}', [TenantApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    Route::get('/api-keys/{apiKey}/usage', [TenantApiKeyController::class, 'usage'])->name('api-keys.usage');

    // Legacy dashboard routes (for backward compatibility)
    Route::get('/pages-old', [TenantDashboardController::class, 'pages'])->name('pages-old');
    // Route::get('/prompts', [TenantDashboardController::class, 'prompts'])->name('prompts'); // DISABLED - conflicts with prompts.index
    Route::get('/generations', [TenantDashboardController::class, 'contentGenerations'])->name('generations');
    Route::get('/generations/{generation}', [TenantDashboardController::class, 'showGeneration'])->name('generation.show');
    Route::get('/settings', [TenantDashboardController::class, 'settings'])->name('settings');

    // Legacy API key routes (for backward compatibility)
    Route::get('/api-keys-old', [TenantDashboardController::class, 'apiKeys'])->name('api-keys-old');
    Route::post('/api-keys-old', [TenantDashboardController::class, 'createApiKey'])->name('api-keys.create-old');
    Route::patch('/api-keys-old/{key}/revoke', [TenantDashboardController::class, 'revokeApiKey'])->name('api-keys.revoke-old');

    // Onboarding routes
    Route::post('/onboarding/complete', [\App\Http\Controllers\OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/reset', [\App\Http\Controllers\OnboardingController::class, 'reset'])->name('onboarding.reset');
});

// API Documentation
Route::get('/api/docs', function () {
    return view('api.docs');
})->name('api.docs');

// Public routes
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
})->name('health');

// Maintenance mode check for all routes
Route::middleware(\App\Http\Middleware\CheckMaintenanceMode::class)->group(function () {
    // All routes are wrapped in maintenance mode check
});
