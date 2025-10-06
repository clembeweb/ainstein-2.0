<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvCampaign extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'name',
        'info',
        'keywords',
        'type',
        'language',
        'url',
        'tokens_used',
        'model_used',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
    ];

    /**
     * Relationship: Campaign belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Campaign has many generated assets
     */
    public function assets(): HasMany
    {
        return $this->hasMany(AdvGeneratedAsset::class, 'campaign_id');
    }

    /**
     * Scope: Filter campaigns by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Accessor: Get keywords as array
     */
    public function getKeywordsArrayAttribute(): array
    {
        if (empty($this->keywords)) {
            return [];
        }

        return array_map('trim', explode(',', $this->keywords));
    }

    /**
     * Helper: Check if campaign is RSA type
     */
    public function isRsa(): bool
    {
        return $this->type === 'rsa';
    }

    /**
     * Helper: Check if campaign is PMAX type
     */
    public function isPmax(): bool
    {
        return $this->type === 'pmax';
    }
}
