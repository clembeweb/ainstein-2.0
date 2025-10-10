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
        Schema::create('seo_ai_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUlid('audit_id')->constrained('seo_audits')->onDelete('cascade');

            // AI Provider Info
            $table->string('provider', 50); // openai, anthropic, azure, etc.
            $table->string('model', 100); // gpt-4o, claude-3-5-sonnet, etc.
            $table->string('prompt_template', 100)->nullable(); // Reference to template used

            // Report Content (Markdown)
            $table->longText('executive_summary')->nullable();
            $table->longText('prioritized_actions')->nullable();
            $table->longText('quick_wins')->nullable();
            $table->longText('risks_dependencies')->nullable();
            $table->longText('long_term_recommendations')->nullable();

            // Metadata
            $table->integer('tokens_input')->nullable();
            $table->integer('tokens_output')->nullable();
            $table->integer('tokens_total')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->integer('generation_duration_ms')->nullable();

            // Status
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('audit_id');
            $table->index('provider');
            $table->index('status');
            $table->index(['audit_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_ai_reports');
    }
};
