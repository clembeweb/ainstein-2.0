<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support dropping/modifying foreign keys directly
        // We need to recreate the table with the correct foreign key

        // For SQLite: Disable foreign keys temporarily and recreate table
        DB::statement('PRAGMA foreign_keys = OFF');

        // Create new table with correct foreign keys
        Schema::create('content_generations_new', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('prompt_type');
            $table->ulid('prompt_id');
            $table->text('prompt_template');
            $table->json('variables')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->text('generated_content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->string('ai_model');
            $table->string('execution_mode', 20)->default('async');
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->ulid('page_id'); // This will now reference contents
            $table->ulid('tenant_id');
            $table->ulid('created_by');
            $table->timestamp('started_at')->nullable();
            $table->integer('generation_time_ms')->nullable();
            $table->timestamps();

            // Foreign keys pointing to correct tables
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('prompt_id')->references('id')->on('prompts')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('page_id')->references('id')->on('contents')->onDelete('cascade'); // Fixed!

            $table->index('execution_mode');
        });

        // Copy data from old table to new (specify columns explicitly to handle column order)
        DB::statement('INSERT INTO content_generations_new (id, prompt_type, prompt_id, prompt_template, variables, additional_instructions, generated_content, meta_title, meta_description, tokens_used, ai_model, execution_mode, status, error, error_message, published_at, completed_at, page_id, tenant_id, created_by, started_at, generation_time_ms, created_at, updated_at)
            SELECT id, prompt_type, prompt_id, prompt_template, variables, additional_instructions, generated_content, meta_title, meta_description, tokens_used, ai_model, execution_mode, status, error, error_message, published_at, completed_at, page_id, tenant_id, created_by, started_at, generation_time_ms, created_at, updated_at FROM content_generations');

        // Drop old table and rename new one
        Schema::drop('content_generations');
        Schema::rename('content_generations_new', 'content_generations');

        // Re-enable foreign keys
        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: Point foreign key back to pages table
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('content_generations_new', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('prompt_type');
            $table->ulid('prompt_id');
            $table->text('prompt_template');
            $table->json('variables')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->text('generated_content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->string('ai_model');
            $table->string('execution_mode', 20)->default('async');
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->ulid('page_id');
            $table->ulid('tenant_id');
            $table->ulid('created_by');
            $table->timestamp('started_at')->nullable();
            $table->integer('generation_time_ms')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('prompt_id')->references('id')->on('prompts')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
        });

        DB::statement('INSERT INTO content_generations_new SELECT * FROM content_generations');
        Schema::drop('content_generations');
        Schema::rename('content_generations_new', 'content_generations');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
