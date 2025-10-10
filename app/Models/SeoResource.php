<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoResource extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'audit_id',
        'page_id',
        'url',
        'url_hash',
        'type',
        'status_code',
        'size_bytes',
        'load_time_ms',
        'source',
        'alt',
        'has_dimensions',
        'is_broken',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'size_bytes' => 'integer',
        'load_time_ms' => 'integer',
        'has_dimensions' => 'boolean',
        'is_broken' => 'boolean',
    ];

    /**
     * Relationship: Resource belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Resource belongs to an Audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'audit_id');
    }

    /**
     * Relationship: Resource belongs to a Page
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoPage::class, 'page_id');
    }

    /**
     * Scope: Filter resources by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only images
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    /**
     * Scope: Only CSS files
     */
    public function scopeCss($query)
    {
        return $query->where('type', 'css');
    }

    /**
     * Scope: Only JS files
     */
    public function scopeJs($query)
    {
        return $query->where('type', 'js');
    }

    /**
     * Scope: Only broken resources
     */
    public function scopeBroken($query)
    {
        return $query->where('is_broken', true);
    }

    /**
     * Helper: Check if resource is image
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }
}
