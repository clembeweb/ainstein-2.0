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
        Schema::create('crew_templates', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('tenant_id')->nullable()->index(); // Null for system templates
            $table->string('created_by')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable()->index(); // content, research, analysis, etc.
            $table->json('crew_configuration'); // Full crew configuration to clone
            $table->boolean('is_system')->default(false)->index(); // System vs user templates
            $table->boolean('is_public')->default(false)->index(); // Shareable templates
            $table->integer('usage_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0); // 0.00 to 5.00
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Composite indexes for performance
            $table->index(['is_system', 'is_public']);
            $table->index(['category', 'is_public']);
            $table->index(['tenant_id', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_templates');
    }
};
