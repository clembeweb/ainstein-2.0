<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsConnection extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'cms_type',
        'connection_name',
        'site_url',
        'api_key',
        'api_secret',
        'status',
        'last_sync_at',
        'last_error',
        'sync_config',
        'created_by',
    ];

    protected $casts = [
        'sync_config' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the CMS connection.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this connection.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the content imports for this CMS connection.
     */
    public function contentImports(): HasMany
    {
        return $this->hasMany(ContentImport::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active connections.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by CMS type.
     */
    public function scopeByCmsType($query, $cmsType)
    {
        return $query->where('cms_type', $cmsType);
    }

    /**
     * Check if connection is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if connection has errors.
     */
    public function hasErrors(): bool
    {
        return $this->status === 'error' && !empty($this->last_error);
    }
}
