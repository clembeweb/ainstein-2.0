<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prompt extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'alias',
        'description',
        'template',
        'variables',
        'category',
        'is_active',
        'is_system',
        'is_global',
        'tenant_id',
        'tool_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_global' => 'boolean',
    ];

    /**
     * Get the tenant that owns the prompt.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the tool that this prompt belongs to.
     */
    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    /**
     * Scope to filter active prompts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter global prompts.
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope to filter system prompts.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by tool.
     */
    public function scopeForTool($query, $toolId)
    {
        return $query->where('tool_id', $toolId);
    }

    /**
     * Scope to get prompts available for a specific tool.
     * Includes tool-specific prompts and global prompts.
     */
    public function scopeAvailableForTool($query, $toolId)
    {
        return $query->where(function ($q) use ($toolId) {
            $q->where('tool_id', $toolId)
              ->orWhere('is_global', true);
        });
    }
}
