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
        Schema::table('cms_connections', function (Blueprint $table) {
            // Add new columns
            $table->string('site_url')->after('name')->nullable();
            $table->enum('status', ['pending', 'active', 'disconnected', 'error'])->default('pending')->after('is_active');
            $table->text('last_error')->nullable()->after('last_sync_at');
            $table->json('sync_config')->nullable()->after('last_error');
            $table->string('created_by')->nullable()->after('sync_config');

            // Add foreign key for created_by
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Add indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_connections', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['site_url', 'status', 'last_error', 'sync_config', 'created_by']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['tenant_id', 'type']);
        });
    }
};
