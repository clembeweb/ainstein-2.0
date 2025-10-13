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
            // Google Ads API Integration (for advertising tools)
            if (!Schema::hasColumn('platform_settings', 'google_ads_client_id')) {
                $table->text('google_ads_client_id')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'google_ads_client_secret')) {
                $table->text('google_ads_client_secret')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'google_ads_refresh_token')) {
                $table->text('google_ads_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'google_ads_token_expires_at')) {
                $table->timestamp('google_ads_token_expires_at')->nullable();
            }

            // Facebook Ads API Integration (for advertising tools)
            if (!Schema::hasColumn('platform_settings', 'facebook_app_id')) {
                $table->text('facebook_app_id')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'facebook_app_secret')) {
                $table->text('facebook_app_secret')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'facebook_access_token')) {
                $table->text('facebook_access_token')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'facebook_token_expires_at')) {
                $table->timestamp('facebook_token_expires_at')->nullable();
            }

            // Google Search Console API Integration (for SEO tools)
            if (!Schema::hasColumn('platform_settings', 'google_console_client_id')) {
                $table->text('google_console_client_id')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'google_console_client_secret')) {
                $table->text('google_console_client_secret')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'google_console_refresh_token')) {
                $table->text('google_console_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('platform_settings', 'google_console_token_expires_at')) {
                $table->timestamp('google_console_token_expires_at')->nullable();
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
                'google_ads_client_id',
                'google_ads_client_secret',
                'google_ads_refresh_token',
                'google_ads_token_expires_at',
                'facebook_app_id',
                'facebook_app_secret',
                'facebook_access_token',
                'facebook_token_expires_at',
                'google_console_client_id',
                'google_console_client_secret',
                'google_console_refresh_token',
                'google_console_token_expires_at',
            ]);
        });
    }
};
