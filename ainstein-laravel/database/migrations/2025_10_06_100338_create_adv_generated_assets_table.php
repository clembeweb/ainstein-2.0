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
        Schema::create('adv_generated_assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained('adv_campaigns')->onDelete('cascade');
            $table->enum('type', ['rsa', 'pmax']);
            $table->json('titles');
            $table->json('long_titles')->nullable();
            $table->json('descriptions');
            $table->decimal('ai_quality_score', 3, 2)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('campaign_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adv_generated_assets');
    }
};
