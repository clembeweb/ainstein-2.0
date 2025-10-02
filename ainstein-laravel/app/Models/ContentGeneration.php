<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContentGeneration extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'prompt_type',
        'prompt_id',
        'prompt_template',
        'variables',
        'additional_instructions',
        'generated_content',
        'meta_title',
        'meta_description',
        'tokens_used',
        'ai_model',
        'status',
        'error',
        'error_message',
        'published_at',
        'completed_at',
        'page_id',
        'tenant_id',
        'created_by',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'published_at' => 'datetime',
        'completed_at' => 'datetime',
        'variables' => 'array',
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
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
