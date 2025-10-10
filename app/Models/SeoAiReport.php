<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAiReport extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'audit_id',
        'provider',
        'model',
        'prompt_template',
        'executive_summary',
        'prioritized_actions',
        'quick_wins',
        'risks_dependencies',
        'long_term_recommendations',
        'tokens_input',
        'tokens_output',
        'tokens_total',
        'cost_usd',
        'generation_duration_ms',
        'status',
        'error_message',
        'generated_at',
    ];

    protected $casts = [
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
        'tokens_total' => 'integer',
        'cost_usd' => 'decimal:6',
        'generation_duration_ms' => 'integer',
        'generated_at' => 'datetime',
    ];

    /**
     * Relationship: AI Report belongs to a Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship: AI Report belongs to an Audit
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'audit_id');
    }

    /**
     * Scope: Filter reports by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Only completed reports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Only failed reports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: By provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Helper: Check if report is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Helper: Check if report failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Helper: Get full report markdown
     */
    public function getFullReportMarkdown(): string
    {
        $sections = [];

        if ($this->executive_summary) {
            $sections[] = "## Executive Summary\n\n" . $this->executive_summary;
        }

        if ($this->prioritized_actions) {
            $sections[] = "## Prioritized Actions\n\n" . $this->prioritized_actions;
        }

        if ($this->quick_wins) {
            $sections[] = "## Quick Wins\n\n" . $this->quick_wins;
        }

        if ($this->risks_dependencies) {
            $sections[] = "## Risks & Dependencies\n\n" . $this->risks_dependencies;
        }

        if ($this->long_term_recommendations) {
            $sections[] = "## Long-term Recommendations\n\n" . $this->long_term_recommendations;
        }

        return implode("\n\n", $sections);
    }
}
