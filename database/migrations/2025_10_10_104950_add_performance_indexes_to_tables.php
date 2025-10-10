<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE OPTIMIZATION - STEP 7
     * Adds missing indexes identified in performance analysis
     * Expected impact: 40-60% faster queries with WHERE/ORDER BY clauses
     */
    public function up(): void
    {
        // Pages table indexes - for filtering and sorting optimization
        Schema::table('pages', function (Blueprint $table) {
            // Composite index for category filtering within tenant
            $table->index(['tenant_id', 'category'], 'pages_tenant_category_index');

            // Composite index for language filtering within tenant
            $table->index(['tenant_id', 'language'], 'pages_tenant_language_index');

            // Composite index for created_at sorting within tenant (dashboard recent pages)
            $table->index(['tenant_id', 'created_at'], 'pages_tenant_created_index');

            // Single column index for category GROUP BY queries
            $table->index('category', 'pages_category_index');
        });

        // Content generations table indexes - for analytics and filtering
        Schema::table('content_generations', function (Blueprint $table) {
            // Composite index for created_at sorting within tenant (dashboard trends)
            $table->index(['tenant_id', 'created_at'], 'generations_tenant_created_index');

            // Composite index for prompt_type filtering within tenant
            $table->index(['tenant_id', 'prompt_type'], 'generations_tenant_prompt_type_index');

            // Composite index for completed_at queries by status (performance analytics)
            $table->index(['status', 'completed_at'], 'generations_status_completed_index');

            // Composite index for date-based queries with status filtering
            $table->index(['created_at', 'status'], 'generations_created_status_index');
        });

        // Adv campaigns table indexes - for campaign filtering
        Schema::table('adv_campaigns', function (Blueprint $table) {
            // Composite index for campaign type filtering within tenant
            $table->index(['tenant_id', 'type'], 'campaigns_tenant_type_index');

            // Composite index for created_at sorting within tenant
            $table->index(['tenant_id', 'created_at'], 'campaigns_tenant_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_tenant_category_index');
            $table->dropIndex('pages_tenant_language_index');
            $table->dropIndex('pages_tenant_created_index');
            $table->dropIndex('pages_category_index');
        });

        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropIndex('generations_tenant_created_index');
            $table->dropIndex('generations_tenant_prompt_type_index');
            $table->dropIndex('generations_status_completed_index');
            $table->dropIndex('generations_created_status_index');
        });

        Schema::table('adv_campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_tenant_type_index');
            $table->dropIndex('campaigns_tenant_created_index');
        });
    }
};
