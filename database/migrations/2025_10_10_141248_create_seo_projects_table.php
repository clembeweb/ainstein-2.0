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
        Schema::create('seo_projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('domain', 255);
            $table->text('description')->nullable();

            // Scope Configuration
            $table->boolean('include_subdomains')->default(false);
            $table->string('scope_path', 500)->nullable();
            $table->text('include_patterns')->nullable(); // JSON array of regex/globs
            $table->text('exclude_patterns')->nullable(); // JSON array of regex/globs

            // Authentication
            $table->enum('auth_type', ['none', 'basic', 'digest', 'cookie'])->default('none');
            $table->string('auth_username')->nullable();
            $table->string('auth_password')->nullable(); // encrypted
            $table->text('auth_cookie_header')->nullable();

            // URL Parameters
            $table->text('param_whitelist')->nullable(); // JSON array
            $table->text('param_blacklist')->nullable(); // JSON array
            $table->boolean('normalize_param_order')->default(true);

            // Crawl Settings
            $table->string('user_agent', 500)->nullable();
            $table->boolean('obey_robots')->default(true);
            $table->integer('max_concurrency')->default(8);
            $table->integer('delay_ms')->default(300);
            $table->integer('timeout_seconds')->default(30);

            // Limits
            $table->integer('max_pages')->default(10000);
            $table->integer('max_depth')->default(10);

            // Scheduling
            $table->enum('recurring_schedule', ['none', 'daily', 'weekly', 'monthly'])->default('none');
            $table->time('schedule_time')->nullable();
            $table->timestamp('last_scheduled_at')->nullable();

            // Status & Metadata
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_audit_at')->nullable();
            $table->json('config_snapshot')->nullable(); // Additional custom settings

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('domain');
            $table->index('is_active');
            $table->index('recurring_schedule');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_projects');
    }
};
