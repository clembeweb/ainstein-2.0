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
        Schema::create('seo_issues', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUlid('audit_id')->constrained('seo_audits')->onDelete('cascade');
            $table->foreignUlid('page_id')->nullable()->constrained('seo_pages')->onDelete('cascade'); // Null for global issues

            // Issue Info
            $table->string('issue_code', 100); // Es: TITLE_MISSING, HTTP_4XX, etc.
            $table->enum('severity', ['ERROR', 'WARN', 'INFO']);
            $table->string('category', 50)->nullable(); // HTTP, META, CANONICAL, LINKS, etc.
            $table->text('message'); // Human-readable description
            $table->json('evidence')->nullable(); // Technical details, URLs, snippets

            // Occurrence Tracking
            $table->integer('occurrence_count')->default(1); // For aggregated issues
            $table->timestamp('first_detected_at');
            $table->timestamp('last_detected_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('audit_id');
            $table->index('page_id');
            $table->index('issue_code');
            $table->index('severity');
            $table->index('category');
            $table->index(['audit_id', 'severity']);
            $table->index(['audit_id', 'issue_code']);
            $table->index(['page_id', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_issues');
    }
};
