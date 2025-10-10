<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoIssue extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'audit_id',
        'page_id',
        'issue_code',
        'severity',
        'category',
        'message',
        'evidence',
        'occurrence_count',
        'first_detected_at',
        'last_detected_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'occurrence_count' => 'integer',
        'first_detected_at' => 'datetime',
        'last_detected_at' => 'datetime',
    ];

    /**
     * Relationship: Issue belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: Issue belongs to an Audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'audit_id');
    }

    /**
     * Relationship: Issue belongs to a Page (optional)
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoPage::class, 'page_id');
    }

    /**
     * Scope: Filter issues by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only ERROR severity
     */
    public function scopeErrors($query)
    {
        return $query->where('severity', 'ERROR');
    }

    /**
     * Scope: Only WARN severity
     */
    public function scopeWarnings($query)
    {
        return $query->where('severity', 'WARN');
    }

    /**
     * Scope: Only INFO severity
     */
    public function scopeInfo($query)
    {
        return $query->where('severity', 'INFO');
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Helper: Check if issue is critical
     */
    public function isCritical(): bool
    {
        return $this->severity === 'ERROR';
    }

    /**
     * Helper: Get severity color/class
     */
    public function getSeverityClass(): string
    {
        return match($this->severity) {
            'ERROR' => 'danger',
            'WARN' => 'warning',
            'INFO' => 'info',
            default => 'secondary'
        };
    }
}
