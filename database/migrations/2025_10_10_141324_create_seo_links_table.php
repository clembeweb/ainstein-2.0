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
        Schema::create('seo_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUlid('audit_id')->constrained('seo_audits')->onDelete('cascade');
            $table->foreignUlid('from_page_id')->constrained('seo_pages')->onDelete('cascade');

            // Link Info
            $table->string('to_url', 2048);
            $table->string('to_url_hash', 64)->index(); // For dedup
            $table->foreignUlid('to_page_id')->nullable()->constrained('seo_pages')->onDelete('set null'); // Null if external
            $table->enum('type', ['internal', 'external', 'mailto', 'tel'])->default('internal');

            // Link Attributes
            $table->text('anchor_text')->nullable();
            $table->string('rel', 200)->nullable(); // nofollow, sponsored, ugc
            $table->boolean('nofollow')->default(false);
            $table->enum('position', ['navigation', 'content', 'footer', 'sidebar', 'other'])->default('other');

            // Target Status (if checked)
            $table->integer('target_status_code')->nullable();
            $table->boolean('is_broken')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('audit_id');
            $table->index('from_page_id');
            $table->index('to_page_id');
            $table->index('type');
            $table->index('is_broken');
            $table->index('nofollow');
            $table->index(['audit_id', 'type']);
            $table->index(['audit_id', 'is_broken']);
            $table->index(['from_page_id', 'to_page_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_links');
    }
};
