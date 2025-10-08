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
            $table->string('execution_mode')->default('async')->after('status');
            $table->timestamp('started_at')->nullable()->after('published_at');
            $table->integer('generation_time_ms')->nullable()->after('tokens_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropColumn(['execution_mode', 'started_at', 'generation_time_ms']);
        });
    }
};
