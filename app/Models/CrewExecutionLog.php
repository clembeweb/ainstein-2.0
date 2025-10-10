<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewExecutionLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'crew_execution_id',
        'task_id',
        'agent_id',
        'level',
        'message',
        'data',
        'tokens_used',
        'logged_at',
    ];

    protected $casts = [
        'data' => 'array',
        'tokens_used' => 'integer',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the execution this log belongs to.
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(CrewExecution::class, 'crew_execution_id');
    }

    /**
     * Get the task this log relates to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(CrewTask::class, 'task_id');
    }

    /**
     * Get the agent this log relates to.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(CrewAgent::class, 'agent_id');
    }

    /**
     * Scope to filter by level.
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to get error logs.
     */
    public function scopeErrors($query)
    {
        return $query->where('level', 'error');
    }

    /**
     * Scope to get warning logs.
     */
    public function scopeWarnings($query)
    {
        return $query->where('level', 'warning');
    }

    /**
     * Check if log is an error.
     */
    public function isError(): bool
    {
        return $this->level === 'error';
    }
}
