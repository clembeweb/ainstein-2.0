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
        Schema::table('prompts', function (Blueprint $table) {
            $table->string('tool_id')->nullable()->after('id');
            $table->boolean('is_global')->default(false)->after('tool_id');

            // Foreign key
            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('set null');

            // Indexes
            $table->index(['tool_id', 'is_active']);
            $table->index(['tenant_id', 'tool_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->dropForeign(['tool_id']);
            $table->dropIndex(['tool_id', 'is_active']);
            $table->dropIndex(['tenant_id', 'tool_id']);
            $table->dropColumn(['tool_id', 'is_global']);
        });
    }
};
