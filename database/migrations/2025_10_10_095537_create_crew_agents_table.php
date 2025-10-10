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
        Schema::create('crew_agents', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('crew_id')->index();
            $table->string('name');
            $table->string('role');
            $table->text('goal')->nullable();
            $table->text('backstory')->nullable();
            $table->json('tools')->nullable(); // Array of tool names/configurations
            $table->json('llm_config')->nullable(); // Model, temperature, etc.
            $table->integer('max_iterations')->default(25);
            $table->integer('order')->default(0); // Execution order
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('crew_id')->references('id')->on('crews')->onDelete('cascade');

            // Indexes for performance
            $table->index(['crew_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_agents');
    }
};
