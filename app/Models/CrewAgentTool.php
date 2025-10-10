<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class CrewAgentTool extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
        'type',
        'configuration',
        'is_active',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to filter active tools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get builtin tools.
     */
    public function scopeBuiltin($query)
    {
        return $query->where('type', 'builtin');
    }

    /**
     * Check if tool is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if tool is builtin.
     */
    public function isBuiltin(): bool
    {
        return $this->type === 'builtin';
    }
}
