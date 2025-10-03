# 🔧 Admin Settings Centralization & OAuth Integration

**Status**: 📋 Specification
**Priority**: P0 CRITICAL
**Fase**: 1 - Platform Base
**Estimated Time**: 8-12 hours

---

## 🎯 OBIETTIVO

**Centralizzare TUTTE le configurazioni nella pagina Super Admin Settings eliminando OGNI valore hardcoded nel codice.**

### Principio Fondamentale
> **Zero hardcoded values** - Qualsiasi configurazione che normalmente metteresti nel `.env` o hardcoded nel codice DEVE essere gestibile dalla UI admin.

---

## 📋 REQUISITI

### 1. OAuth Integrations
- ✅ Google OAuth (già implementato - email/password)
- 🆕 Google Ads OAuth (client_id, client_secret)
- 🆕 Facebook OAuth (app_id, app_secret)
- 🆕 Google Search Console OAuth (client_id, client_secret)

### 2. Logo Upload
- 🆕 Upload logo piattaforma (branding tenant)
- 🆕 Validazione immagini (JPEG, PNG, SVG)
- 🆕 Resize automatico (multiple sizes)
- 🆕 Storage S3/local configurabile

### 3. Configuration Centralization
- 🆕 OpenAI API Key (attualmente hardcoded)
- 🆕 Stripe Keys (public/secret)
- 🆕 Email SMTP settings
- 🆕 Cache settings
- 🆕 Queue settings
- 🆕 Rate limiting thresholds
- 🆕 Feature flags

---

## 🗄️ DATABASE SCHEMA

### Migration 1: Expand platform_settings table

```php
// database/migrations/2025_10_03_XXXXXX_expand_platform_settings_oauth.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Google Ads OAuth
            $table->string('google_ads_client_id')->nullable()->after('value');
            $table->text('google_ads_client_secret')->nullable();
            $table->text('google_ads_refresh_token')->nullable();
            $table->timestamp('google_ads_token_expires_at')->nullable();

            // Facebook OAuth
            $table->string('facebook_app_id')->nullable();
            $table->text('facebook_app_secret')->nullable();
            $table->text('facebook_access_token')->nullable();
            $table->timestamp('facebook_token_expires_at')->nullable();

            // Google Search Console OAuth
            $table->string('google_console_client_id')->nullable();
            $table->text('google_console_client_secret')->nullable();
            $table->text('google_console_refresh_token')->nullable();
            $table->timestamp('google_console_token_expires_at')->nullable();

            // OpenAI Configuration
            $table->text('openai_api_key')->nullable();
            $table->string('openai_organization_id')->nullable();
            $table->string('openai_default_model')->default('gpt-4o-mini');
            $table->integer('openai_max_tokens')->default(2000);
            $table->decimal('openai_temperature', 2, 1)->default(0.7);

            // Stripe Configuration
            $table->text('stripe_public_key')->nullable();
            $table->text('stripe_secret_key')->nullable();
            $table->text('stripe_webhook_secret')->nullable();
            $table->boolean('stripe_test_mode')->default(true);

            // Email SMTP Configuration
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption')->default('tls');
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();

            // Cache Configuration
            $table->string('cache_driver')->default('redis');
            $table->integer('cache_default_ttl')->default(3600); // seconds

            // Queue Configuration
            $table->string('queue_driver')->default('redis');
            $table->integer('queue_retry_after')->default(90); // seconds
            $table->integer('queue_max_tries')->default(3);

            // Rate Limiting
            $table->integer('rate_limit_per_minute')->default(60);
            $table->integer('rate_limit_ai_per_hour')->default(100);

            // Feature Flags (JSON)
            $table->json('feature_flags')->nullable();

            // Logo & Branding
            $table->string('platform_logo_path')->nullable();
            $table->string('platform_logo_small_path')->nullable(); // 64x64
            $table->string('platform_favicon_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'google_ads_client_id', 'google_ads_client_secret', 'google_ads_refresh_token', 'google_ads_token_expires_at',
                'facebook_app_id', 'facebook_app_secret', 'facebook_access_token', 'facebook_token_expires_at',
                'google_console_client_id', 'google_console_client_secret', 'google_console_refresh_token', 'google_console_token_expires_at',
                'openai_api_key', 'openai_organization_id', 'openai_default_model', 'openai_max_tokens', 'openai_temperature',
                'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_test_mode',
                'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'mail_from_address', 'mail_from_name',
                'cache_driver', 'cache_default_ttl',
                'queue_driver', 'queue_retry_after', 'queue_max_tries',
                'rate_limit_per_minute', 'rate_limit_ai_per_hour',
                'feature_flags',
                'platform_logo_path', 'platform_logo_small_path', 'platform_favicon_path',
            ]);
        });
    }
};
```

---

## 🏗️ MODEL

### app/Models/PlatformSetting.php (Enhancement)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'description', 'is_encrypted',
        // OAuth fields
        'google_ads_client_id', 'google_ads_client_secret', 'google_ads_refresh_token', 'google_ads_token_expires_at',
        'facebook_app_id', 'facebook_app_secret', 'facebook_access_token', 'facebook_token_expires_at',
        'google_console_client_id', 'google_console_client_secret', 'google_console_refresh_token', 'google_console_token_expires_at',
        // OpenAI
        'openai_api_key', 'openai_organization_id', 'openai_default_model', 'openai_max_tokens', 'openai_temperature',
        // Stripe
        'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_test_mode',
        // Email
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'mail_from_address', 'mail_from_name',
        // Cache & Queue
        'cache_driver', 'cache_default_ttl', 'queue_driver', 'queue_retry_after', 'queue_max_tries',
        // Rate Limiting
        'rate_limit_per_minute', 'rate_limit_ai_per_hour',
        // Feature Flags
        'feature_flags',
        // Logo
        'platform_logo_path', 'platform_logo_small_path', 'platform_favicon_path',
    ];

    protected $casts = [
        'google_ads_token_expires_at' => 'datetime',
        'facebook_token_expires_at' => 'datetime',
        'google_console_token_expires_at' => 'datetime',
        'openai_max_tokens' => 'integer',
        'openai_temperature' => 'float',
        'stripe_test_mode' => 'boolean',
        'smtp_port' => 'integer',
        'cache_default_ttl' => 'integer',
        'queue_retry_after' => 'integer',
        'queue_max_tries' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_ai_per_hour' => 'integer',
        'feature_flags' => 'array',
        'is_encrypted' => 'boolean',
    ];

    // Encrypted fields
    protected $encrypted = [
        'google_ads_client_secret',
        'google_ads_refresh_token',
        'facebook_app_secret',
        'facebook_access_token',
        'google_console_client_secret',
        'google_console_refresh_token',
        'openai_api_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'smtp_password',
    ];

    /**
     * Get setting value with caching
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("platform_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::first();
            if (!$setting) {
                return $default;
            }

            $value = $setting->$key ?? $default;

            // Decrypt if needed
            if (in_array($key, (new self)->encrypted) && $value !== null) {
                try {
                    return Crypt::decryptString($value);
                } catch (\Exception $e) {
                    \Log::error("Failed to decrypt setting: {$key}");
                    return $default;
                }
            }

            return $value;
        });
    }

    /**
     * Set setting value
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = self::firstOrCreate([]);

        // Encrypt if needed
        if (in_array($key, (new self)->encrypted) && $value !== null) {
            $value = Crypt::encryptString($value);
        }

        $setting->update([$key => $value]);

        // Clear cache
        Cache::forget("platform_setting_{$key}");
    }

    /**
     * Check if Google Ads is configured
     */
    public static function isGoogleAdsConfigured(): bool
    {
        $clientId = self::get('google_ads_client_id');
        $clientSecret = self::get('google_ads_client_secret');
        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Check if Facebook is configured
     */
    public static function isFacebookConfigured(): bool
    {
        $appId = self::get('facebook_app_id');
        $appSecret = self::get('facebook_app_secret');
        return !empty($appId) && !empty($appSecret);
    }

    /**
     * Check if Google Search Console is configured
     */
    public static function isGoogleConsoleConfigured(): bool
    {
        $clientId = self::get('google_console_client_id');
        $clientSecret = self::get('google_console_client_secret');
        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Check if OpenAI is configured
     */
    public static function isOpenAiConfigured(): bool
    {
        return !empty(self::get('openai_api_key'));
    }

    /**
     * Check if Stripe is configured
     */
    public static function isStripeConfigured(): bool
    {
        $publicKey = self::get('stripe_public_key');
        $secretKey = self::get('stripe_secret_key');
        return !empty($publicKey) && !empty($secretKey);
    }

    /**
     * Get feature flag value
     */
    public static function featureEnabled(string $feature): bool
    {
        $flags = self::get('feature_flags', []);
        return $flags[$feature] ?? false;
    }
}
```

---

## 🎨 CONTROLLER

### app/Http/Controllers/Admin/PlatformSettingsController.php (Enhancement)

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class PlatformSettingsController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::first() ?? new PlatformSetting();

        return view('admin.settings.index', [
            'settings' => $settings,
            'googleAdsConfigured' => PlatformSetting::isGoogleAdsConfigured(),
            'facebookConfigured' => PlatformSetting::isFacebookConfigured(),
            'googleConsoleConfigured' => PlatformSetting::isGoogleConsoleConfigured(),
            'openAiConfigured' => PlatformSetting::isOpenAiConfigured(),
            'stripeConfigured' => PlatformSetting::isStripeConfigured(),
        ]);
    }

    public function updateOAuth(Request $request)
    {
        $request->validate([
            'google_ads_client_id' => 'nullable|string',
            'google_ads_client_secret' => 'nullable|string',
            'facebook_app_id' => 'nullable|string',
            'facebook_app_secret' => 'nullable|string',
            'google_console_client_id' => 'nullable|string',
            'google_console_client_secret' => 'nullable|string',
        ]);

        $setting = PlatformSetting::firstOrCreate([]);
        $setting->update($request->only([
            'google_ads_client_id', 'google_ads_client_secret',
            'facebook_app_id', 'facebook_app_secret',
            'google_console_client_id', 'google_console_client_secret',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'OAuth settings updated successfully!');
    }

    public function updateOpenAI(Request $request)
    {
        $request->validate([
            'openai_api_key' => 'required|string',
            'openai_organization_id' => 'nullable|string',
            'openai_default_model' => 'required|in:gpt-4,gpt-4o,gpt-4o-mini,gpt-3.5-turbo',
            'openai_max_tokens' => 'required|integer|min:100|max:4000',
            'openai_temperature' => 'required|numeric|min:0|max:2',
        ]);

        $setting = PlatformSetting::firstOrCreate([]);
        $setting->update($request->only([
            'openai_api_key', 'openai_organization_id', 'openai_default_model',
            'openai_max_tokens', 'openai_temperature',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'OpenAI settings updated successfully!');
    }

    public function updateStripe(Request $request)
    {
        $request->validate([
            'stripe_public_key' => 'required|string',
            'stripe_secret_key' => 'required|string',
            'stripe_webhook_secret' => 'nullable|string',
            'stripe_test_mode' => 'boolean',
        ]);

        $setting = PlatformSetting::firstOrCreate([]);
        $setting->update($request->only([
            'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret', 'stripe_test_mode',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'Stripe settings updated successfully!');
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string',
            'smtp_password' => 'required|string',
            'smtp_encryption' => 'required|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        $setting = PlatformSetting::firstOrCreate([]);
        $setting->update($request->only([
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
            'smtp_encryption', 'mail_from_address', 'mail_from_name',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'Email settings updated successfully!');
    }

    public function updateAdvanced(Request $request)
    {
        $request->validate([
            'cache_driver' => 'required|in:redis,file,database',
            'cache_default_ttl' => 'required|integer|min:60',
            'queue_driver' => 'required|in:redis,database,sync',
            'queue_retry_after' => 'required|integer|min:30',
            'queue_max_tries' => 'required|integer|min:1|max:10',
            'rate_limit_per_minute' => 'required|integer|min:10|max:1000',
            'rate_limit_ai_per_hour' => 'required|integer|min:10|max:10000',
        ]);

        $setting = PlatformSetting::firstOrCreate([]);
        $setting->update($request->only([
            'cache_driver', 'cache_default_ttl', 'queue_driver',
            'queue_retry_after', 'queue_max_tries',
            'rate_limit_per_minute', 'rate_limit_ai_per_hour',
        ]));

        return redirect()->route('admin.settings.index')
            ->with('success', 'Advanced settings updated successfully!');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,svg|max:2048', // 2MB max
        ]);

        $setting = PlatformSetting::firstOrCreate([]);

        // Delete old logos if exist
        if ($setting->platform_logo_path) {
            Storage::disk('public')->delete($setting->platform_logo_path);
        }
        if ($setting->platform_logo_small_path) {
            Storage::disk('public')->delete($setting->platform_logo_small_path);
        }

        // Store original logo
        $logoPath = $request->file('logo')->store('logos', 'public');

        // Create small version (64x64 for favicon/navbar)
        $image = Image::make(Storage::disk('public')->path($logoPath));
        $image->resize(64, 64, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $smallPath = 'logos/small_' . basename($logoPath);
        $image->save(Storage::disk('public')->path($smallPath));

        // Create favicon (32x32)
        $favicon = Image::make(Storage::disk('public')->path($logoPath));
        $favicon->resize(32, 32, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $faviconPath = 'logos/favicon_' . basename($logoPath);
        $favicon->save(Storage::disk('public')->path($faviconPath));

        // Update database
        $setting->update([
            'platform_logo_path' => $logoPath,
            'platform_logo_small_path' => $smallPath,
            'platform_favicon_path' => $faviconPath,
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Logo uploaded successfully!');
    }

    public function deleteLogo()
    {
        $setting = PlatformSetting::first();

        if ($setting && $setting->platform_logo_path) {
            Storage::disk('public')->delete([
                $setting->platform_logo_path,
                $setting->platform_logo_small_path,
                $setting->platform_favicon_path,
            ]);

            $setting->update([
                'platform_logo_path' => null,
                'platform_logo_small_path' => null,
                'platform_favicon_path' => null,
            ]);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Logo deleted successfully!');
    }

    public function testOpenAI()
    {
        try {
            $apiKey = PlatformSetting::get('openai_api_key');

            if (empty($apiKey)) {
                return response()->json(['success' => false, 'message' => 'OpenAI API key not configured']);
            }

            // Simple test request
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "API connection successful"']
                ],
                'max_tokens' => 10,
            ]);

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'OpenAI API connection successful!']);
            }

            return response()->json(['success' => false, 'message' => 'API connection failed: ' . $response->body()]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function testStripe()
    {
        try {
            $secretKey = PlatformSetting::get('stripe_secret_key');

            if (empty($secretKey)) {
                return response()->json(['success' => false, 'message' => 'Stripe secret key not configured']);
            }

            \Stripe\Stripe::setApiKey($secretKey);
            $account = \Stripe\Account::retrieve();

            return response()->json(['success' => true, 'message' => 'Stripe connection successful! Account: ' . $account->email]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
```

---

## 🛣️ ROUTES

### routes/web.php (Add)

```php
// Super Admin Settings (Protected by SuperAdminMiddleware)
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/settings', [PlatformSettingsController::class, 'index'])->name('settings.index');

    // OAuth Settings
    Route::post('/settings/oauth', [PlatformSettingsController::class, 'updateOAuth'])->name('settings.oauth.update');

    // OpenAI Settings
    Route::post('/settings/openai', [PlatformSettingsController::class, 'updateOpenAI'])->name('settings.openai.update');
    Route::post('/settings/openai/test', [PlatformSettingsController::class, 'testOpenAI'])->name('settings.openai.test');

    // Stripe Settings
    Route::post('/settings/stripe', [PlatformSettingsController::class, 'updateStripe'])->name('settings.stripe.update');
    Route::post('/settings/stripe/test', [PlatformSettingsController::class, 'testStripe'])->name('settings.stripe.test');

    // Email Settings
    Route::post('/settings/email', [PlatformSettingsController::class, 'updateEmail'])->name('settings.email.update');

    // Advanced Settings
    Route::post('/settings/advanced', [PlatformSettingsController::class, 'updateAdvanced'])->name('settings.advanced.update');

    // Logo Upload
    Route::post('/settings/logo', [PlatformSettingsController::class, 'uploadLogo'])->name('settings.logo.upload');
    Route::delete('/settings/logo', [PlatformSettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
});
```

---

## 🎨 VIEW

### resources/views/admin/settings/index.blade.php

```blade
@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Platform Settings</h1>
            <p class="text-gray-600 mt-2">Manage all platform configurations from this central location</p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
        @endif

        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 mb-6" x-data="{ activeTab: 'oauth' }">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'oauth'"
                        :class="activeTab === 'oauth' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    OAuth Integrations
                </button>
                <button @click="activeTab = 'openai'"
                        :class="activeTab === 'openai' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    OpenAI Configuration
                </button>
                <button @click="activeTab = 'stripe'"
                        :class="activeTab === 'stripe' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Stripe Billing
                </button>
                <button @click="activeTab = 'email'"
                        :class="activeTab === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Email SMTP
                </button>
                <button @click="activeTab = 'branding'"
                        :class="activeTab === 'branding' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Logo & Branding
                </button>
                <button @click="activeTab = 'advanced'"
                        :class="activeTab === 'advanced' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Advanced
                </button>
            </nav>

            <!-- OAuth Integrations Tab -->
            <div x-show="activeTab === 'oauth'" class="mt-6">
                <form action="{{ route('admin.settings.oauth.update') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Google Ads -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Google Ads OAuth</h3>
                            @if($googleAdsConfigured)
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">✓ Configured</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600">Not configured</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                                <input type="text" name="google_ads_client_id"
                                       value="{{ old('google_ads_client_id', $settings->google_ads_client_id) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="123456789-xxxxx.apps.googleusercontent.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client Secret</label>
                                <input type="password" name="google_ads_client_secret"
                                       value="{{ old('google_ads_client_secret') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="GOCSPX-xxxxxxxxxxxxx">
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-2">
                            Get credentials from <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 underline">Google Cloud Console</a>
                        </p>
                    </div>

                    <!-- Facebook -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Facebook OAuth</h3>
                            @if($facebookConfigured)
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">✓ Configured</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600">Not configured</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">App ID</label>
                                <input type="text" name="facebook_app_id"
                                       value="{{ old('facebook_app_id', $settings->facebook_app_id) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="1234567890123456">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">App Secret</label>
                                <input type="password" name="facebook_app_secret"
                                       value="{{ old('facebook_app_secret') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-2">
                            Get credentials from <a href="https://developers.facebook.com/apps" target="_blank" class="text-blue-600 underline">Facebook Developers</a>
                        </p>
                    </div>

                    <!-- Google Search Console -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Google Search Console OAuth</h3>
                            @if($googleConsoleConfigured)
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">✓ Configured</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600">Not configured</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                                <input type="text" name="google_console_client_id"
                                       value="{{ old('google_console_client_id', $settings->google_console_client_id) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="123456789-xxxxx.apps.googleusercontent.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client Secret</label>
                                <input type="password" name="google_console_client_secret"
                                       value="{{ old('google_console_client_secret') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="GOCSPX-xxxxxxxxxxxxx">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Save OAuth Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- OpenAI Configuration Tab -->
            <div x-show="activeTab === 'openai'" class="mt-6">
                <form action="{{ route('admin.settings.openai.update') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">OpenAI API Configuration</h3>
                            @if($openAiConfigured)
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">✓ Configured</span>
                                    <button type="button" onclick="testOpenAI()" class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Test Connection
                                    </button>
                                </div>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">⚠ Required</span>
                            @endif
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key *</label>
                                <input type="password" name="openai_api_key"
                                       value="{{ old('openai_api_key') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="sk-proj-xxxxxxxxxxxxx" required>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Organization ID (Optional)</label>
                                    <input type="text" name="openai_organization_id"
                                           value="{{ old('openai_organization_id', $settings->openai_organization_id) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="org-xxxxxxxxxxxxx">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Model *</label>
                                    <select name="openai_default_model" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <option value="gpt-4o" {{ $settings->openai_default_model == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Recommended)</option>
                                        <option value="gpt-4o-mini" {{ $settings->openai_default_model == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Fast)</option>
                                        <option value="gpt-4" {{ $settings->openai_default_model == 'gpt-4' ? 'selected' : '' }}>GPT-4</option>
                                        <option value="gpt-3.5-turbo" {{ $settings->openai_default_model == 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Tokens *</label>
                                    <input type="number" name="openai_max_tokens"
                                           value="{{ old('openai_max_tokens', $settings->openai_max_tokens ?? 2000) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           min="100" max="4000" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Temperature *</label>
                                    <input type="number" name="openai_temperature"
                                           value="{{ old('openai_temperature', $settings->openai_temperature ?? 0.7) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           min="0" max="2" step="0.1" required>
                                    <p class="text-xs text-gray-500 mt-1">0 = Deterministic, 2 = Very Creative</p>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-4">
                            Get API key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 underline">OpenAI Platform</a>
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Save OpenAI Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Logo & Branding Tab -->
            <div x-show="activeTab === 'branding'" class="mt-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Logo</h3>

                    @if($settings->platform_logo_path)
                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2">Current Logo:</p>
                        <div class="flex items-center space-x-4">
                            <img src="{{ Storage::url($settings->platform_logo_path) }}" alt="Platform Logo" class="h-16 w-auto border border-gray-200 rounded">
                            <form action="{{ route('admin.settings.logo.delete') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                    Delete Logo
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('admin.settings.logo.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Logo</label>
                            <input type="file" name="logo" accept="image/jpeg,image/png,image/svg+xml"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <p class="text-xs text-gray-500 mt-2">Supported formats: JPEG, PNG, SVG (Max 2MB). Recommended size: 256x256px or larger.</p>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Upload Logo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add other tabs (Stripe, Email, Advanced) similarly... -->
        </div>
    </div>
</div>

<script>
function testOpenAI() {
    fetch('{{ route("admin.settings.openai.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => {
        alert('Error testing OpenAI connection');
    });
}
</script>
@endsection
```

---

## 🔧 SERVICE REFACTORING

### app/Services/OpenAiService.php (Refactor to use settings)

```php
<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    protected string $apiKey;
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;

    public function __construct()
    {
        // Load from database instead of .env
        $this->apiKey = PlatformSetting::get('openai_api_key');
        $this->model = PlatformSetting::get('openai_default_model', 'gpt-4o-mini');
        $this->maxTokens = PlatformSetting::get('openai_max_tokens', 2000);
        $this->temperature = PlatformSetting::get('openai_temperature', 0.7);

        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key not configured in platform settings');
        }
    }

    public function generateContent(string $prompt, array $options = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful AI content generator.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
        ]);

        if ($response->failed()) {
            Log::error('OpenAI API Error', ['response' => $response->body()]);
            throw new \Exception('OpenAI API request failed');
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
        ];
    }
}
```

---

## ✅ IMPLEMENTATION CHECKLIST

### Phase 1: Database & Models (2 hours)
- [ ] Create migration `expand_platform_settings_oauth.php`
- [ ] Run migration: `php artisan migrate`
- [ ] Update `PlatformSetting` model with new fields
- [ ] Add encryption methods for secrets
- [ ] Test model methods: `isGoogleAdsConfigured()`, `isOpenAiConfigured()`, etc.

### Phase 2: Controller & Routes (2 hours)
- [ ] Update `PlatformSettingsController` with new methods
- [ ] Add routes for OAuth, OpenAI, Stripe, Email, Logo
- [ ] Test each route individually

### Phase 3: Views (3 hours)
- [ ] Create tabbed settings UI
- [ ] Add OAuth forms (Google Ads, Facebook, GSC)
- [ ] Add OpenAI configuration form
- [ ] Add logo upload form
- [ ] Add Stripe, Email, Advanced tabs
- [ ] Style with Tailwind CSS

### Phase 4: Logo Upload (1.5 hours)
- [ ] Install Intervention/Image: `composer require intervention/image`
- [ ] Configure storage in `config/filesystems.php`
- [ ] Create logo upload controller methods
- [ ] Test upload, resize, favicon generation
- [ ] Test logo deletion

### Phase 5: Service Refactoring (1.5 hours)
- [ ] Refactor `OpenAiService` to use `PlatformSetting::get()`
- [ ] Remove hardcoded API keys from `.env` (keep as fallback)
- [ ] Update all services to use settings
- [ ] Test content generation with new config

### Phase 6: Testing (2 hours)
- [ ] Test OAuth credentials save/load
- [ ] Test OpenAI API test button
- [ ] Test Stripe API test button
- [ ] Test logo upload/delete
- [ ] Test encryption/decryption of secrets
- [ ] Test with empty settings (fallback behavior)

---

## 🎯 SUCCESS CRITERIA

- ✅ Zero hardcoded API keys in codebase
- ✅ All configurations editable via Super Admin UI
- ✅ OAuth credentials stored encrypted
- ✅ Logo upload with automatic resize (256px, 64px, 32px)
- ✅ Test buttons for OpenAI and Stripe connections
- ✅ Settings cached for performance
- ✅ Fallback to `.env` if settings not configured

---

## 📝 NEXT OAUTH FLOWS

After this foundation, implement OAuth flows:

### Google Ads OAuth Flow
```php
Route::get('/auth/google-ads', [GoogleAdsAuthController::class, 'redirect']);
Route::get('/auth/google-ads/callback', [GoogleAdsAuthController::class, 'callback']);
```

### Facebook OAuth Flow
```php
Route::get('/auth/facebook', [FacebookAuthController::class, 'redirect']);
Route::get('/auth/facebook/callback', [FacebookAuthController::class, 'callback']);
```

---

## 🔒 SECURITY NOTES

1. **Encryption**: All secrets encrypted with Laravel's `Crypt` facade
2. **Middleware**: Settings page protected by `SuperAdminMiddleware`
3. **Validation**: All inputs validated before saving
4. **Audit Log**: Consider adding activity log for settings changes
5. **Backup**: Settings should be included in database backups

---

## 📊 PRIORITY

**P0 CRITICAL** - This is foundation for all future development. Without centralized settings, ogni feature richiede hardcoding.

**Estimated Total Time**: 8-12 hours

---

_Created: 2025-10-03_
_Status: Ready for Implementation_
