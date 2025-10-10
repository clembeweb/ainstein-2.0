<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoPage extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'audit_id',
        'url',
        'url_hash',
        'status_code',
        'load_time_ms',
        'size_bytes',
        'content_type',
        'depth',
        'rendered_js',
        'content_hash',
        'title',
        'meta_description',
        'meta_robots',
        'canonical',
        'h1',
        'h2_first',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_types',
        'internal_links_count',
        'external_links_count',
        'images_count',
        'css_count',
        'js_count',
        'indexable',
        'indexability_reasons',
        'in_sitemap',
        'hreflang_alternates',
        'crawled_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'load_time_ms' => 'integer',
        'size_bytes' => 'integer',
        'depth' => 'integer',
        'rendered_js' => 'boolean',
        'internal_links_count' => 'integer',
        'external_links_count' => 'integer',
        'images_count' => 'integer',
        'css_count' => 'integer',
        'js_count' => 'integer',
        'indexable' => 'boolean',
        'in_sitemap' => 'boolean',
        'schema_types' => 'array',
        'indexability_reasons' => 'array',
        'hreflang_alternates' => 'array',
        'crawled_at' => 'datetime',
    ];

    /**
     * Relationship: Page belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Page belongs to an Audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'audit_id');
    }

    /**
     * Relationship: Page has many issues
     */
    public function issues(): HasMany
    {
        return $this->hasMany(SeoIssue::class, 'page_id');
    }

    /**
     * Relationship: Page has many outgoing links
     */
    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(SeoLink::class, 'from_page_id');
    }

    /**
     * Relationship: Page has many incoming links
     */
    public function incomingLinks(): HasMany
    {
        return $this->hasMany(SeoLink::class, 'to_page_id');
    }

    /**
     * Relationship: Page has many resources
     */
    public function resources(): HasMany
    {
        return $this->hasMany(SeoResource::class, 'page_id');
    }

    /**
     * Scope: Filter pages by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only indexable pages
     */
    public function scopeIndexable($query)
    {
        return $query->where('indexable', true);
    }

    /**
     * Scope: Pages with errors (4xx, 5xx)
     */
    public function scopeWithErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    /**
     * Scope: Pages in sitemap
     */
    public function scopeInSitemap($query)
    {
        return $query->where('in_sitemap', true);
    }

    /**
     * Helper: Check if page has errors
     */
    public function hasErrors(): bool
    {
        return $this->status_code >= 400;
    }

    /**
     * Helper: Check if page is success (200)
     */
    public function isSuccess(): bool
    {
        return $this->status_code === 200;
    }

    /**
     * Helper: Get status code type
     */
    public function getStatusType(): string
    {
        if ($this->status_code >= 500) return 'server_error';
        if ($this->status_code >= 400) return 'client_error';
        if ($this->status_code >= 300) return 'redirect';
        if ($this->status_code >= 200) return 'success';
        return 'unknown';
    }
}
