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
        Schema::create('crew_agent_tools', function (Blueprint $table) {
            $table->string('id')->primary(); // ULID
            $table->string('name')->unique();
            $table->text('description');
            $table->enum('type', ['builtin', 'custom', 'api'])->default('builtin')->index();
            $table->json('configuration')->nullable(); // Tool-specific configuration
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_agent_tools');
    }
};
