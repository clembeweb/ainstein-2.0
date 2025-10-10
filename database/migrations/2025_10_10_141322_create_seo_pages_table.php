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
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUlid('audit_id')->constrained('seo_audits')->onDelete('cascade');

            // URL & Basic Info
            $table->string('url', 2048); // Normalized URL
            $table->string('url_hash', 64)->index(); // MD5/SHA256 for dedup

            // HTTP Info
            $table->integer('status_code');
            $table->integer('load_time_ms')->nullable();
            $table->integer('size_bytes')->nullable();
            $table->string('content_type', 100)->nullable();
            $table->integer('depth')->default(0); // Click depth from home
            $table->boolean('rendered_js')->default(false);
            $table->string('content_hash', 64)->nullable(); // For duplicate content detection

            // Meta Tags
            $table->string('title', 500)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_robots', 200)->nullable();
            $table->string('canonical', 2048)->nullable();

            // Headers
            $table->string('h1', 500)->nullable();
            $table->string('h2_first', 500)->nullable();

            // Open Graph
            $table->string('og_title', 500)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image', 2048)->nullable();
            $table->string('og_type', 100)->nullable();

            // Twitter Card
            $table->string('twitter_card', 100)->nullable();
            $table->string('twitter_title', 500)->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 2048)->nullable();

            // Structured Data
            $table->json('schema_types')->nullable(); // Array of detected schema types

            // Link & Resource Counts
            $table->integer('internal_links_count')->default(0);
            $table->integer('external_links_count')->default(0);
            $table->integer('images_count')->default(0);
            $table->integer('css_count')->default(0);
            $table->integer('js_count')->default(0);

            // Indexability
            $table->boolean('indexable')->default(true);
            $table->text('indexability_reasons')->nullable(); // JSON array of reasons if not indexable
            $table->boolean('in_sitemap')->default(false);

            // Hreflang
            $table->json('hreflang_alternates')->nullable(); // Array of hreflang links

            // Timestamps
            $table->timestamp('crawled_at');
            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('audit_id');
            $table->index('status_code');
            $table->index('indexable');
            $table->index('in_sitemap');
            $table->index('depth');
            $table->index(['audit_id', 'url_hash']);
            $table->index(['audit_id', 'status_code']);
            $table->index(['audit_id', 'indexable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
