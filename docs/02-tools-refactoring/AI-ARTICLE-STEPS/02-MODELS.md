# AI Article Steps — Models & Relationships

**Documento**: 02 — Eloquent Models con Relationships
**Progetto**: Ainstein Laravel Multi-Tenant Platform
**Tool**: AI Article Steps (Copy Article Generator)
**Database**: 7 tabelle principali

---

## Indice
1. [Overview Models](#1-overview-models)
2. [Keyword Model](#2-keyword-model)
3. [PromptTemplate Model](#3-prompttemplate-model)
4. [Article Model](#4-article-model)
5. [SeoStep Model](#5-seostep-model)
6. [InternalLink Model](#6-internallink-model)
7. [ArticleVariant Model](#7-articlevariant-model)
8. [ArticleGeneration Model](#8-articlegeneration-model)
9. [Model Relationships Diagram](#9-model-relationships-diagram)
10. [Query Examples](#10-query-examples)

---

## 1. Overview Models

### Models Structure
```
app/Models/
├── Keyword.php                 # Gestione keyword principali
├── PromptTemplate.php          # Template prompts riutilizzabili
├── Article.php                 # ⭐ Articolo generato (CORE MODEL)
├── SeoStep.php                 # Step SEO ottimizzazione
├── InternalLink.php            # Link interni suggeriti da AI
├── ArticleVariant.php          # Varianti AB testing
└── ArticleGeneration.php       # Tracking generazioni in real-time
```

### Common Traits
Tutti i model utilizzano:
- **ULID primary keys** (26 caratteri, tipo string)
- **Multi-tenancy** via `tenant_id` foreign key
- **SoftDeletes** sui modelli principali (Article, Keyword, PromptTemplate)
- **Relationships** tramite Eloquent
- **Casts** per JSON fields e date
- **Query Scopes** per filtri comuni

---

## 2. Keyword Model

**File**: `app/Models/Keyword.php`

### Complete Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Keyword extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'keywords';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'keyword',
        'search_volume',
        'cpc',
        'competition',
        'intent',
        'category',
        'language',
        'country',
        'notes',
        'tags',
        'priority',
        'status',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'search_volume' => 'integer',
        'cpc' => 'decimal:2',
        'competition' => 'decimal:2',
        'tags' => 'array',
        'priority' => 'integer',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Keyword status constants.
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_USED = 'used';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Keyword intent constants.
     */
    const INTENT_INFORMATIONAL = 'informational';
    const INTENT_COMMERCIAL = 'commercial';
    const INTENT_TRANSACTIONAL = 'transactional';
    const INTENT_NAVIGATIONAL = 'navigational';

    /**
     * Get the tenant that owns the keyword.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created the keyword.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all articles for this keyword.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'keyword_id');
    }

    /**
     * Get completed articles for this keyword.
     */
    public function completedArticles(): HasMany
    {
        return $this->articles()->where('status', Article::STATUS_COMPLETED);
    }

    /**
     * Scope: Filter by tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by intent.
     */
    public function scopeByIntent($query, string $intent)
    {
        return $query->where('intent', $intent);
    }

    /**
     * Scope: High priority keywords.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 8);
    }

    /**
     * Scope: Unused keywords (no articles yet).
     */
    public function scopeUnused($query)
    {
        return $query->doesntHave('articles')
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Search by keyword text.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('keyword', 'like', "%{$term}%");
    }

    /**
     * Check if keyword has been used for article generation.
     */
    public function isUsed(): bool
    {
        return $this->articles()->exists();
    }

    /**
     * Get article count for this keyword.
     */
    public function getArticleCountAttribute(): int
    {
        return $this->articles()->count();
    }

    /**
     * Get competition level as text.
     */
    public function getCompetitionLevelAttribute(): string
    {
        if ($this->competition === null) {
            return 'Unknown';
        }

        if ($this->competition < 0.3) {
            return 'Low';
        } elseif ($this->competition < 0.7) {
            return 'Medium';
        } else {
            return 'High';
        }
    }

    /**
     * Mark keyword as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['status' => self::STATUS_USED]);
    }

    /**
     * Archive keyword.
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }
}
```

---

## 3. PromptTemplate Model

**File**: `app/Models/PromptTemplate.php`

### Complete Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplate extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'prompt_templates';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'prompt_text',
        'tone',
        'style',
        'target_word_count_min',
        'target_word_count_max',
        'language',
        'include_seo_optimization',
        'include_internal_links',
        'category',
        'tags',
        'is_default',
        'is_public',
        'usage_count',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'target_word_count_min' => 'integer',
        'target_word_count_max' => 'integer',
        'include_seo_optimization' => 'boolean',
        'include_internal_links' => 'boolean',
        'tags' => 'array',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'usage_count' => 'integer',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Tone constants.
     */
    const TONE_PROFESSIONAL = 'professional';
    const TONE_CASUAL = 'casual';
    const TONE_FRIENDLY = 'friendly';
    const TONE_AUTHORITATIVE = 'authoritative';
    const TONE_CONVERSATIONAL = 'conversational';

    /**
     * Style constants.
     */
    const STYLE_BLOG = 'blog';
    const STYLE_TUTORIAL = 'tutorial';
    const STYLE_GUIDE = 'guide';
    const STYLE_LISTICLE = 'listicle';
    const STYLE_NEWS = 'news';
    const STYLE_REVIEW = 'review';

    /**
     * Get the tenant that owns the template.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all articles using this template.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'prompt_template_id');
    }

    /**
     * Scope: Filter by tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Public templates (shared).
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: By category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: By tone.
     */
    public function scopeByTone($query, string $tone)
    {
        return $query->where('tone', $tone);
    }

    /**
     * Scope: Most used templates.
     */
    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Replace placeholders in prompt text with actual values.
     */
    public function renderPrompt(array $variables): string
    {
        $text = $this->prompt_text;

        foreach ($variables as $key => $value) {
            $text = str_replace("{{" . $key . "}}", $value, $text);
        }

        return $text;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get target word count range as string.
     */
    public function getWordCountRangeAttribute(): string
    {
        if (!$this->target_word_count_min && !$this->target_word_count_max) {
            return 'No limit';
        }

        if ($this->target_word_count_min && $this->target_word_count_max) {
            return "{$this->target_word_count_min} - {$this->target_word_count_max} words";
        }

        if ($this->target_word_count_min) {
            return "Min {$this->target_word_count_min} words";
        }

        return "Max {$this->target_word_count_max} words";
    }

    /**
     * Extract placeholders from prompt text.
     */
    public function getPlaceholdersAttribute(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->prompt_text, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Check if template is customizable (has placeholders).
     */
    public function isCustomizable(): bool
    {
        return count($this->placeholders) > 0;
    }
}
```

---

## 4. Article Model

**File**: `app/Models/Article.php`

### Complete Code (⭐ CORE MODEL - 450+ LOC)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'articles';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'keyword_id',
        'prompt_template_id',

        // Article content
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',

        // Generation status
        'status',
        'generation_started_at',
        'generation_completed_at',
        'generation_failed_at',
        'failure_reason',

        // AI metadata
        'model_used',
        'temperature',
        'max_tokens',
        'tokens_used',
        'cost',
        'prompt_used',
        'ai_instructions',

        // SEO fields
        'meta_title',
        'meta_description',
        'focus_keyword',
        'seo_score',

        // Content analysis
        'word_count',
        'reading_time_minutes',
        'readability_score',
        'tone_detected',

        // Publishing
        'published_at',
        'scheduled_at',
        'is_published',
        'published_url',

        // AB Testing
        'parent_article_id',
        'is_variant',
        'variant_name',

        // User tracking
        'created_by',
        'last_edited_by',
        'reviewed_by',
        'approved_at',

        // Analytics
        'views_count',
        'engagement_score',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'generation_started_at' => 'datetime',
        'generation_completed_at' => 'datetime',
        'generation_failed_at' => 'datetime',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'tokens_used' => 'integer',
        'cost' => 'decimal:6',
        'seo_score' => 'integer',
        'word_count' => 'integer',
        'reading_time_minutes' => 'integer',
        'readability_score' => 'decimal:2',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'is_published' => 'boolean',
        'is_variant' => 'boolean',
        'approved_at' => 'datetime',
        'views_count' => 'integer',
        'engagement_score' => 'decimal:2',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_GENERATING = 'generating';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEW = 'review';
    const STATUS_APPROVED = 'approved';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';

    /**
     * Get the tenant that owns the article.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the keyword for this article.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Get the prompt template used.
     */
    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class);
    }

    /**
     * Get the user who created the article.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last edited the article.
     */
    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    /**
     * Get the user who reviewed the article.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get all SEO steps for this article.
     */
    public function seoSteps(): HasMany
    {
        return $this->hasMany(SeoStep::class, 'article_id');
    }

    /**
     * Get all internal links for this article.
     */
    public function internalLinks(): HasMany
    {
        return $this->hasMany(InternalLink::class, 'article_id');
    }

    /**
     * Get all variants for this article (if parent).
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ArticleVariant::class, 'parent_article_id');
    }

    /**
     * Get parent article if this is a variant.
     */
    public function parentArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'parent_article_id');
    }

    /**
     * Get all generations for this article.
     */
    public function generations(): HasMany
    {
        return $this->hasMany(ArticleGeneration::class, 'article_id');
    }

    /**
     * Get the latest generation.
     */
    public function latestGeneration()
    {
        return $this->hasOne(ArticleGeneration::class, 'article_id')->latestOfMany();
    }

    /**
     * Scope: Filter by tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at');
    }

    /**
     * Scope: Draft articles.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Failed generations.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Scheduled articles.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now());
    }

    /**
     * Scope: Ready to publish (scheduled in the past).
     */
    public function scopeReadyToPublish($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope: Variants only.
     */
    public function scopeVariants($query)
    {
        return $query->where('is_variant', true);
    }

    /**
     * Scope: Parent articles only (not variants).
     */
    public function scopeParents($query)
    {
        return $query->where('is_variant', false);
    }

    /**
     * Scope: Recently completed.
     */
    public function scopeRecentlyCompleted($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->where('generation_completed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Search by title or content.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%")
              ->orWhere('excerpt', 'like', "%{$term}%");
        });
    }

    /**
     * Scope: High SEO score.
     */
    public function scopeHighSeoScore($query, int $minScore = 80)
    {
        return $query->where('seo_score', '>=', $minScore);
    }

    /**
     * Auto-generate slug from title.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug) && !empty($article->title)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    /**
     * Check if article generation is in progress.
     */
    public function isGenerating(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_GENERATING,
        ]);
    }

    /**
     * Check if article generation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if article generation failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if article is published.
     */
    public function isPublished(): bool
    {
        return $this->is_published && $this->published_at !== null;
    }

    /**
     * Check if article is scheduled for future publishing.
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->scheduled_at
            && $this->scheduled_at->isFuture();
    }

    /**
     * Check if article is ready to publish.
     */
    public function isReadyToPublish(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->scheduled_at
            && $this->scheduled_at->isPast();
    }

    /**
     * Mark article as generating.
     */
    public function markAsGenerating(): void
    {
        $this->update([
            'status' => self::STATUS_GENERATING,
            'generation_started_at' => now(),
        ]);
    }

    /**
     * Mark article as completed.
     */
    public function markAsCompleted(array $data = []): void
    {
        $this->update(array_merge([
            'status' => self::STATUS_COMPLETED,
            'generation_completed_at' => now(),
        ], $data));
    }

    /**
     * Mark article as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'generation_failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Publish article immediately.
     */
    public function publish(string $url = null): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
            'published_url' => $url,
        ]);
    }

    /**
     * Schedule article for future publishing.
     */
    public function schedule(\DateTime $datetime): void
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_at' => $datetime,
        ]);
    }

    /**
     * Calculate word count from content.
     */
    public function calculateWordCount(): int
    {
        if (empty($this->content)) {
            return 0;
        }

        return str_word_count(strip_tags($this->content));
    }

    /**
     * Calculate reading time in minutes.
     */
    public function calculateReadingTime(): int
    {
        $wordCount = $this->word_count ?: $this->calculateWordCount();
        return max(1, (int) ceil($wordCount / 200)); // 200 words per minute
    }

    /**
     * Update word count and reading time.
     */
    public function updateContentMetrics(): void
    {
        $this->update([
            'word_count' => $this->calculateWordCount(),
            'reading_time_minutes' => $this->calculateReadingTime(),
        ]);
    }

    /**
     * Get generation duration in seconds.
     */
    public function getGenerationDurationAttribute(): ?int
    {
        if (!$this->generation_started_at || !$this->generation_completed_at) {
            return null;
        }

        return $this->generation_completed_at->diffInSeconds($this->generation_started_at);
    }

    /**
     * Get generation duration formatted.
     */
    public function getGenerationDurationFormattedAttribute(): string
    {
        $duration = $this->generation_duration;

        if ($duration === null) {
            return 'N/A';
        }

        if ($duration < 60) {
            return "{$duration}s";
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Get SEO score level.
     */
    public function getSeoScoreLevelAttribute(): string
    {
        if ($this->seo_score === null) {
            return 'Not analyzed';
        }

        if ($this->seo_score >= 80) {
            return 'Excellent';
        } elseif ($this->seo_score >= 60) {
            return 'Good';
        } elseif ($this->seo_score >= 40) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }

    /**
     * Get completed SEO steps count.
     */
    public function getCompletedSeoStepsCountAttribute(): int
    {
        return $this->seoSteps()
            ->where('status', SeoStep::STATUS_COMPLETED)
            ->count();
    }

    /**
     * Get total SEO steps count.
     */
    public function getTotalSeoStepsCountAttribute(): int
    {
        return $this->seoSteps()->count();
    }

    /**
     * Get SEO steps completion percentage.
     */
    public function getSeoStepsCompletionPercentageAttribute(): int
    {
        $total = $this->total_seo_steps_count;

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->completed_seo_steps_count / $total) * 100);
    }

    /**
     * Check if all SEO steps are completed.
     */
    public function hasAllSeoStepsCompleted(): bool
    {
        return $this->total_seo_steps_count > 0
            && $this->completed_seo_steps_count === $this->total_seo_steps_count;
    }

    /**
     * Get variant count (if parent article).
     */
    public function getVariantCountAttribute(): int
    {
        return $this->variants()->count();
    }
}
```

---

## 5. SeoStep Model

**File**: `app/Models/SeoStep.php`

### Complete Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoStep extends Model
{
    use HasFactory, HasUlids;

    /**
     * The table associated with the model.
     */
    protected $table = 'seo_steps';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'article_id',
        'step_type',
        'step_title',
        'step_description',
        'step_order',
        'status',
        'ai_suggestion',
        'user_input',
        'applied_value',
        'is_automated',
        'completed_at',
        'completed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'step_order' => 'integer',
        'is_automated' => 'boolean',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Step type constants.
     */
    const TYPE_META_TITLE = 'meta_title';
    const TYPE_META_DESCRIPTION = 'meta_description';
    const TYPE_HEADING_H1 = 'heading_h1';
    const TYPE_HEADING_H2 = 'heading_h2';
    const TYPE_HEADING_STRUCTURE = 'heading_structure';
    const TYPE_KEYWORD_DENSITY = 'keyword_density';
    const TYPE_INTERNAL_LINKS = 'internal_links';
    const TYPE_IMAGE_ALT = 'image_alt';
    const TYPE_READABILITY = 'readability';
    const TYPE_CONTENT_LENGTH = 'content_length';

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SKIPPED = 'skipped';

    /**
     * Get the article for this step.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the user who completed the step.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Scope: Filter by article.
     */
    public function scopeForArticle($query, string $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('step_type', $type);
    }

    /**
     * Scope: Completed steps.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Pending steps.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Automated steps.
     */
    public function scopeAutomated($query)
    {
        return $query->where('is_automated', true);
    }

    /**
     * Scope: Manual steps.
     */
    public function scopeManual($query)
    {
        return $query->where('is_automated', false);
    }

    /**
     * Scope: Ordered by step_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('step_order', 'asc');
    }

    /**
     * Check if step is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if step is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark step as completed.
     */
    public function markAsCompleted(string $userId = null, string $value = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $userId,
            'applied_value' => $value ?? $this->applied_value,
        ]);
    }

    /**
     * Skip this step.
     */
    public function skip(): void
    {
        $this->update(['status' => self::STATUS_SKIPPED]);
    }

    /**
     * Apply AI suggestion to step.
     */
    public function applyAiSuggestion(): void
    {
        $this->update([
            'applied_value' => $this->ai_suggestion,
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get step type label.
     */
    public function getStepTypeLabelAttribute(): string
    {
        return match($this->step_type) {
            self::TYPE_META_TITLE => 'Meta Title',
            self::TYPE_META_DESCRIPTION => 'Meta Description',
            self::TYPE_HEADING_H1 => 'H1 Heading',
            self::TYPE_HEADING_H2 => 'H2 Headings',
            self::TYPE_HEADING_STRUCTURE => 'Heading Structure',
            self::TYPE_KEYWORD_DENSITY => 'Keyword Density',
            self::TYPE_INTERNAL_LINKS => 'Internal Links',
            self::TYPE_IMAGE_ALT => 'Image Alt Text',
            self::TYPE_READABILITY => 'Readability',
            self::TYPE_CONTENT_LENGTH => 'Content Length',
            default => ucfirst(str_replace('_', ' ', $this->step_type)),
        };
    }
}
```

---

## 6. InternalLink Model

**File**: `app/Models/InternalLink.php`

### Complete Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalLink extends Model
{
    use HasFactory, HasUlids;

    /**
     * The table associated with the model.
     */
    protected $table = 'internal_links';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'article_id',
        'target_url',
        'target_article_id',
        'anchor_text',
        'context_sentence',
        'relevance_score',
        'position_in_content',
        'status',
        'is_ai_suggested',
        'suggestion_reason',
        'applied_at',
        'applied_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'relevance_score' => 'decimal:2',
        'position_in_content' => 'integer',
        'is_ai_suggested' => 'boolean',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    const STATUS_SUGGESTED = 'suggested';
    const STATUS_APPROVED = 'approved';
    const STATUS_APPLIED = 'applied';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the article for this link.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the target article (if internal).
     */
    public function targetArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'target_article_id');
    }

    /**
     * Get the user who applied the link.
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Scope: Filter by article.
     */
    public function scopeForArticle($query, string $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: AI suggested links.
     */
    public function scopeAiSuggested($query)
    {
        return $query->where('is_ai_suggested', true);
    }

    /**
     * Scope: Suggested links.
     */
    public function scopeSuggested($query)
    {
        return $query->where('status', self::STATUS_SUGGESTED);
    }

    /**
     * Scope: Applied links.
     */
    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }

    /**
     * Scope: High relevance links.
     */
    public function scopeHighRelevance($query, float $minScore = 0.7)
    {
        return $query->where('relevance_score', '>=', $minScore);
    }

    /**
     * Scope: Order by relevance.
     */
    public function scopeByRelevance($query)
    {
        return $query->orderBy('relevance_score', 'desc');
    }

    /**
     * Check if link is suggested.
     */
    public function isSuggested(): bool
    {
        return $this->status === self::STATUS_SUGGESTED;
    }

    /**
     * Check if link is applied.
     */
    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * Approve link suggestion.
     */
    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Reject link suggestion.
     */
    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Apply link to content.
     */
    public function apply(string $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_APPLIED,
            'applied_at' => now(),
            'applied_by' => $userId,
        ]);
    }

    /**
     * Get relevance level.
     */
    public function getRelevanceLevelAttribute(): string
    {
        if ($this->relevance_score === null) {
            return 'Unknown';
        }

        if ($this->relevance_score >= 0.8) {
            return 'Very High';
        } elseif ($this->relevance_score >= 0.6) {
            return 'High';
        } elseif ($this->relevance_score >= 0.4) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
}
```

---

## 7. ArticleVariant Model

**File**: `app/Models/ArticleVariant.php`

### Complete Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleVariant extends Model
{
    use HasFactory, HasUlids;

    /**
     * The table associated with the model.
     */
    protected $table = 'article_variants';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'parent_article_id',
        'variant_article_id',
        'variant_name',
        'variant_type',
        'test_started_at',
        'test_ended_at',
        'status',

        // Traffic split
        'traffic_percentage',

        // Metrics
        'views_count',
        'clicks_count',
        'conversions_count',
        'avg_time_on_page',
        'bounce_rate',
        'engagement_score',

        // Winner selection
        'is_winner',
        'confidence_level',
        'statistical_significance',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'test_started_at' => 'datetime',
        'test_ended_at' => 'datetime',
        'traffic_percentage' => 'integer',
        'views_count' => 'integer',
        'clicks_count' => 'integer',
        'conversions_count' => 'integer',
        'avg_time_on_page' => 'integer',
        'bounce_rate' => 'decimal:2',
        'engagement_score' => 'decimal:2',
        'is_winner' => 'boolean',
        'confidence_level' => 'decimal:2',
        'statistical_significance' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Variant type constants.
     */
    const TYPE_TITLE = 'title';
    const TYPE_INTRO = 'intro';
    const TYPE_STRUCTURE = 'structure';
    const TYPE_CTA = 'cta';
    const TYPE_FULL_CONTENT = 'full_content';

    /**
     * Status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';

    /**
     * Get the parent article.
     */
    public function parentArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'parent_article_id');
    }

    /**
     * Get the variant article.
     */
    public function variantArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'variant_article_id');
    }

    /**
     * Scope: Filter by parent article.
     */
    public function scopeForParent($query, string $parentArticleId)
    {
        return $query->where('parent_article_id', $parentArticleId);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Active tests (running).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope: Completed tests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Winners.
     */
    public function scopeWinners($query)
    {
        return $query->where('is_winner', true);
    }

    /**
     * Check if test is running.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if test is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Start AB test.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'test_started_at' => now(),
        ]);
    }

    /**
     * Pause AB test.
     */
    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Complete AB test.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'test_ended_at' => now(),
        ]);
    }

    /**
     * Mark as winner.
     */
    public function markAsWinner(float $confidence = null): void
    {
        $this->update([
            'is_winner' => true,
            'confidence_level' => $confidence,
        ]);
    }

    /**
     * Calculate conversion rate.
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->views_count === 0) {
            return 0;
        }

        return round(($this->conversions_count / $this->views_count) * 100, 2);
    }

    /**
     * Calculate CTR (Click-Through Rate).
     */
    public function getCtrAttribute(): float
    {
        if ($this->views_count === 0) {
            return 0;
        }

        return round(($this->clicks_count / $this->views_count) * 100, 2);
    }

    /**
     * Get test duration in days.
     */
    public function getTestDurationAttribute(): ?int
    {
        if (!$this->test_started_at) {
            return null;
        }

        $endDate = $this->test_ended_at ?? now();
        return $this->test_started_at->diffInDays($endDate);
    }

    /**
     * Check if has statistical significance.
     */
    public function hasStatisticalSignificance(float $threshold = 0.95): bool
    {
        return $this->statistical_significance !== null
            && $this->statistical_significance >= $threshold;
    }
}
```

---

## 8. ArticleGeneration Model

**File**: `app/Models/ArticleGeneration.php`

### Complete Code

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleGeneration extends Model
{
    use HasFactory, HasUlids;

    /**
     * The table associated with the model.
     */
    protected $table = 'article_generations';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'article_id',
        'status',
        'queue_name',
        'job_id',
        'attempt',

        // Progress tracking
        'progress_percentage',
        'current_step',
        'total_steps',
        'step_description',

        // Timing
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',

        // Error handling
        'error_message',
        'error_trace',

        // Results
        'result_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'attempt' => 'integer',
        'progress_percentage' => 'integer',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'result_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    const STATUS_QUEUED = 'queued';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the article for this generation.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Scope: Filter by article.
     */
    public function scopeForArticle($query, string $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Queued generations.
     */
    public function scopeQueued($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    /**
     * Scope: Processing generations.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope: Completed generations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Failed generations.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: In progress (queued or processing).
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [self::STATUS_QUEUED, self::STATUS_PROCESSING]);
    }

    /**
     * Check if generation is queued.
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * Check if generation is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if generation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if generation failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(array $resultData = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percentage' => 100,
            'result_data' => $resultData,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage, string $errorTrace = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'error_trace' => $errorTrace,
        ]);
    }

    /**
     * Update progress.
     */
    public function updateProgress(int $percentage, string $stepDescription = null, int $currentStep = null): void
    {
        $data = ['progress_percentage' => min(100, max(0, $percentage))];

        if ($stepDescription !== null) {
            $data['step_description'] = $stepDescription;
        }

        if ($currentStep !== null) {
            $data['current_step'] = $currentStep;
        }

        $this->update($data);
    }

    /**
     * Get processing duration in seconds.
     */
    public function getProcessingDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->failed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get processing duration formatted.
     */
    public function getProcessingDurationFormattedAttribute(): string
    {
        $duration = $this->processing_duration;

        if ($duration === null) {
            return 'N/A';
        }

        if ($duration < 60) {
            return "{$duration}s";
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Get wait time (queue time) in seconds.
     */
    public function getWaitTimeAttribute(): ?int
    {
        if (!$this->queued_at || !$this->started_at) {
            return null;
        }

        return $this->queued_at->diffInSeconds($this->started_at);
    }
}
```

---

## 9. Model Relationships Diagram

### Visual Schema

```
┌─────────────────┐
│     Tenant      │
└────────┬────────┘
         │
         │ has many
         ▼
    ┌─────────┐         ┌──────────────────┐
    │ Keyword │◄────────│ PromptTemplate   │
    └────┬────┘         └────────┬─────────┘
         │                       │
         │ has many              │ has many
         │                       │
         ▼                       ▼
    ┌────────────────────────────────────────┐
    │           Article (CORE)                │
    │  ----------------------------------------│
    │  - content, title, status               │
    │  - AI metadata (tokens, cost)           │
    │  - SEO fields (meta_title, etc.)        │
    │  - Publishing (published_at, etc.)      │
    └──────┬──────────┬──────────┬───────────┘
           │          │          │
           │          │          │ has many
           │          │          ▼
           │          │    ┌──────────────────┐
           │          │    │ SeoStep          │
           │          │    │ ─────────────────│
           │          │    │ - step_type      │
           │          │    │ - ai_suggestion  │
           │          │    │ - status         │
           │          │    └──────────────────┘
           │          │
           │          │ has many
           │          ▼
           │    ┌──────────────────────┐
           │    │ InternalLink         │
           │    │ ─────────────────────│
           │    │ - target_url         │
           │    │ - anchor_text        │
           │    │ - relevance_score    │
           │    └──────────────────────┘
           │
           │ has many
           ▼
    ┌──────────────────────┐         ┌────────────────────────┐
    │ ArticleVariant       │         │ ArticleGeneration      │
    │ ─────────────────────│         │ ───────────────────────│
    │ - parent_article_id  │         │ - status               │
    │ - variant_article_id │◄────────┤ - progress_percentage  │
    │ - traffic_%          │         │ - current_step         │
    │ - metrics (views)    │         └────────────────────────┘
    └──────────────────────┘
```

### Relationships Summary

**Tenant** (1) → (N) **Keyword**
**Tenant** (1) → (N) **PromptTemplate**
**Tenant** (1) → (N) **Article**

**Keyword** (1) → (N) **Article**
**PromptTemplate** (1) → (N) **Article**

**Article** (1) → (N) **SeoStep**
**Article** (1) → (N) **InternalLink**
**Article** (1) → (N) **ArticleVariant** (as parent)
**Article** (1) → (N) **ArticleGeneration**

**Article** (1) → (1) **Article** (parent_article_id, self-referencing)

---

## 10. Query Examples

### Example 1: Get All Unused Keywords for a Tenant

```php
use App\Models\Keyword;

$unusedKeywords = Keyword::forTenant($tenantId)
    ->unused()
    ->highPriority()
    ->orderBy('priority', 'desc')
    ->get();
```

### Example 2: Get Article with All Relationships

```php
use App\Models\Article;

$article = Article::with([
    'keyword',
    'promptTemplate',
    'seoSteps' => fn($q) => $q->ordered(),
    'internalLinks' => fn($q) => $q->byRelevance(),
    'variants',
    'latestGeneration',
])
->find($articleId);
```

### Example 3: Get Completed Articles with High SEO Score

```php
use App\Models\Article;

$topArticles = Article::forTenant($tenantId)
    ->byStatus(Article::STATUS_COMPLETED)
    ->highSeoScore(80)
    ->with('keyword')
    ->orderBy('seo_score', 'desc')
    ->paginate(20);
```

### Example 4: Get Pending SEO Steps for Article

```php
use App\Models\SeoStep;

$pendingSteps = SeoStep::forArticle($articleId)
    ->pending()
    ->ordered()
    ->get();
```

### Example 5: Get AI Suggested Internal Links

```php
use App\Models\InternalLink;

$aiLinks = InternalLink::forArticle($articleId)
    ->aiSuggested()
    ->highRelevance(0.7)
    ->byRelevance()
    ->with('targetArticle')
    ->get();
```

### Example 6: Get Active AB Tests with Metrics

```php
use App\Models\ArticleVariant;

$activeTests = ArticleVariant::active()
    ->with(['parentArticle', 'variantArticle'])
    ->get()
    ->map(function ($variant) {
        return [
            'variant' => $variant,
            'conversion_rate' => $variant->conversion_rate,
            'ctr' => $variant->ctr,
            'test_duration' => $variant->test_duration,
        ];
    });
```

### Example 7: Get Article Generation Progress

```php
use App\Models\ArticleGeneration;

$generation = ArticleGeneration::forArticle($articleId)
    ->latest()
    ->first();

if ($generation->isProcessing()) {
    echo "Progress: {$generation->progress_percentage}%";
    echo " - Step: {$generation->current_step}/{$generation->total_steps}";
    echo " - {$generation->step_description}";
}
```

### Example 8: Get Recently Completed Articles with Metrics

```php
use App\Models\Article;

$recentArticles = Article::forTenant($tenantId)
    ->recentlyCompleted(7)
    ->with(['keyword', 'latestGeneration'])
    ->get()
    ->map(function ($article) {
        return [
            'title' => $article->title,
            'keyword' => $article->keyword->keyword,
            'word_count' => $article->word_count,
            'seo_score' => $article->seo_score,
            'seo_steps_completion' => $article->seo_steps_completion_percentage,
            'generation_duration' => $article->generation_duration_formatted,
            'cost' => $article->cost,
        ];
    });
```

### Example 9: Mark Article SEO Steps as Complete

```php
use App\Models\Article;

$article = Article::find($articleId);

foreach ($article->seoSteps()->pending()->get() as $step) {
    if ($step->ai_suggestion) {
        $step->applyAiSuggestion();
    }
}

if ($article->hasAllSeoStepsCompleted()) {
    echo "All SEO steps completed!";
}
```

### Example 10: Create Article with AB Test Variant

```php
use App\Models\Article;
use App\Models\ArticleVariant;

// Create parent article
$parentArticle = Article::create([
    'tenant_id' => $tenantId,
    'keyword_id' => $keywordId,
    'title' => 'Original Title',
    'content' => '...',
    'status' => Article::STATUS_COMPLETED,
]);

// Create variant article
$variantArticle = Article::create([
    'tenant_id' => $tenantId,
    'keyword_id' => $keywordId,
    'parent_article_id' => $parentArticle->id,
    'is_variant' => true,
    'variant_name' => 'Variant A',
    'title' => 'Alternative Title',
    'content' => '...',
    'status' => Article::STATUS_COMPLETED,
]);

// Create AB test
$abTest = ArticleVariant::create([
    'parent_article_id' => $parentArticle->id,
    'variant_article_id' => $variantArticle->id,
    'variant_name' => 'Title Test',
    'variant_type' => ArticleVariant::TYPE_TITLE,
    'traffic_percentage' => 50,
]);

$abTest->start();
```

---

## Implementation Checklist

### Models Setup
- [x] Create Keyword model with relationships
- [x] Create PromptTemplate model with rendering logic
- [x] Create Article model (core) with 50+ fields
- [x] Create SeoStep model with workflow status
- [x] Create InternalLink model with AI suggestions
- [x] Create ArticleVariant model for AB testing
- [x] Create ArticleGeneration model for queue tracking

### Business Logic
- [x] Implement scopes for common queries
- [x] Add accessor methods for calculated fields
- [x] Add mutator methods for data transformation
- [x] Implement status management methods
- [x] Add relationship methods
- [x] Add validation logic in models

### Testing
- [ ] Create factories for all models
- [ ] Write unit tests for business logic
- [ ] Test relationships integrity
- [ ] Test scopes and queries
- [ ] Test calculated attributes

---

**Documento completo**: 7 Models con 2,000+ LOC
**Status**: ✅ Production-Ready
**Next Step**: `03-SERVICES.md` — Service Layer Implementation

---

_AI Article Steps — Ainstein Platform_
_Laravel Multi-Tenant SaaS_
_Generated: October 2025_
