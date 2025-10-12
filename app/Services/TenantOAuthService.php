<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantOAuthProvider;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TenantOAuthService
{
    /**
     * Configure Socialite dynamically for a specific tenant
     */
    public function configureSocialiteForTenant($provider, $tenantId = null)
    {
        // If no tenant ID provided, try to get from subdomain or domain
        if (!$tenantId) {
            $tenantId = $this->getTenantIdFromRequest();
        }

        // Get OAuth configuration for this tenant
        $oauthConfig = TenantOAuthProvider::where('tenant_id', $tenantId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();

        if (!$oauthConfig || !$oauthConfig->isConfigured()) {
            // Fallback to global configuration from platform_settings
            return $this->useGlobalConfiguration($provider);
        }

        // Configure Socialite dynamically for this tenant
        $this->setSocialiteConfig($provider, [
            'client_id' => $oauthConfig->client_id,
            'client_secret' => $oauthConfig->client_secret,
            'redirect' => $oauthConfig->getCallbackUrl(),
        ]);

        return true;
    }

    /**
     * Get tenant ID from request (subdomain or session)
     */
    protected function getTenantIdFromRequest()
    {
        // Try to get from session first
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }

        // Try to get from subdomain
        $host = request()->getHost();
        $subdomain = explode('.', $host)[0];

        if ($subdomain && $subdomain !== 'www') {
            $tenant = Tenant::where('subdomain', $subdomain)->first();
            if ($tenant) {
                return $tenant->id;
            }
        }

        // Try to get from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        return null;
    }

    /**
     * Use global OAuth configuration as fallback
     */
    protected function useGlobalConfiguration($provider)
    {
        $settings = \App\Models\PlatformSetting::first();

        if (!$settings) {
            return false;
        }

        $config = match($provider) {
            'google' => [
                'client_id' => $settings->google_console_client_id,
                'client_secret' => $settings->google_console_client_secret,
                'redirect' => url('/auth/google/callback'),
            ],
            'facebook' => [
                'client_id' => $settings->facebook_app_id,
                'client_secret' => $settings->facebook_app_secret,
                'redirect' => url('/auth/facebook/callback'),
            ],
            default => null,
        };

        if (!$config || empty($config['client_id']) || empty($config['client_secret'])) {
            return false;
        }

        $this->setSocialiteConfig($provider, $config);
        return true;
    }

    /**
     * Set Socialite configuration dynamically
     */
    protected function setSocialiteConfig($provider, array $config)
    {
        Config::set("services.{$provider}.client_id", $config['client_id']);
        Config::set("services.{$provider}.client_secret", $config['client_secret']);
        Config::set("services.{$provider}.redirect", $config['redirect']);
    }

    /**
     * Get available OAuth providers for a tenant
     */
    public function getAvailableProviders($tenantId)
    {
        $providers = [];

        // Check tenant-specific providers
        $tenantProviders = TenantOAuthProvider::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($tenantProviders as $provider) {
            if ($provider->isConfigured()) {
                $providers[] = $provider->provider;
            }
        }

        // If no tenant-specific providers, check global configuration
        if (empty($providers)) {
            $settings = \App\Models\PlatformSetting::first();

            if ($settings) {
                if (!empty($settings->google_console_client_id) && !empty($settings->google_console_client_secret)) {
                    $providers[] = 'google';
                }
                if (!empty($settings->facebook_app_id) && !empty($settings->facebook_app_secret)) {
                    $providers[] = 'facebook';
                }
            }
        }

        return $providers;
    }

    /**
     * Test OAuth configuration
     */
    public function testConfiguration(TenantOAuthProvider $provider)
    {
        try {
            // Configure Socialite for this provider
            $this->setSocialiteConfig($provider->provider, [
                'client_id' => $provider->client_id,
                'client_secret' => $provider->client_secret,
                'redirect' => $provider->getCallbackUrl(),
            ]);

            // Try to get the redirect URL (this validates the configuration)
            $redirectUrl = Socialite::driver($provider->provider)->redirect()->getTargetUrl();

            if ($redirectUrl) {
                $provider->updateTestStatus('success', 'Configuration is valid');
                return ['success' => true, 'message' => 'OAuth configuration is valid'];
            }
        } catch (\Exception $e) {
            $errorMessage = 'Configuration test failed: ' . $e->getMessage();
            $provider->updateTestStatus('failed', $errorMessage);

            Log::error('OAuth configuration test failed', [
                'provider' => $provider->provider,
                'tenant_id' => $provider->tenant_id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $errorMessage];
        }

        return ['success' => false, 'message' => 'Unknown error during configuration test'];
    }
}