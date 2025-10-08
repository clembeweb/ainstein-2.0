<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'tokens_monthly_limit',
        'features',
        'max_users',
        'max_api_keys',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'tokens_monthly_limit' => 'integer',
        'features' => 'array',
        'max_users' => 'integer',
        'max_api_keys' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // Relationships
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_type', 'slug');
    }

    // Methods
    public function getYearlyDiscount(): int
    {
        if ($this->price_monthly <= 0 || $this->price_yearly <= 0) {
            return 0;
        }

        $yearlyEquivalent = $this->price_monthly * 12;
        $discount = (($yearlyEquivalent - $this->price_yearly) / $yearlyEquivalent) * 100;

        return round($discount);
    }

    public function getFormattedFeatures(): array
    {
        return is_array($this->features) ? $this->features : [];
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->getFormattedFeatures());
    }
}