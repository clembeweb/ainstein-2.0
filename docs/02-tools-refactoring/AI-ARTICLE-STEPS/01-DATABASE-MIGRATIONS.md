# 01 - Database Migrations AI Article Steps

**Creato**: 2025-10-08
**Stato**: Complete Database Schema

---

## Overview

Schema database completo per il tool **AI Article Steps**, che permette la generazione automatica di articoli tramite AI con gestione di SEO steps e internal links.

### Tables Overview

```
keywords (Main keywords gestite dal tenant)
    └── articles (Articoli generati con AI)
            ├── seo_steps (Step SEO per articolo)
            ├── internal_links (Link interni suggeriti)
            └── article_variants (AB test varianti)

prompt_templates (Template prompt riutilizzabili)

article_generations (Tracking generazione in tempo reale)
```

### Storage Estimate

- **Per articolo medio** (2000 parole, 5 steps, 10 links): ~50 KB
- **100 articoli**: ~5 MB
- **1000 articoli**: ~50 MB
- **Consigliato**: Archiviation dopo 12 mesi

---

## Migration Files

### 1. Create Keywords Table

**File**: `database/migrations/2025_10_08_000001_create_keywords_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();

            // Keyword data
            $table->string('keyword', 255);
            $table->text('description')->nullable();
            $table->integer('search_volume')->nullable(); // From external API
            $table->decimal('difficulty', 3, 1)->nullable(); // 0-100
            $table->string('intent', 50)->nullable(); // informational, transactional, navigational

            // Metadata
            $table->json('metadata')->nullable(); // Additional data from APIs
            $table->boolean('is_active')->default(true);
            $table->integer('articles_count')->default(0); // Denormalized count

            // Tracking
            $table->string('created_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'keyword']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
```

---

### 2. Create Prompt Templates Table

**File**: `database/migrations/2025_10_08_000002_create_prompt_templates_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();

            // Template data
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('template_text');

            // Template configuration
            $table->string('category', 100)->nullable(); // blog, product, landing, etc.
            $table->string('tone', 100)->default('professional'); // professional, casual, friendly, formal
            $table->integer('default_word_count')->default(1000);
            $table->string('language', 10)->default('it'); // it, en, es, etc.

            // Variables placeholders
            $table->json('variables')->nullable(); // {keyword}, {target_audience}, etc.

            // Metadata
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false); // Can be shared with other tenants
            $table->integer('usage_count')->default(0);

            // Tracking
            $table->string('created_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'category']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
    }
};
```

---

### 3. Create Articles Table

**File**: `database/migrations/2025_10_08_000003_create_articles_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('keyword_id', 26)->nullable();
            $table->string('prompt_template_id', 26)->nullable();

            // Article content
            $table->string('title', 500)->nullable();
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('featured_image_url', 500)->nullable();

            // Generation status
            $table->enum('status', ['pending', 'generating', 'completed', 'failed', 'draft'])->default('pending');
            $table->text('error_message')->nullable();

            // Generation parameters
            $table->json('generation_params')->nullable(); // tone, word_count, extra_instructions, etc.
            $table->text('original_prompt')->nullable();

            // AI metadata
            $table->string('model_used', 100)->nullable(); // gpt-4o, gpt-4o-mini, etc.
            $table->integer('tokens_used')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->integer('word_count')->nullable();
            $table->integer('estimated_read_time')->nullable(); // minutes

            // SEO fields
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('slug', 500)->nullable();
            $table->json('focus_keywords')->nullable();

            // Content analysis
            $table->decimal('readability_score', 5, 2)->nullable(); // Flesch reading ease
            $table->integer('sentences_count')->nullable();
            $table->integer('paragraphs_count')->nullable();
            $table->json('content_structure')->nullable(); // H2, H3 hierarchy

            // Publishing
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('published_url', 500)->nullable();

            // AB Testing
            $table->string('parent_article_id', 26)->nullable(); // If this is a variant
            $table->boolean('is_variant')->default(false);
            $table->string('variant_type', 50)->nullable(); // title, tone, length

            // Tracking
            $table->timestamp('generated_at')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->string('reviewed_by', 26)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'keyword_id']);
            $table->index(['tenant_id', 'is_published']);
            $table->index('generated_at');
            $table->index('created_at');
            $table->fullText(['title', 'content']); // Full-text search

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('keyword_id')->references('id')->on('keywords')->onDelete('set null');
            $table->foreign('prompt_template_id')->references('id')->on('prompt_templates')->onDelete('set null');
            $table->foreign('parent_article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

---

### 4. Create SEO Steps Table

**File**: `database/migrations/2025_10_08_000004_create_seo_steps_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_steps', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('article_id', 26);

            // Step data
            $table->string('step_type', 100); // title_optimization, meta_description, h2_structure, internal_links, etc.
            $table->string('step_title', 255);
            $table->text('step_description')->nullable();
            $table->integer('step_order')->default(0);

            // Step status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->text('completion_notes')->nullable();

            // Suggestions from AI
            $table->json('ai_suggestions')->nullable();
            $table->text('ai_rationale')->nullable(); // Why this step is recommended

            // Implementation
            $table->text('current_value')->nullable(); // Before optimization
            $table->text('suggested_value')->nullable(); // AI suggestion
            $table->text('implemented_value')->nullable(); // What was actually implemented

            // Impact tracking
            $table->decimal('priority_score', 3, 1)->nullable(); // 0-10
            $table->string('impact_level', 50)->nullable(); // high, medium, low
            $table->json('expected_improvements')->nullable();

            // Tracking
            $table->timestamp('completed_at')->nullable();
            $table->string('completed_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'article_id']);
            $table->index(['article_id', 'step_order']);
            $table->index(['article_id', 'status']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_steps');
    }
};
```

---

### 5. Create Internal Links Table

**File**: `database/migrations/2025_10_08_000005_create_internal_links_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_links', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('article_id', 26);

            // Link data
            $table->string('target_url', 500);
            $table->string('anchor_text', 255);
            $table->text('context')->nullable(); // Surrounding text

            // Link metadata
            $table->string('link_type', 100)->default('internal'); // internal, external, resource
            $table->string('target_article_id', 26)->nullable(); // If linking to another article
            $table->integer('position_in_content')->nullable(); // Character position

            // AI suggestion data
            $table->boolean('is_ai_suggested')->default(false);
            $table->decimal('relevance_score', 5, 2)->nullable(); // 0-100
            $table->text('ai_rationale')->nullable();
            $table->json('semantic_similarity')->nullable();

            // Link status
            $table->enum('status', ['suggested', 'accepted', 'rejected', 'implemented'])->default('suggested');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_nofollow')->default(false);

            // Tracking
            $table->string('suggested_by', 26)->nullable(); // AI or user
            $table->string('reviewed_by', 26)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'article_id']);
            $table->index(['article_id', 'status']);
            $table->index(['tenant_id', 'is_ai_suggested']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('target_article_id')->references('id')->on('articles')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_links');
    }
};
```

---

### 6. Create Article Variants Table (AB Testing)

**File**: `database/migrations/2025_10_08_000006_create_article_variants_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_variants', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('original_article_id', 26);
            $table->string('variant_article_id', 26);

            // Variant configuration
            $table->string('variant_name', 255);
            $table->string('variant_type', 100); // title, tone, length, structure
            $table->text('variant_description')->nullable();

            // AB Test configuration
            $table->boolean('is_active')->default(true);
            $table->integer('traffic_split')->default(50); // Percentage 0-100
            $table->timestamp('test_started_at')->nullable();
            $table->timestamp('test_ended_at')->nullable();

            // Results tracking
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('ctr', 5, 2)->nullable(); // Click-through rate
            $table->integer('avg_time_on_page')->nullable(); // seconds
            $table->decimal('bounce_rate', 5, 2)->nullable();
            $table->decimal('conversion_rate', 5, 2)->nullable();

            // Winner selection
            $table->string('winner_article_id', 26)->nullable();
            $table->timestamp('winner_selected_at')->nullable();
            $table->text('winner_rationale')->nullable();

            // Tracking
            $table->string('created_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index(['original_article_id']);
            $table->index(['tenant_id', 'test_started_at']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('original_article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('variant_article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('winner_article_id')->references('id')->on('articles')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_variants');
    }
};
```

---

### 7. Create Article Generations Queue Table

**File**: `database/migrations/2025_10_08_000007_create_article_generations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_generations', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('article_id', 26);

            // Generation job data
            $table->string('job_id', 100)->unique()->nullable(); // Queue job ID
            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'cancelled'])->default('queued');

            // Generation progress
            $table->integer('progress_percentage')->default(0);
            $table->string('current_step', 255)->nullable(); // "Generating title", "Writing introduction", etc.
            $table->json('steps_completed')->nullable();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            // Error handling
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            // Resource tracking
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 10, 6)->default(0);

            // Tracking
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['article_id']);
            $table->index(['status', 'started_at']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_generations');
    }
};
```

---

### 8. Add Performance Indexes

**File**: `database/migrations/2025_10_08_000008_add_article_steps_performance_indexes.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite indexes for common queries

        Schema::table('articles', function (Blueprint $table) {
            $table->index(['tenant_id', 'status', 'created_at'], 'idx_articles_tenant_status_created');
            $table->index(['tenant_id', 'keyword_id', 'status'], 'idx_articles_tenant_keyword_status');
            $table->index(['created_by', 'status'], 'idx_articles_user_status');
        });

        Schema::table('keywords', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_active', 'articles_count'], 'idx_keywords_tenant_active_count');
        });

        Schema::table('seo_steps', function (Blueprint $table) {
            $table->index(['article_id', 'status', 'step_order'], 'idx_steps_article_status_order');
        });

        Schema::table('internal_links', function (Blueprint $table) {
            $table->index(['article_id', 'is_ai_suggested', 'status'], 'idx_links_article_ai_status');
            $table->index(['target_article_id', 'is_active'], 'idx_links_target_active');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('idx_articles_tenant_status_created');
            $table->dropIndex('idx_articles_tenant_keyword_status');
            $table->dropIndex('idx_articles_user_status');
        });

        Schema::table('keywords', function (Blueprint $table) {
            $table->dropIndex('idx_keywords_tenant_active_count');
        });

        Schema::table('seo_steps', function (Blueprint $table) {
            $table->dropIndex('idx_steps_article_status_order');
        });

        Schema::table('internal_links', function (Blueprint $table) {
            $table->dropIndex('idx_links_article_ai_status');
            $table->dropIndex('idx_links_target_active');
        });
    }
};
```

---

## Summary

Migrations complete per AI Article Steps:

✅ **8 migration files** completi
✅ **7 tabelle** principali create
✅ **Multi-tenancy** con tenant_id su tutte le tabelle
✅ **ULID primary keys** (26 caratteri)
✅ **Foreign keys** con CASCADE delete
✅ **Soft deletes** su tabelle principali
✅ **Composite indexes** per performance
✅ **Full-text search** su articles.title e content
✅ **JSON fields** per metadata flessibili

### Database Tables

| Table | Purpose | Estimated Size |
|-------|---------|----------------|
| `keywords` | Main keywords | ~1 KB/record |
| `prompt_templates` | Reusable prompts | ~2 KB/record |
| `articles` | Generated articles | ~40 KB/record |
| `seo_steps` | SEO optimization steps | ~2 KB/record |
| `internal_links` | Link suggestions | ~1 KB/record |
| `article_variants` | AB test variants | ~500 B/record |
| `article_generations` | Queue tracking | ~1 KB/record |

### Features Implemented

1. **Article Generation** - Complete workflow tracking
2. **SEO Steps** - Structured optimization steps
3. **Internal Links** - AI-powered link suggestions
4. **AB Testing** - Variant testing support
5. **Real-time Progress** - Generation queue tracking
6. **Multi-language** - Language field on templates
7. **Content Analysis** - Readability, structure tracking
8. **Cost Tracking** - Tokens & cost per article

---

**Total**: ~40 KB documentation
**Migration Files**: 8 complete
**Tables**: 7 primary + indexes
**Storage per 1000 articles**: ~50 MB
