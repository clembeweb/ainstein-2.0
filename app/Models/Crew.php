<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crew extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'process_type',
        'status',
        'configuration',
    ];

    protected $casts = [
        'configuration' => 'array',
    ];

    /**
     * Get the tenant that owns the crew.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this crew.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the agents for this crew.
     */
    public function agents(): HasMany
    {
        return $this->hasMany(CrewAgent::class)->orderBy('order');
    }

    /**
     * Get the tasks for this crew.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(CrewTask::class)->orderBy('order');
    }

    /**
     * Get the executions for this crew.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(CrewExecution::class);
    }

    /**
     * Scope to filter active crews.
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
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if crew is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if crew is sequential.
     */
    public function isSequential(): bool
    {
        return $this->process_type === 'sequential';
    }

    /**
     * Get total executions count.
     */
    public function getTotalExecutionsAttribute(): int
    {
        return $this->executions()->count();
    }

    /**
     * Get successful executions count.
     */
    public function getSuccessfulExecutionsAttribute(): int
    {
        return $this->executions()->where('status', 'completed')->count();
    }

    /**
     * Get success rate.
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->total_executions;
        if ($total === 0) {
            return 0;
        }
        return round(($this->successful_executions / $total) * 100, 2);
    }
}
