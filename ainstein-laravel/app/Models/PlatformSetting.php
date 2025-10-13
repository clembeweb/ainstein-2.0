<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PlatformSetting extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        // OpenAI
        'openai_api_key',
        'openai_model',
        'openai_organization_id',
        'openai_default_model',
        'openai_max_tokens',
        'openai_temperature',
        // Stripe
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook',
        'stripe_webhook_secret',
        'stripe_test_mode',
        // Email SMTP
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_pass',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'mail_from_address',
        'mail_from_name',
        // Google OAuth
        'google_client_id',
        'google_client_secret',
        'google_ads_client_id',
        'google_ads_client_secret',
        'google_ads_refresh_token',
        'google_ads_token_expires_at',
        'google_console_client_id',
        'google_console_client_secret',
        'google_console_refresh_token',
        'google_console_token_expires_at',
        // Facebook OAuth
        'facebook_client_id',
        'facebook_client_secret',
        'facebook_app_id',
        'facebook_app_secret',
        'facebook_access_token',
        'facebook_token_expires_at',
        // Platform
        'platform_name',
        'platform_description',
        'maintenance_mode',
        'default_plan_id',
        // Cache & Queue
        'cache_driver',
        'cache_default_ttl',
        'queue_driver',
        'queue_retry_after',
        'queue_max_tries',
        // Rate Limiting
        'rate_limit_per_minute',
        'rate_limit_ai_per_hour',
        // Feature Flags
        'feature_flags',
        // Logo & Branding
        'platform_logo_path',
        'platform_logo_small_path',
        'platform_favicon_path',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
        'maintenance_mode' => 'boolean',
        'openai_max_tokens' => 'integer',
        'openai_temperature' => 'float',
        'stripe_test_mode' => 'boolean',
        'cache_default_ttl' => 'integer',
        'queue_retry_after' => 'integer',
        'queue_max_tries' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_ai_per_hour' => 'integer',
        'feature_flags' => 'array',
        'google_ads_token_expires_at' => 'datetime',
        'facebook_token_expires_at' => 'datetime',
        'google_console_token_expires_at' => 'datetime',
        // Encrypted fields
        'openai_api_key' => 'encrypted',
        'stripe_secret_key' => 'encrypted',
        'stripe_public_key' => 'encrypted',
        'stripe_webhook_secret' => 'encrypted',
        'google_client_secret' => 'encrypted',
        'google_ads_client_secret' => 'encrypted',
        'google_ads_refresh_token' => 'encrypted',
        'google_console_client_secret' => 'encrypted',
        'google_console_refresh_token' => 'encrypted',
        'facebook_client_secret' => 'encrypted',
        'facebook_app_secret' => 'encrypted',
        'facebook_access_token' => 'encrypted',
        'smtp_pass' => 'encrypted',
        'smtp_password' => 'encrypted',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid();
            }
        });
    }

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

            return $setting->$key ?? $default;
        });
    }

    /**
     * Set setting value
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = self::firstOrCreate(['id' => self::first()?->id ?? Str::ulid()]);
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
     * Check if Google Social Login is configured (for user authentication)
     */
    public static function isGoogleLoginConfigured(): bool
    {
        $clientId = self::get('google_client_id');
        $clientSecret = self::get('google_client_secret');
        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Check if Facebook Social Login is configured (for user authentication)
     */
    public static function isFacebookLoginConfigured(): bool
    {
        $clientId = self::get('facebook_client_id');
        $clientSecret = self::get('facebook_client_secret');
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
