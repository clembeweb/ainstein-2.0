<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeoProject extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'domain',
        'description',
        'include_subdomains',
        'scope_path',
        'include_patterns',
        'exclude_patterns',
        'auth_type',
        'auth_username',
        'auth_password',
        'auth_cookie_header',
        'param_whitelist',
        'param_blacklist',
        'normalize_param_order',
        'user_agent',
        'obey_robots',
        'max_concurrency',
        'delay_ms',
        'timeout_seconds',
        'max_pages',
        'max_depth',
        'recurring_schedule',
        'schedule_time',
        'last_scheduled_at',
        'is_active',
        'last_audit_at',
        'config_snapshot',
    ];

    protected $casts = [
        'include_subdomains' => 'boolean',
        'normalize_param_order' => 'boolean',
        'obey_robots' => 'boolean',
        'is_active' => 'boolean',
        'max_concurrency' => 'integer',
        'delay_ms' => 'integer',
        'timeout_seconds' => 'integer',
        'max_pages' => 'integer',
        'max_depth' => 'integer',
        'last_scheduled_at' => 'datetime',
        'last_audit_at' => 'datetime',
        'config_snapshot' => 'array',
        'include_patterns' => 'array',
        'exclude_patterns' => 'array',
        'param_whitelist' => 'array',
        'param_blacklist' => 'array',
    ];

    /**
     * Relationship: Project belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Project has many audits
     */
    public function audits(): HasMany
    {
        return $this->hasMany(SeoAudit::class, 'project_id');
    }

    /**
     * Relationship: Get latest audit
     */
    public function latestAudit()
    {
        return $this->hasOne(SeoAudit::class, 'project_id')->latestOfMany();
    }

    /**
     * Scope: Filter projects by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only active projects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Projects with recurring schedule
     */
    public function scopeScheduled($query)
    {
        return $query->whereIn('recurring_schedule', ['daily', 'weekly', 'monthly']);
    }

    /**
     * Helper: Check if project has recurring schedule
     */
    public function hasSchedule(): bool
    {
        return in_array($this->recurring_schedule, ['daily', 'weekly', 'monthly']);
    }

    /**
     * Helper: Check if authentication is configured
     */
    public function hasAuthentication(): bool
    {
        return $this->auth_type !== 'none' && !empty($this->auth_username);
    }

    /**
     * Helper: Get full domain URL
     */
    public function getFullDomainUrl(): string
    {
        $protocol = 'https://';
        return $protocol . $this->domain . ($this->scope_path ?? '');
    }
}
