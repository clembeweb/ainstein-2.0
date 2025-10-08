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
        Schema::create('pages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('url_path');
            $table->string('keyword')->nullable();
            $table->string('category')->nullable();
            $table->string('language')->default('it');
            $table->string('cms_type')->nullable();
            $table->string('cms_page_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('priority')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced')->nullable();
            $table->string('tenant_id');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'url_path']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
