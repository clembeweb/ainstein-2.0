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
        Schema::create('crews', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('tenant_id')->index();
            $table->string('created_by')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('process_type', ['sequential', 'hierarchical'])->default('sequential');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Composite indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crews');
    }
};
