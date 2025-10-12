<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Social login credentials (for user authentication)
            $table->string('google_social_client_id')->nullable()->after('google_client_secret');
            $table->string('google_social_client_secret')->nullable()->after('google_social_client_id');
            $table->string('facebook_social_app_id')->nullable()->after('facebook_client_secret');
            $table->string('facebook_social_app_secret')->nullable()->after('facebook_social_app_id');

            // Rename existing fields for clarity (these are for API integrations)
            $table->renameColumn('google_client_id', 'google_ads_client_id');
            $table->renameColumn('google_client_secret', 'google_ads_client_secret');
            $table->renameColumn('facebook_client_id', 'facebook_app_id');
            $table->renameColumn('facebook_client_secret', 'facebook_app_secret');

            // Add Google Search Console API fields
            $table->string('google_console_client_id')->nullable()->after('google_ads_client_secret');
            $table->string('google_console_client_secret')->nullable()->after('google_console_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn([
                'google_social_client_id',
                'google_social_client_secret',
                'facebook_social_app_id',
                'facebook_social_app_secret',
                'google_console_client_id',
                'google_console_client_secret'
            ]);

            // Restore original column names
            $table->renameColumn('google_ads_client_id', 'google_client_id');
            $table->renameColumn('google_ads_client_secret', 'google_client_secret');
            $table->renameColumn('facebook_app_id', 'facebook_client_id');
            $table->renameColumn('facebook_app_secret', 'facebook_client_secret');
        });
    }
};