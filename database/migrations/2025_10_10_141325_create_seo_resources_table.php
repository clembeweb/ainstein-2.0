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
        Schema::create('seo_resources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUlid('audit_id')->constrained('seo_audits')->onDelete('cascade');
            $table->foreignUlid('page_id')->constrained('seo_pages')->onDelete('cascade');

            // Resource Info
            $table->string('url', 2048);
            $table->string('url_hash', 64)->index();
            $table->enum('type', ['css', 'js', 'image', 'font', 'video', 'other'])->default('other');

            // HTTP Info
            $table->integer('status_code')->nullable();
            $table->integer('size_bytes')->nullable();
            $table->integer('load_time_ms')->nullable();

            // Attributes
            $table->enum('source', ['html', 'header', 'css'])->default('html'); // Where it was loaded from
            $table->string('alt', 500)->nullable(); // For images
            $table->boolean('has_dimensions')->default(false); // For images (width/height)
            $table->boolean('is_broken')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('audit_id');
            $table->index('page_id');
            $table->index('type');
            $table->index('status_code');
            $table->index('is_broken');
            $table->index(['audit_id', 'type']);
            $table->index(['audit_id', 'is_broken']);
            $table->index(['page_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_resources');
    }
};
