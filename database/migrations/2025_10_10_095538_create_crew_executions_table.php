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
        Schema::create('crew_executions', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('crew_id')->index();
            $table->string('tenant_id')->index();
            $table->string('triggered_by')->nullable();
            $table->json('input_variables')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_tokens_used')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->json('results')->nullable(); // Output per task
            $table->text('error_message')->nullable();
            $table->json('execution_log')->nullable(); // Step-by-step execution log
            $table->integer('retry_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('crew_id')->references('id')->on('crews')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('triggered_by')->references('id')->on('users')->onDelete('set null');

            // Composite indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['crew_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_executions');
    }
};
