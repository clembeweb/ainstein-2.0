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
        // SQLite requires recreating the table to change foreign keys
        // We'll rename the old table, create new one, copy data, then drop old

        // Step 1: Rename current table
        Schema::rename('content_generations', 'content_generations_old');

        // Step 2: Create new table with correct foreign key
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
            $table->integer('generation_time_ms')->nullable();
            $table->string('ai_model');
            $table->string('status')->default('pending');
            $table->string('execution_mode')->default('async');
            $table->text('error')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('page_id'); // Column name stays the same, but foreign key now points to contents
            $table->string('tenant_id');
            $table->string('created_by');
            $table->timestamps();

            // Foreign key constraints - NOW POINTING TO CONTENTS TABLE
            $table->foreign('page_id')->references('id')->on('contents')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('prompt_id')->references('id')->on('prompts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes will be created manually after data copy
        });

        // Step 3: Copy data from old table (if any exists)
        DB::statement('INSERT INTO content_generations SELECT * FROM content_generations_old');

        // Step 4: Drop old indexes from new table (they were copied with the data)
        try {
            DB::statement('DROP INDEX IF EXISTS content_generations_tenant_id_status_index');
            DB::statement('DROP INDEX IF EXISTS content_generations_page_id_index');
        } catch (\Exception $e) {
            // Ignore if indexes don't exist
        }

        // Step 5: Create indexes on new table
        DB::statement('CREATE INDEX content_generations_tenant_id_status_index ON content_generations (tenant_id, status)');
        DB::statement('CREATE INDEX content_generations_page_id_index ON content_generations (page_id)');

        // Step 6: Drop old table
        Schema::dropIfExists('content_generations_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: recreate with foreign key pointing back to pages
        Schema::rename('content_generations', 'content_generations_new');

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

            // Original foreign key to pages
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('prompt_id')->references('id')->on('prompts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['tenant_id', 'status']);
            $table->index(['page_id']);
        });

        DB::statement('INSERT INTO content_generations SELECT id, prompt_type, prompt_id, prompt_template, variables, additional_instructions, generated_content, meta_title, meta_description, tokens_used, ai_model, status, error, error_message, published_at, completed_at, page_id, tenant_id, created_by, updated_at, created_at FROM content_generations_new');

        Schema::dropIfExists('content_generations_new');
    }
};
