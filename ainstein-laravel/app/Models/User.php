<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'email',
        'password_hash',
        'name',
        'avatar',
        'role',
        'is_super_admin',
        'is_active',
        'email_verified',
        'email_verified_at',
        'onboarding_completed',
        'onboarding_tools_completed',
        'preferences',
        'last_login',
        'tenant_id',
        'social_provider',
        'social_id',
        'social_avatar',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'preferences' => 'array',
        'onboarding_tools_completed' => 'array',
        'is_super_admin' => 'boolean',
        'is_active' => 'boolean',
        'email_verified' => 'boolean',
        'onboarding_completed' => 'boolean',
        'last_login' => 'datetime',
        'email_verified_at' => 'datetime',
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

    // Override the password attribute to use password_hash
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = $value;
    }

    public function getPasswordAttribute()
    {
        return $this->password_hash;
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('email_verified', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Helper methods
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function hasSocialAuth(): bool
    {
        return !empty($this->social_provider) && !empty($this->social_id);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->social_avatar) {
            return $this->social_avatar;
        }

        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }

        // Fallback to Gravatar
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=mp&s=150';
    }

    // Onboarding tools helper methods
    public function hasCompletedToolOnboarding(string $tool): bool
    {
        $completed = $this->onboarding_tools_completed ?? [];
        return in_array($tool, $completed);
    }

    public function markToolOnboardingComplete(string $tool): void
    {
        $completed = $this->onboarding_tools_completed ?? [];

        if (!in_array($tool, $completed)) {
            $completed[] = $tool;
            $this->onboarding_tools_completed = $completed;
            $this->save();
        }
    }

    public function resetToolOnboarding(string $tool = null): void
    {
        if ($tool) {
            $completed = $this->onboarding_tools_completed ?? [];
            $this->onboarding_tools_completed = array_diff($completed, [$tool]);
        } else {
            $this->onboarding_tools_completed = [];
        }
        $this->save();
    }

    // Filament User Implementation
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_super_admin && $this->is_active;
    }
}
