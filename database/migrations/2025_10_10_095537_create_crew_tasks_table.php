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
        Schema::create('crew_tasks', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('crew_id')->index();
            $table->string('agent_id')->nullable()->index();
            $table->text('description');
            $table->text('expected_output')->nullable();
            $table->json('context')->nullable(); // Input variables for the task
            $table->json('dependencies')->nullable(); // Array of task IDs this task depends on
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('crew_id')->references('id')->on('crews')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('crew_agents')->onDelete('set null');

            // Indexes for performance
            $table->index(['crew_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_tasks');
    }
};
