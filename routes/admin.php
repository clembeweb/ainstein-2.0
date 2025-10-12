<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Admin Login (separate from tenant login)
Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/admin/logout', [AuthController::class, 'adminLogout'])->name('admin.logout');

// Admin Routes (protected)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Users Management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');

    // Tenants Management
    Route::get('/tenants', [AdminController::class, 'tenants'])->name('tenants');
    Route::get('/tenants/create', [AdminController::class, 'createTenant'])->name('tenants.create');
    Route::post('/tenants', [AdminController::class, 'storeTenant'])->name('tenants.store');
    Route::get('/tenants/{tenant}/edit', [AdminController::class, 'editTenant'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [AdminController::class, 'updateTenant'])->name('tenants.update');
    Route::post('/tenants/{tenant}/reset-tokens', [AdminController::class, 'resetTokens'])->name('tenants.reset-tokens');

    // Platform Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'index'])->name('settings.index');

    // Social Login Settings
    Route::post('/settings/social', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'updateSocialLogin'])->name('settings.social.update');

    // OAuth Settings (API Integrations)
    Route::post('/settings/oauth', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'updateOAuth'])->name('settings.oauth.update');

    // OpenAI Settings
    Route::post('/settings/openai', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'updateOpenAI'])->name('settings.openai.update');
    Route::post('/settings/openai/test', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'testOpenAI'])->name('settings.openai.test');

    // Stripe Settings
    Route::post('/settings/stripe', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'updateStripe'])->name('settings.stripe.update');
    Route::post('/settings/stripe/test', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'testStripe'])->name('settings.stripe.test');

    // Email Settings
    Route::post('/settings/email', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'updateEmail'])->name('settings.email.update');

    // Advanced Settings
    Route::post('/settings/advanced', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'updateAdvanced'])->name('settings.advanced.update');

    // Logo Upload
    Route::post('/settings/logo', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'uploadLogo'])->name('settings.logo.upload');
    Route::delete('/settings/logo', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
});
