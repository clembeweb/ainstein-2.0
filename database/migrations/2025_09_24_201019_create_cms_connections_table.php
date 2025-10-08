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
        Schema::create('cms_connections', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('endpoint');
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->json('additional_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('tenant_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Unique constraints
            $table->unique(['tenant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_connections');
    }
};
