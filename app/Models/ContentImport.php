<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentImport extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'import_type',
        'cms_connection_id',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'errors' => 'array',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the import.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the CMS connection for this import (if applicable).
     */
    public function cmsConnection(): BelongsTo
    {
        return $this->belongsTo(CmsConnection::class);
    }

    /**
     * Get the user who created this import.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by import type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('import_type', $type);
    }

    /**
     * Scope to filter pending imports.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter processing imports.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to filter completed imports.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter failed imports.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows <= 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    /**
     * Get the success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_rows <= 0) {
            return 0;
        }

        return round(($this->successful_rows / $this->processed_rows) * 100, 2);
    }
}
