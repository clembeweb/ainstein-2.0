<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TenantApiKeyController extends Controller
{
    /**
     * Display a listing of API keys for the authenticated tenant
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $query = ApiKey::where('tenant_id', $tenantId)
                ->with(['tenant:id,name'])
                ->orderBy('created_at', 'desc');

            // Filter by active status
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                        });
                } elseif ($request->status === 'inactive') {
                    $query->where(function ($q) {
                        $q->where('is_active', false)
                          ->orWhere('expires_at', '<=', now());
                    });
                } elseif ($request->status === 'expired') {
                    $query->where('expires_at', '<=', now());
                }
            }

            // Search by name
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $apiKeys = $query->paginate($request->get('per_page', 15));

            // Add status information to each API key
            $apiKeys->getCollection()->transform(function ($apiKey) {
                $apiKey->status = $this->getApiKeyStatus($apiKey);
                $apiKey->days_until_expiry = $apiKey->expires_at ? now()->diffInDays($apiKey->expires_at, false) : null;
                // Don't expose the actual key in the response
                unset($apiKey->key);
                return $apiKey;
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $apiKeys->items(),
                    'meta' => [
                        'current_page' => $apiKeys->currentPage(),
                        'last_page' => $apiKeys->lastPage(),
                        'per_page' => $apiKeys->perPage(),
                        'total' => $apiKeys->total(),
                    ],
                    'stats' => [
                        'total_keys' => ApiKey::where('tenant_id', $tenantId)->count(),
                        'active_keys' => ApiKey::where('tenant_id', $tenantId)
                            ->where('is_active', true)
                            ->where(function ($q) {
                                $q->whereNull('expires_at')
                                  ->orWhere('expires_at', '>', now());
                            })->count(),
                        'expired_keys' => ApiKey::where('tenant_id', $tenantId)
                            ->where('expires_at', '<=', now())->count(),
                    ]
                ]);
            }

            return view('tenant.api-keys.index', compact('apiKeys'));

        } catch (\Exception $e) {
            Log::error('Error fetching API keys', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch API keys'], 500);
            }

            return back()->with('error', 'Failed to load API keys. Please try again.');
        }
    }

    /**
     * Generate a new API key
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:read,write,delete,admin',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = Auth::user();
            $tenant = $user->tenant;
            $tenantId = $user->tenant_id;

            // Check if tenant has reached API key limit
            $currentKeysCount = ApiKey::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count();

            $maxKeys = $this->getMaxApiKeysForPlan($tenant->plan_type ?? 'free');

            if ($currentKeysCount >= $maxKeys) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => "You have reached the maximum number of API keys ({$maxKeys}) for your plan."
                    ], 403);
                }
                return back()->withErrors(['name' => "You have reached the maximum number of API keys ({$maxKeys}) for your plan."]);
            }

            // Check for duplicate names
            $existingKey = ApiKey::where('tenant_id', $tenantId)
                ->where('name', $request->name)
                ->where('is_active', true)
                ->first();

            if ($existingKey) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'An active API key with this name already exists.'
                    ], 409);
                }
                return back()->withErrors(['name' => 'An active API key with this name already exists.'])->withInput();
            }

            // Generate secure API key
            $plainTextKey = 'ak_' . Str::random(40);
            $hashedKey = Hash::make($plainTextKey);

            // Create the API key record
            $apiKey = ApiKey::create([
                'tenant_id' => $tenantId,
                'name' => $request->name,
                'key' => $hashedKey,
                'expires_at' => $request->expires_at,
                'is_active' => true,
                'permissions' => $request->permissions ?? ['read'],
                'created_by' => $user->id,
            ]);

            Log::info('API key generated successfully', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'expires_at' => $apiKey->expires_at
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'API key generated successfully',
                    'data' => [
                        'id' => $apiKey->id,
                        'name' => $apiKey->name,
                        'key' => $plainTextKey, // Return plain text only once
                        'expires_at' => $apiKey->expires_at,
                        'permissions' => $apiKey->permissions,
                        'created_at' => $apiKey->created_at,
                    ],
                    'warning' => 'Please save this key securely. It will not be shown again.'
                ], 201);
            }

            return response()->json([
                'success' => true,
                'message' => 'API key generated successfully',
                'api_key' => $plainTextKey,
                'key_id' => $apiKey->id,
                'expires_at' => $apiKey->expires_at,
                'warning' => 'Please save this key securely. It will not be shown again.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating API key', [
                'user_id' => Auth::id(),
                'name' => $request->name,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to generate API key'], 500);
            }

            return back()->with('error', 'Failed to generate API key. Please try again.');
        }
    }

    /**
     * Display the specified API key
     */
    public function show(ApiKey $apiKey)
    {
        $user = Auth::user();

        // Ensure the API key belongs to the user's tenant
        if ($apiKey->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this API key');
        }

        try {
            $apiKey->load(['tenant:id,name']);
            $apiKey->status = $this->getApiKeyStatus($apiKey);
            $apiKey->days_until_expiry = $apiKey->expires_at ? now()->diffInDays($apiKey->expires_at, false) : null;

            // Don't expose the actual key
            unset($apiKey->key);

            if (request()->expectsJson()) {
                return response()->json([
                    'data' => $apiKey
                ]);
            }

            return view('tenant.api-keys.show', compact('apiKey'));

        } catch (\Exception $e) {
            Log::error('Error fetching API key details', [
                'api_key_id' => $apiKey->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch API key details'], 500);
            }

            return back()->with('error', 'Failed to load API key details. Please try again.');
        }
    }

    /**
     * Update the specified API key
     */
    public function update(Request $request, ApiKey $apiKey)
    {
        $user = Auth::user();

        // Ensure the API key belongs to the user's tenant
        if ($apiKey->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this API key');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:read,write,delete,admin',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Check for duplicate names (excluding current key)
            $existingKey = ApiKey::where('tenant_id', $user->tenant_id)
                ->where('name', $request->name)
                ->where('is_active', true)
                ->where('id', '!=', $apiKey->id)
                ->first();

            if ($existingKey) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'An active API key with this name already exists.'
                    ], 409);
                }
                return back()->withErrors(['name' => 'An active API key with this name already exists.'])->withInput();
            }

            $apiKey->update([
                'name' => $request->name,
                'expires_at' => $request->expires_at,
                'permissions' => $request->permissions ?? $apiKey->permissions,
            ]);

            Log::info('API key updated successfully', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'API key updated successfully',
                    'data' => $apiKey->fresh()->load('tenant:id,name')
                ]);
            }

            return redirect()->route('tenant.api-keys.show', $apiKey->id)
                ->with('success', 'API key updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating API key', [
                'api_key_id' => $apiKey->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update API key'], 500);
            }

            return back()->with('error', 'Failed to update API key. Please try again.')->withInput();
        }
    }

    /**
     * Revoke (deactivate) the specified API key
     */
    public function revoke(ApiKey $apiKey)
    {
        $user = Auth::user();

        // Ensure the API key belongs to the user's tenant
        if ($apiKey->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this API key');
        }

        try {
            $apiKey->update([
                'is_active' => false,
                'revoked_at' => now(),
                'revoked_by' => $user->id,
            ]);

            Log::info('API key revoked successfully', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'API key revoked successfully'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'API key revoked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error revoking API key', [
                'api_key_id' => $apiKey->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to revoke API key'], 500);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to revoke API key. Please try again.'
            ], 500);
        }
    }

    /**
     * Activate the specified API key
     */
    public function activate(ApiKey $apiKey)
    {
        $user = Auth::user();

        // Ensure the API key belongs to the user's tenant
        if ($apiKey->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this API key');
        }

        try {
            // Check if the key has expired
            if ($apiKey->expires_at && $apiKey->expires_at <= now()) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'error' => 'Cannot activate an expired API key. Please create a new one.'
                    ], 400);
                }
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot activate an expired API key. Please create a new one.'
                ]);
            }

            $apiKey->update([
                'is_active' => true,
                'revoked_at' => null,
                'revoked_by' => null,
            ]);

            Log::info('API key activated successfully', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'API key activated successfully'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'API key activated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error activating API key', [
                'api_key_id' => $apiKey->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to activate API key'], 500);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to activate API key. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete the specified API key permanently
     */
    public function destroy(ApiKey $apiKey)
    {
        $user = Auth::user();

        // Ensure the API key belongs to the user's tenant
        if ($apiKey->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this API key');
        }

        try {
            $apiKeyInfo = [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'tenant_id' => $apiKey->tenant_id
            ];

            $apiKey->delete();

            Log::info('API key deleted permanently', [
                'api_key_info' => $apiKeyInfo,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'API key deleted successfully'
                ]);
            }

            return redirect()->route('tenant.api-keys.index')
                ->with('success', 'API key deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting API key', [
                'api_key_id' => $apiKey->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete API key'], 500);
            }

            return back()->with('error', 'Failed to delete API key. Please try again.');
        }
    }

    /**
     * Get API key usage statistics
     */
    public function usage(ApiKey $apiKey)
    {
        $user = Auth::user();

        // Ensure the API key belongs to the user's tenant
        if ($apiKey->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // This would typically be implemented with proper API usage tracking
            // For now, return basic statistics
            $usage = [
                'total_requests' => 0, // Would be tracked in database
                'requests_this_month' => 0,
                'requests_today' => 0,
                'last_used' => $apiKey->last_used,
                'created_at' => $apiKey->created_at,
                'is_active' => $apiKey->is_active,
                'expires_at' => $apiKey->expires_at,
                'status' => $this->getApiKeyStatus($apiKey),
            ];

            return response()->json([
                'data' => $usage
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching API key usage', [
                'api_key_id' => $apiKey->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to fetch API key usage'], 500);
        }
    }

    /**
     * Get the status of an API key
     */
    private function getApiKeyStatus(ApiKey $apiKey): string
    {
        if (!$apiKey->is_active) {
            return 'revoked';
        }

        if ($apiKey->expires_at && $apiKey->expires_at <= now()) {
            return 'expired';
        }

        if ($apiKey->expires_at && $apiKey->expires_at <= now()->addDays(7)) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Get maximum API keys allowed for a plan
     */
    private function getMaxApiKeysForPlan(string $planType): int
    {
        return match ($planType) {
            'free' => 2,
            'basic' => 5,
            'pro' => 10,
            'enterprise' => 25,
            default => 2,
        };
    }
}
