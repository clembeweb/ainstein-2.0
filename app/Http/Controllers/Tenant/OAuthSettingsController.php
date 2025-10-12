<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantOAuthProvider;
use App\Services\TenantOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OAuthSettingsController extends Controller
{
    protected $oauthService;

    public function __construct(TenantOAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * Display OAuth settings for the tenant
     */
    public function index()
    {
        $tenant = auth()->user()->tenant;

        // Get or create OAuth provider configurations
        $googleProvider = TenantOAuthProvider::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'provider' => 'google',
            ],
            [
                'is_active' => false,
                'redirect_url' => url('/auth/google/callback'),
                'scopes' => ['email', 'profile'],
            ]
        );

        $facebookProvider = TenantOAuthProvider::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'provider' => 'facebook',
            ],
            [
                'is_active' => false,
                'redirect_url' => url('/auth/facebook/callback'),
                'scopes' => ['email', 'public_profile'],
            ]
        );

        return view('tenant.settings.oauth', compact('googleProvider', 'facebookProvider'));
    }

    /**
     * Update Google OAuth settings
     */
    public function updateGoogle(Request $request)
    {
        $request->validate([
            'client_id' => 'nullable|string|max:500',
            'client_secret' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $tenant = auth()->user()->tenant;

        $provider = TenantOAuthProvider::where('tenant_id', $tenant->id)
            ->where('provider', 'google')
            ->firstOrFail();

        // Update client ID if provided
        if ($request->filled('client_id')) {
            $provider->client_id = $request->client_id;
        }

        // Update client secret if provided
        if ($request->filled('client_secret')) {
            $provider->client_secret = $request->client_secret;
        }

        // Update active status
        $provider->is_active = $request->boolean('is_active');

        // Reset test status when configuration changes
        if ($request->filled('client_id') || $request->filled('client_secret')) {
            $provider->test_status = 'not_tested';
            $provider->test_message = null;
            $provider->last_tested_at = null;
        }

        $provider->save();

        Log::info('Google OAuth settings updated', [
            'tenant_id' => $tenant->id,
            'is_active' => $provider->is_active,
        ]);

        return redirect()->route('tenant.settings.oauth')
            ->with('success', 'Google OAuth settings updated successfully');
    }

    /**
     * Update Facebook OAuth settings
     */
    public function updateFacebook(Request $request)
    {
        $request->validate([
            'client_id' => 'nullable|string|max:500',
            'client_secret' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $tenant = auth()->user()->tenant;

        $provider = TenantOAuthProvider::where('tenant_id', $tenant->id)
            ->where('provider', 'facebook')
            ->firstOrFail();

        // Update client ID if provided
        if ($request->filled('client_id')) {
            $provider->client_id = $request->client_id;
        }

        // Update client secret if provided
        if ($request->filled('client_secret')) {
            $provider->client_secret = $request->client_secret;
        }

        // Update active status
        $provider->is_active = $request->boolean('is_active');

        // Reset test status when configuration changes
        if ($request->filled('client_id') || $request->filled('client_secret')) {
            $provider->test_status = 'not_tested';
            $provider->test_message = null;
            $provider->last_tested_at = null;
        }

        $provider->save();

        Log::info('Facebook OAuth settings updated', [
            'tenant_id' => $tenant->id,
            'is_active' => $provider->is_active,
        ]);

        return redirect()->route('tenant.settings.oauth')
            ->with('success', 'Facebook OAuth settings updated successfully');
    }

    /**
     * Test OAuth provider configuration
     */
    public function test(Request $request, $provider)
    {
        $tenant = auth()->user()->tenant;

        $oauthProvider = TenantOAuthProvider::where('tenant_id', $tenant->id)
            ->where('provider', $provider)
            ->firstOrFail();

        if (!$oauthProvider->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Provider is not configured. Please provide Client ID and Client Secret.',
            ]);
        }

        $result = $this->oauthService->testConfiguration($oauthProvider);

        return response()->json($result);
    }

    /**
     * Toggle OAuth provider status
     */
    public function toggle(Request $request, $provider)
    {
        $tenant = auth()->user()->tenant;

        $oauthProvider = TenantOAuthProvider::where('tenant_id', $tenant->id)
            ->where('provider', $provider)
            ->firstOrFail();

        if (!$oauthProvider->isConfigured()) {
            return redirect()->route('tenant.settings.oauth')
                ->with('error', 'Cannot enable provider without configuration');
        }

        $oauthProvider->is_active = !$oauthProvider->is_active;
        $oauthProvider->save();

        $status = $oauthProvider->is_active ? 'enabled' : 'disabled';

        return redirect()->route('tenant.settings.oauth')
            ->with('success', ucfirst($provider) . ' OAuth ' . $status . ' successfully');
    }

    /**
     * Clear OAuth provider configuration
     */
    public function clear(Request $request, $provider)
    {
        $tenant = auth()->user()->tenant;

        $oauthProvider = TenantOAuthProvider::where('tenant_id', $tenant->id)
            ->where('provider', $provider)
            ->firstOrFail();

        $oauthProvider->update([
            'client_id' => null,
            'client_secret' => null,
            'is_active' => false,
            'test_status' => null,
            'test_message' => null,
            'last_tested_at' => null,
        ]);

        return redirect()->route('tenant.settings.oauth')
            ->with('success', ucfirst($provider) . ' OAuth configuration cleared');
    }
}