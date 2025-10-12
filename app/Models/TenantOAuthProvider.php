<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TenantOAuthProvider extends Model
{
    use HasFactory;

    protected $table = 'tenant_oauth_providers';

    protected $fillable = [
        'tenant_id',
        'provider',
        'client_id',
        'client_secret',
        'redirect_url',
        'is_active',
        'scopes',
        'settings',
        'last_tested_at',
        'test_status',
        'test_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scopes' => 'array',
        'settings' => 'array',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Encrypt client_secret before saving
     */
    public function setClientSecretAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['client_secret'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt client_secret when retrieving
     */
    public function getClientSecretAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, return null
                return null;
            }
        }
        return null;
    }

    /**
     * Encrypt client_id before saving (optional extra security)
     */
    public function setClientIdAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['client_id'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt client_id when retrieving
     */
    public function getClientIdAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, return null
                return null;
            }
        }
        return null;
    }

    /**
     * Get the tenant that owns this OAuth provider configuration
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the callback URL for this provider
     */
    public function getCallbackUrl()
    {
        if (!empty($this->redirect_url)) {
            return $this->redirect_url;
        }

        // Default callback URL
        return url("/auth/{$this->provider}/callback");
    }

    /**
     * Check if this provider is properly configured
     */
    public function isConfigured()
    {
        return !empty($this->client_id) && !empty($this->client_secret);
    }

    /**
     * Check if this provider is configured and active
     */
    public function isAvailable()
    {
        return $this->isConfigured() && $this->is_active;
    }

    /**
     * Update test status
     */
    public function updateTestStatus($status, $message = null)
    {
        $this->update([
            'test_status' => $status,
            'test_message' => $message,
            'last_tested_at' => now(),
        ]);
    }

    /**
     * Get display name for the provider
     */
    public function getDisplayName()
    {
        $names = [
            'google' => 'Google',
            'facebook' => 'Facebook',
        ];

        return $names[$this->provider] ?? ucfirst($this->provider);
    }

    /**
     * Get icon class for the provider
     */
    public function getIconClass()
    {
        $icons = [
            'google' => 'fab fa-google text-red-500',
            'facebook' => 'fab fa-facebook text-blue-600',
        ];

        return $icons[$this->provider] ?? 'fas fa-sign-in-alt';
    }

    /**
     * Get configuration instructions URL
     */
    public function getSetupUrl()
    {
        $urls = [
            'google' => 'https://console.cloud.google.com/apis/credentials',
            'facebook' => 'https://developers.facebook.com/apps',
        ];

        return $urls[$this->provider] ?? '#';
    }
}