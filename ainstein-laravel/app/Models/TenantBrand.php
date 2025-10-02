<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBrand extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'brand_name',
        'brand_description',
        'logo_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'text_color',
        'font_family',
        'heading_font',
        'font_scale',
        'theme_mode',
        'border_radius',
        'custom_css',
        'contact_email',
        'contact_phone',
        'website_url',
        'social_links',
        'footer_text',
        'copyright_text',
        'privacy_policy_url',
        'terms_of_service_url',
        'is_active',
    ];

    protected $casts = [
        'custom_css' => 'array',
        'social_links' => 'array',
        'font_scale' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getColorsArray(): array
    {
        return [
            'primary' => $this->primary_color,
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
            'background' => $this->background_color,
            'text' => $this->text_color,
        ];
    }

    public function getSocialLinksArray(): array
    {
        return $this->social_links ?? [];
    }

    public function getCustomCssRules(): string
    {
        if (empty($this->custom_css)) {
            return '';
        }

        $css = '';
        foreach ($this->custom_css as $selector => $rules) {
            $css .= $selector . ' {';
            foreach ($rules as $property => $value) {
                $css .= $property . ': ' . $value . '; ';
            }
            $css .= '} ';
        }

        return $css;
    }

    public function generateCssVariables(): string
    {
        return "
            :root {
                --primary-color: {$this->primary_color};
                --secondary-color: {$this->secondary_color};
                --accent-color: {$this->accent_color};
                --background-color: {$this->background_color};
                --text-color: {$this->text_color};
                --font-family: '{$this->font_family}';
                --heading-font: '{$this->heading_font}';
                --font-scale: {$this->font_scale};
                --border-radius: {$this->border_radius};
            }
        ";
    }
}