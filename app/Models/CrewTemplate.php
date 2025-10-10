<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewTemplate extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'category',
        'crew_configuration',
        'is_system',
        'is_public',
        'usage_count',
        'rating',
    ];

    protected $casts = [
        'crew_configuration' => 'array',
        'is_system' => 'boolean',
        'is_public' => 'boolean',
        'usage_count' => 'integer',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the tenant that owns this template.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to filter user templates.
     */
    public function scopeUser($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to filter public templates.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Check if template is system template.
     */
    public function isSystem(): bool
    {
        return $this->is_system === true;
    }

    /**
     * Check if template is public.
     */
    public function isPublic(): bool
    {
        return $this->is_public === true;
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
