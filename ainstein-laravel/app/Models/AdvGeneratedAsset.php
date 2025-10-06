<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvGeneratedAsset extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'campaign_id',
        'type',
        'titles',
        'long_titles',
        'descriptions',
        'ai_quality_score',
    ];

    protected $casts = [
        'titles' => 'array',
        'long_titles' => 'array',
        'descriptions' => 'array',
        'ai_quality_score' => 'decimal:2',
    ];

    /**
     * Relationship: Asset belongs to a Campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdvCampaign::class, 'campaign_id');
    }

    /**
     * Accessor: Get titles count
     */
    public function getTitlesCountAttribute(): int
    {
        return is_array($this->titles) ? count($this->titles) : 0;
    }

    /**
     * Accessor: Get long titles count
     */
    public function getLongTitlesCountAttribute(): int
    {
        return is_array($this->long_titles) ? count($this->long_titles) : 0;
    }

    /**
     * Accessor: Get descriptions count
     */
    public function getDescriptionsCountAttribute(): int
    {
        return is_array($this->descriptions) ? count($this->descriptions) : 0;
    }

    /**
     * Helper: Check if asset is RSA type
     */
    public function isRsa(): bool
    {
        return $this->type === 'rsa';
    }

    /**
     * Helper: Check if asset is PMAX type
     */
    public function isPmax(): bool
    {
        return $this->type === 'pmax';
    }

    /**
     * Validation: Check RSA limits
     * RSA: 3-15 titles (max 30 chars), 2-4 descriptions (max 90 chars)
     */
    public function validateRsaLimits(): array
    {
        $errors = [];

        if ($this->titles_count < 3 || $this->titles_count > 15) {
            $errors[] = 'RSA requires 3-15 titles';
        }

        if ($this->descriptions_count < 2 || $this->descriptions_count > 4) {
            $errors[] = 'RSA requires 2-4 descriptions';
        }

        foreach ($this->titles ?? [] as $title) {
            if (mb_strlen($title) > 30) {
                $errors[] = "Title exceeds 30 chars: {$title}";
            }
        }

        foreach ($this->descriptions ?? [] as $desc) {
            if (mb_strlen($desc) > 90) {
                $errors[] = "Description exceeds 90 chars: " . mb_substr($desc, 0, 50) . "...";
            }
        }

        return $errors;
    }

    /**
     * Validation: Check PMAX limits
     * PMAX: 3-5 short titles (30 chars), 1-5 long titles (90 chars), 1-5 descriptions (90 chars)
     */
    public function validatePmaxLimits(): array
    {
        $errors = [];

        if ($this->titles_count < 3 || $this->titles_count > 5) {
            $errors[] = 'PMAX requires 3-5 short titles';
        }

        if ($this->long_titles_count < 1 || $this->long_titles_count > 5) {
            $errors[] = 'PMAX requires 1-5 long titles';
        }

        if ($this->descriptions_count < 1 || $this->descriptions_count > 5) {
            $errors[] = 'PMAX requires 1-5 descriptions';
        }

        foreach ($this->titles ?? [] as $title) {
            if (mb_strlen($title) > 30) {
                $errors[] = "Short title exceeds 30 chars: {$title}";
            }
        }

        foreach ($this->long_titles ?? [] as $title) {
            if (mb_strlen($title) > 90) {
                $errors[] = "Long title exceeds 90 chars: " . mb_substr($title, 0, 50) . "...";
            }
        }

        foreach ($this->descriptions ?? [] as $desc) {
            if (mb_strlen($desc) > 90) {
                $errors[] = "Description exceeds 90 chars: " . mb_substr($desc, 0, 50) . "...";
            }
        }

        return $errors;
    }
}
