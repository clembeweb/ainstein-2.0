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
            $table->text('facebook_client_id')->nullable()->after('google_client_secret');
            $table->text('facebook_client_secret')->nullable()->after('facebook_client_id');
            $table->string('platform_name')->default('Ainstein Platform')->after('facebook_client_secret');
            $table->text('platform_description')->nullable()->after('platform_name');
            $table->boolean('maintenance_mode')->default(false)->after('platform_description');
            $table->string('default_plan_id')->nullable()->after('maintenance_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_client_id',
                'facebook_client_secret',
                'platform_name',
                'platform_description',
                'maintenance_mode',
                'default_plan_id'
            ]);
        });
    }
};
