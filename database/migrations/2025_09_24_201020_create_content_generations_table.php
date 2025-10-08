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
        Schema::create('content_generations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('prompt_type');
            $table->string('prompt_id');
            $table->text('prompt_template');
            $table->json('variables')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->text('generated_content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->string('ai_model');
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('page_id');
            $table->string('tenant_id');
            $table->string('created_by');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('prompt_id')->references('id')->on('prompts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['page_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_generations');
    }
};
