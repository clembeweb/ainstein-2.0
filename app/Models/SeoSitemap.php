<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoSitemap extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'audit_id',
        'url',
        'url_hash',
        'type',
        'entries_count',
        'valid_entries',
        'invalid_entries',
        'last_modified',
        'status_code',
        'is_valid_xml',
        'parse_errors',
        'discovered_urls',
    ];

    protected $casts = [
        'entries_count' => 'integer',
        'valid_entries' => 'integer',
        'invalid_entries' => 'integer',
        'last_modified' => 'datetime',
        'status_code' => 'integer',
        'is_valid_xml' => 'boolean',
        'parse_errors' => 'array',
        'discovered_urls' => 'array',
    ];

    /**
     * Relationship: Sitemap belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Sitemap belongs to an Audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'audit_id');
    }

    /**
     * Scope: Filter sitemaps by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only sitemap indexes
     */
    public function scopeIndexes($query)
    {
        return $query->where('type', 'index');
    }

    /**
     * Scope: Only regular sitemaps
     */
    public function scopeRegular($query)
    {
        return $query->where('type', 'regular');
    }

    /**
     * Scope: Only valid sitemaps
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid_xml', true);
    }

    /**
     * Helper: Check if sitemap is index
     */
    public function isIndex(): bool
    {
        return $this->type === 'index';
    }

    /**
     * Helper: Check if sitemap has errors
     */
    public function hasErrors(): bool
    {
        return !$this->is_valid_xml || !empty($this->parse_errors);
    }
}
