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
        Schema::table('content_generations', function (Blueprint $table) {
            $table->string('execution_mode', 20)
                  ->default('async')
                  ->after('ai_model')
                  ->comment('async (queue) or sync (immediate)');

            $table->timestamp('started_at')->nullable()->after('created_at');
            $table->integer('generation_time_ms')->nullable()->after('tokens_used');

            $table->index('execution_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropIndex(['execution_mode']);
            $table->dropColumn(['execution_mode', 'started_at', 'generation_time_ms']);
        });
    }
};
