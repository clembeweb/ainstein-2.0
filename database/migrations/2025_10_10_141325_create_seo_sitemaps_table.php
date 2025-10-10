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
        Schema::create('seo_sitemaps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUlid('audit_id')->constrained('seo_audits')->onDelete('cascade');

            // Sitemap Info
            $table->string('url', 2048);
            $table->string('url_hash', 64)->index();
            $table->enum('type', ['index', 'regular'])->default('regular'); // index = sitemap of sitemaps

            // Entries
            $table->integer('entries_count')->default(0);
            $table->integer('valid_entries')->default(0);
            $table->integer('invalid_entries')->default(0);

            // Metadata
            $table->timestamp('last_modified')->nullable(); // From sitemap lastmod
            $table->integer('status_code')->nullable();
            $table->boolean('is_valid_xml')->default(true);
            $table->text('parse_errors')->nullable(); // JSON array of errors
            $table->json('discovered_urls')->nullable(); // Sample of URLs found (for preview)

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('audit_id');
            $table->index('type');
            $table->index('is_valid_xml');
            $table->index(['audit_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_sitemaps');
    }
};
