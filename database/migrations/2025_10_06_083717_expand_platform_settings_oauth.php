<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Check existing columns to avoid duplicates
            // openai_api_key, stripe_secret_key, smtp_host, smtp_port already exist
            // google_ads_*, facebook_*, google_console_* already exist

            // OpenAI Configuration (additional fields)
            if (!Schema::hasColumn('platform_settings', 'openai_organization_id')) {
                $table->string('openai_organization_id')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'openai_default_model')) {
                $table->string('openai_default_model')->default('gpt-4o-mini');
            }
            if (!Schema::hasColumn('platform_settings', 'openai_max_tokens')) {
                $table->integer('openai_max_tokens')->default(2000);
            }
            if (!Schema::hasColumn('platform_settings', 'openai_temperature')) {
                $table->decimal('openai_temperature', 2, 1)->default(0.7);
            }

            // Stripe Configuration (additional fields)
            if (!Schema::hasColumn('platform_settings', 'stripe_public_key')) {
                $table->text('stripe_public_key')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'stripe_webhook_secret')) {
                $table->text('stripe_webhook_secret')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'stripe_test_mode')) {
                $table->boolean('stripe_test_mode')->default(true);
            }

            // Email SMTP Configuration (additional fields)
            if (!Schema::hasColumn('platform_settings', 'smtp_username')) {
                $table->string('smtp_username')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'smtp_password')) {
                $table->text('smtp_password')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'smtp_encryption')) {
                $table->string('smtp_encryption')->default('tls');
            }
            if (!Schema::hasColumn('platform_settings', 'mail_from_address')) {
                $table->string('mail_from_address')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'mail_from_name')) {
                $table->string('mail_from_name')->nullable();
            }

            // Cache Configuration
            if (!Schema::hasColumn('platform_settings', 'cache_driver')) {
                $table->string('cache_driver')->default('redis');
            }
            if (!Schema::hasColumn('platform_settings', 'cache_default_ttl')) {
                $table->integer('cache_default_ttl')->default(3600);
            }

            // Queue Configuration
            if (!Schema::hasColumn('platform_settings', 'queue_driver')) {
                $table->string('queue_driver')->default('redis');
            }
            if (!Schema::hasColumn('platform_settings', 'queue_retry_after')) {
                $table->integer('queue_retry_after')->default(90);
            }
            if (!Schema::hasColumn('platform_settings', 'queue_max_tries')) {
                $table->integer('queue_max_tries')->default(3);
            }

            // Rate Limiting
            if (!Schema::hasColumn('platform_settings', 'rate_limit_per_minute')) {
                $table->integer('rate_limit_per_minute')->default(60);
            }
            if (!Schema::hasColumn('platform_settings', 'rate_limit_ai_per_hour')) {
                $table->integer('rate_limit_ai_per_hour')->default(100);
            }

            // Feature Flags
            if (!Schema::hasColumn('platform_settings', 'feature_flags')) {
                $table->json('feature_flags')->nullable();
            }

            // Logo & Branding
            if (!Schema::hasColumn('platform_settings', 'platform_logo_path')) {
                $table->string('platform_logo_path')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'platform_logo_small_path')) {
                $table->string('platform_logo_small_path')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'platform_favicon_path')) {
                $table->string('platform_favicon_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
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
