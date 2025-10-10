<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrewAgent extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'crew_id',
        'name',
        'role',
        'goal',
        'backstory',
        'tools',
        'llm_config',
        'max_iterations',
        'order',
    ];

    protected $casts = [
        'tools' => 'array',
        'llm_config' => 'array',
        'max_iterations' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the crew that owns this agent.
     */
    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    /**
     * Get the tasks assigned to this agent.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(CrewTask::class, 'agent_id');
    }

    /**
     * Get execution logs for this agent.
     */
    public function executionLogs(): HasMany
    {
        return $this->hasMany(CrewExecutionLog::class, 'agent_id');
    }

    /**
     * Scope to order by execution order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
