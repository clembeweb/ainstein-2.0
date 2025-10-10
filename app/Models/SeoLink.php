<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoLink extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'audit_id',
        'from_page_id',
        'to_url',
        'to_url_hash',
        'to_page_id',
        'type',
        'anchor_text',
        'rel',
        'nofollow',
        'position',
        'target_status_code',
        'is_broken',
    ];

    protected $casts = [
        'nofollow' => 'boolean',
        'is_broken' => 'boolean',
        'target_status_code' => 'integer',
    ];

    /**
     * Relationship: Link belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Link belongs to an Audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'audit_id');
    }

    /**
     * Relationship: Link from Page
     */
    public function fromPage(): BelongsTo
    {
        return $this->belongsTo(SeoPage::class, 'from_page_id');
    }

    /**
     * Relationship: Link to Page (if internal)
     */
    public function toPage(): BelongsTo
    {
        return $this->belongsTo(SeoPage::class, 'to_page_id');
    }

    /**
     * Scope: Filter links by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only internal links
     */
    public function scopeInternal($query)
    {
        return $query->where('type', 'internal');
    }

    /**
     * Scope: Only external links
     */
    public function scopeExternal($query)
    {
        return $query->where('type', 'external');
    }

    /**
     * Scope: Only broken links
     */
    public function scopeBroken($query)
    {
        return $query->where('is_broken', true);
    }

    /**
     * Helper: Check if link is internal
     */
    public function isInternal(): bool
    {
        return $this->type === 'internal';
    }

    /**
     * Helper: Check if link is external
     */
    public function isExternal(): bool
    {
        return $this->type === 'external';
    }
}
