<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrewExecution extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'crew_id',
        'tenant_id',
        'triggered_by',
        'input_variables',
        'status',
        'started_at',
        'completed_at',
        'total_tokens_used',
        'cost',
        'results',
        'error_message',
        'execution_log',
        'retry_count',
        'metadata',
    ];

    protected $casts = [
        'input_variables' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_tokens_used' => 'integer',
        'cost' => 'decimal:4',
        'results' => 'array',
        'execution_log' => 'array',
        'retry_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the crew for this execution.
     */
    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    /**
     * Get the tenant for this execution.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who triggered this execution.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Get detailed execution logs.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CrewExecutionLog::class)->orderBy('logged_at');
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
     * Scope to get running executions.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope to get completed executions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed executions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if execution is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if execution is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if execution failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get execution duration in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($end);
    }
}
