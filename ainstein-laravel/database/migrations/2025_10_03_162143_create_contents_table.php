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
        Schema::create('contents', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('tenant_id')->index();
            $table->string('url')->index();
            $table->enum('content_type', ['article', 'product', 'service', 'landing_page', 'category'])->default('article');
            $table->enum('source', ['manual', 'csv', 'wordpress', 'prestashop'])->default('manual');
            $table->string('source_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('keyword')->nullable();
            $table->string('language')->default('it');
            $table->json('meta_data')->nullable();
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active')->index();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Composite indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'content_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
