<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlatformSetting extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'openai_api_key',
        'openai_model',
        'stripe_secret_key',
        'stripe_webhook',
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_pass',
        'google_client_id',
        'google_client_secret',
        'facebook_client_id',
        'facebook_client_secret',
        'platform_name',
        'platform_description',
        'maintenance_mode',
        'default_plan_id',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
        'maintenance_mode' => 'boolean',
        'openai_api_key' => 'encrypted',
        'stripe_secret_key' => 'encrypted',
        'google_client_secret' => 'encrypted',
        'facebook_client_secret' => 'encrypted',
        'smtp_pass' => 'encrypted',
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
}
