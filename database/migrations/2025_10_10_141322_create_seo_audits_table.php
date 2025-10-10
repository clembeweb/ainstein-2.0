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
        Schema::create('seo_audits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUlid('project_id')->constrained('seo_projects')->onDelete('cascade');

            // Status
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Configuration Snapshot (config del progetto al momento dell'esecuzione)
            $table->json('config_snapshot')->nullable();

            // Aggregate Metrics
            $table->integer('pages_crawled')->default(0);
            $table->integer('pages_indexable')->default(0);
            $table->integer('pages_non_indexable')->default(0);
            $table->integer('orphan_pages')->default(0);

            // Issues Count by Severity
            $table->integer('issues_total')->default(0);
            $table->integer('issues_error')->default(0);
            $table->integer('issues_warn')->default(0);
            $table->integer('issues_info')->default(0);

            // Link & Resource Metrics
            $table->integer('broken_internal_links')->default(0);
            $table->integer('broken_external_links')->default(0);
            $table->integer('broken_images')->default(0);

            // Performance Metrics
            $table->integer('avg_load_time_ms')->nullable();
            $table->integer('avg_page_size_bytes')->nullable();
            $table->decimal('avg_depth', 5, 2)->nullable();

            // Site Health Score
            $table->decimal('health_score', 5, 2)->nullable(); // 0.00 - 100.00
            $table->decimal('health_score_previous', 5, 2)->nullable();
            $table->decimal('health_score_delta', 6, 2)->nullable(); // Can be negative

            // Sitemap Stats
            $table->integer('sitemap_entries_found')->default(0);
            $table->integer('sitemap_entries_valid')->default(0);

            // Error Info (if failed)
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('project_id');
            $table->index('status');
            $table->index('started_at');
            $table->index('finished_at');
            $table->index('health_score');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_audits');
    }
};
