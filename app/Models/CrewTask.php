<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrewTask extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'crew_id',
        'agent_id',
        'description',
        'expected_output',
        'context',
        'dependencies',
        'order',
    ];

    protected $casts = [
        'context' => 'array',
        'dependencies' => 'array',
        'order' => 'integer',
    ];

    /**
     * Get the crew that owns this task.
     */
    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    /**
     * Get the agent assigned to this task.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(CrewAgent::class, 'agent_id');
    }

    /**
     * Get execution logs for this task.
     */
    public function executionLogs(): HasMany
    {
        return $this->hasMany(CrewExecutionLog::class, 'task_id');
    }

    /**
     * Scope to order by execution order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Check if task has dependencies.
     */
    public function hasDependencies(): bool
    {
        return !empty($this->dependencies);
    }
}
