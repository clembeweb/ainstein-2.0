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
        Schema::create('adv_campaigns', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('info')->nullable();
            $table->text('keywords')->nullable();
            $table->enum('type', ['rsa', 'pmax']);
            $table->string('language', 10)->default('it');
            $table->string('url', 500)->nullable();
            $table->integer('tokens_used')->default(0);
            $table->string('model_used', 100)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adv_campaigns');
    }
};
