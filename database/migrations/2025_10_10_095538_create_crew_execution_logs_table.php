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
        Schema::create('crew_execution_logs', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('crew_execution_id')->index();
            $table->string('task_id')->nullable()->index();
            $table->string('agent_id')->nullable()->index();
            $table->enum('level', ['info', 'warning', 'error', 'debug'])->default('info')->index();
            $table->text('message');
            $table->json('data')->nullable(); // Additional structured data
            $table->integer('tokens_used')->default(0);
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            // Foreign keys
            $table->foreign('crew_execution_id')->references('id')->on('crew_executions')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('crew_tasks')->onDelete('set null');
            $table->foreign('agent_id')->references('id')->on('crew_agents')->onDelete('set null');

            // Indexes for performance
            $table->index(['crew_execution_id', 'logged_at']);
            $table->index(['crew_execution_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_execution_logs');
    }
};
