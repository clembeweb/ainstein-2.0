<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'plan_type',
        'tokens_monthly_limit',
        'tokens_used_current',
        'status',
        'theme_config',
        'brand_config',
        'features',
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    protected $casts = [
        'theme_config' => 'array',
        'brand_config' => 'array',
        'features' => 'array',
        'tokens_monthly_limit' => 'integer',
        'tokens_used_current' => 'integer',
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function contentGenerations(): HasMany
    {
        return $this->hasMany(ContentGeneration::class);
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class);
    }

    public function cmsConnections(): HasMany
    {
        return $this->hasMany(CmsConnection::class);
    }

    public function gscConnections(): HasMany
    {
        return $this->hasMany(GscConnection::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function usageHistories(): HasMany
    {
        return $this->hasMany(UsageHistory::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function toolSettings(): HasMany
    {
        return $this->hasMany(ToolSetting::class);
    }

    public function contentImports(): HasMany
    {
        return $this->hasMany(ContentImport::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function advCampaigns(): HasMany
    {
        return $this->hasMany(AdvCampaign::class);
    }

    public function crews(): HasMany
    {
        return $this->hasMany(Crew::class);
    }

    public function crewExecutions(): HasMany
    {
        return $this->hasMany(CrewExecution::class);
    }

    public function crewTemplates(): HasMany
    {
        return $this->hasMany(CrewTemplate::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPlan($query, $planType)
    {
        return $query->where('plan_type', $planType);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getTokenUsagePercentAttribute(): float
    {
        if ($this->tokens_monthly_limit <= 0) {
            return 0;
        }

        return round(($this->tokens_used_current / $this->tokens_monthly_limit) * 100, 2);
    }

    public function getRemainingTokensAttribute(): int
    {
        return max(0, $this->tokens_monthly_limit - $this->tokens_used_current);
    }

    public function getOwnerAttribute()
    {
        return $this->users()->where('role', 'owner')->first();
    }

    public function getAdminsAttribute()
    {
        return $this->users()->whereIn('role', ['owner', 'admin'])->get();
    }

    public function getActiveUsersCountAttribute(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    public function getActivePagesCountAttribute(): int
    {
        return $this->pages()->where('status', 'active')->count();
    }

    public function getActiveApiKeysCountAttribute(): int
    {
        return $this->apiKeys()->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })->count();
    }

    public function canGenerateContent(): bool
    {
        return $this->isActive() && $this->remaining_tokens > 0;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getPlanLimits(): array
    {
        return match ($this->plan_type) {
            'free' => [
                'pages' => 10,
                'api_keys' => 2,
                'users' => 1,
                'tokens_monthly' => 10000,
                'features' => ['basic_generation']
            ],
            'basic' => [
                'pages' => 50,
                'api_keys' => 5,
                'users' => 3,
                'tokens_monthly' => 50000,
                'features' => ['basic_generation', 'advanced_prompts']
            ],
            'pro' => [
                'pages' => 200,
                'api_keys' => 10,
                'users' => 10,
                'tokens_monthly' => 200000,
                'features' => ['basic_generation', 'advanced_prompts', 'custom_templates', 'analytics']
            ],
            'enterprise' => [
                'pages' => 1000,
                'api_keys' => 25,
                'users' => 50,
                'tokens_monthly' => 1000000,
                'features' => ['basic_generation', 'advanced_prompts', 'custom_templates', 'analytics', 'priority_support', 'custom_integrations']
            ],
            default => [
                'pages' => 10,
                'api_keys' => 2,
                'users' => 1,
                'tokens_monthly' => 10000,
                'features' => ['basic_generation']
            ]
        };
    }

    public function isWithinLimits(): array
    {
        $limits = $this->getPlanLimits();

        return [
            'pages' => $this->pages()->count() <= $limits['pages'],
            'api_keys' => $this->active_api_keys_count <= $limits['api_keys'],
            'users' => $this->active_users_count <= $limits['users'],
            'tokens' => $this->tokens_used_current <= $limits['tokens_monthly'],
        ];
    }
}
