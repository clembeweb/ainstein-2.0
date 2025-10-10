<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeoAudit extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'config_snapshot',
        'pages_crawled',
        'pages_indexable',
        'pages_non_indexable',
        'orphan_pages',
        'issues_total',
        'issues_error',
        'issues_warn',
        'issues_info',
        'broken_internal_links',
        'broken_external_links',
        'broken_images',
        'avg_load_time_ms',
        'avg_page_size_bytes',
        'avg_depth',
        'health_score',
        'health_score_previous',
        'health_score_delta',
        'sitemap_entries_found',
        'sitemap_entries_valid',
        'error_message',
        'error_trace',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_seconds' => 'integer',
        'config_snapshot' => 'array',
        'pages_crawled' => 'integer',
        'pages_indexable' => 'integer',
        'pages_non_indexable' => 'integer',
        'orphan_pages' => 'integer',
        'issues_total' => 'integer',
        'issues_error' => 'integer',
        'issues_warn' => 'integer',
        'issues_info' => 'integer',
        'broken_internal_links' => 'integer',
        'broken_external_links' => 'integer',
        'broken_images' => 'integer',
        'avg_load_time_ms' => 'integer',
        'avg_page_size_bytes' => 'integer',
        'avg_depth' => 'decimal:2',
        'health_score' => 'decimal:2',
        'health_score_previous' => 'decimal:2',
        'health_score_delta' => 'decimal:2',
        'sitemap_entries_found' => 'integer',
        'sitemap_entries_valid' => 'integer',
    ];

    /**
     * Relationship: Audit belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Audit belongs to a Project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(SeoProject::class, 'project_id');
    }

    /**
     * Relationship: Audit has many pages
     */
    public function pages(): HasMany
    {
        return $this->hasMany(SeoPage::class, 'audit_id');
    }

    /**
     * Relationship: Audit has many issues
     */
    public function issues(): HasMany
    {
        return $this->hasMany(SeoIssue::class, 'audit_id');
    }

    /**
     * Relationship: Audit has many links
     */
    public function links(): HasMany
    {
        return $this->hasMany(SeoLink::class, 'audit_id');
    }

    /**
     * Relationship: Audit has many resources
     */
    public function resources(): HasMany
    {
        return $this->hasMany(SeoResource::class, 'audit_id');
    }

    /**
     * Relationship: Audit has many sitemaps
     */
    public function sitemaps(): HasMany
    {
        return $this->hasMany(SeoSitemap::class, 'audit_id');
    }

    /**
     * Relationship: Audit has one AI report
     */
    public function aiReport(): HasOne
    {
        return $this->hasOne(SeoAiReport::class, 'audit_id');
    }

    /**
     * Scope: Filter audits by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only completed audits
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Only running audits
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Only failed audits
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Helper: Check if audit is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Helper: Check if audit is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Helper: Check if audit has improved
     */
    public function hasImproved(): bool
    {
        return $this->health_score_delta > 0;
    }

    /**
     * Helper: Check if audit has worsened
     */
    public function hasWorsened(): bool
    {
        return $this->health_score_delta < 0;
    }

    /**
     * Helper: Get health score color/status
     */
    public function getHealthStatus(): string
    {
        if ($this->health_score >= 90) return 'excellent';
        if ($this->health_score >= 70) return 'good';
        if ($this->health_score >= 50) return 'needs_attention';
        return 'critical';
    }
}
