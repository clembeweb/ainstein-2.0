<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'key',
        'last_used',
        'expires_at',
        'is_active',
        'tenant_id',
        'permissions',
        'created_by',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'last_used' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'revoked_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'revoked';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->expires_at && $this->expires_at <= now()->addDays(7)) {
            return 'expiring_soon';
        }

        return 'active';
    }
}
