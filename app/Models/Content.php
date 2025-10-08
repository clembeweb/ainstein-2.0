<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'url',
        'content_type',
        'source',
        'source_id',
        'title',
        'keyword',
        'language',
        'meta_data',
        'status',
        'imported_at',
        'last_synced_at',
        'created_by',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'imported_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the content.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this content.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the content generations for this content.
     */
    public function generations(): HasMany
    {
        return $this->hasMany(ContentGeneration::class, 'page_id');
    }

    /**
     * Scope to filter active contents.
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
     * Scope to filter by source.
     */
    public function scopeFromSource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to filter by content type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('content_type', $type);
    }
}
