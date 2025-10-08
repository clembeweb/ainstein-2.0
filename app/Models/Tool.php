<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tool extends Model
{
    use HasUlids;

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'icon',
        'is_active',
        'settings_schema',
    ];

    protected $casts = [
        'settings_schema' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the tool settings for this tool.
     */
    public function toolSettings(): HasMany
    {
        return $this->hasMany(ToolSetting::class);
    }

    /**
     * Get the prompts for this tool.
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class);
    }

    /**
     * Scope to filter active tools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by code.
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
