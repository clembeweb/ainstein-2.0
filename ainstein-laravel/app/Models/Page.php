<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'url_path',
        'keyword',
        'category',
        'language',
        'cms_type',
        'cms_page_id',
        'status',
        'priority',
        'metadata',
        'last_synced',
        'tenant_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'priority' => 'integer',
        'last_synced' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(ContentGeneration::class);
    }

    public function contentGenerations(): HasMany
    {
        return $this->hasMany(ContentGeneration::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    public function scopeByCmsType($query, $cmsType)
    {
        return $query->where('cms_type', $cmsType);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getGenerationsCountAttribute(): int
    {
        return $this->generations()->count();
    }

    public function getCompletedGenerationsCountAttribute(): int
    {
        return $this->generations()->where('status', 'completed')->count();
    }

    public function getFailedGenerationsCountAttribute(): int
    {
        return $this->generations()->where('status', 'failed')->count();
    }

    public function getLastGenerationAttribute()
    {
        return $this->generations()->latest()->first();
    }

    public function getSuccessRateAttribute(): float
    {
        $total = $this->generations_count;
        if ($total === 0) {
            return 0;
        }

        return round(($this->completed_generations_count / $total) * 100, 2);
    }

    public function getPriorityTextAttribute(): string
    {
        return match ($this->priority) {
            1 => 'Low',
            2 => 'Normal',
            3 => 'High',
            4 => 'Critical',
            default => 'Normal',
        };
    }

    public function getDisplayUrlAttribute(): string
    {
        return $this->url_path;
    }
}
