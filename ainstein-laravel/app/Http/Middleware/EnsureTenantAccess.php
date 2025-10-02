<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user has access to a tenant and that
     * any tenant-related resources belong to their tenant.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        Log::info('🔒 EnsureTenantAccess MIDDLEWARE START', [
            'url' => $request->url(),
            'route' => $request->route()?->getName(),
            'auth_check' => Auth::check()
        ]);

        // Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('❌ MIDDLEWARE: User not authenticated, redirecting to login');
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Authentication required',
                    'message' => 'You must be logged in to access this resource'
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();
        Log::info('✅ MIDDLEWARE: User authenticated', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id
        ]);

        // Check if user has a tenant assigned
        if (!$user->tenant_id) {
            Log::warning('User without tenant tried to access tenant resource', [
                'user_id' => $user->id,
                'email' => $user->email,
                'route' => $request->route()?->getName(),
                'url' => $request->url()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'No tenant access',
                    'message' => 'Your account is not associated with any tenant. Please contact support.'
                ], 403);
            }

            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Your account is not associated with any tenant. Please contact support.');
        }

        // Check if user's tenant is active
        $tenant = $user->tenant;
        if (!$tenant) {
            Log::error('User has tenant_id but tenant not found', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Tenant not found',
                    'message' => 'Your tenant account could not be found. Please contact support.'
                ], 404);
            }

            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Your tenant account could not be found. Please contact support.');
        }

        if ($tenant->status !== 'active') {
            Log::warning('User tried to access with inactive tenant', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'tenant_status' => $tenant->status
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Tenant inactive',
                    'message' => 'Your tenant account is currently inactive. Please contact support.',
                    'tenant_status' => $tenant->status
                ], 403);
            }

            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Your tenant account is currently inactive. Please contact support.');
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('Inactive user tried to access tenant resource', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'User account inactive',
                    'message' => 'Your account is currently inactive. Please contact support.'
                ], 403);
            }

            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Your account is currently inactive. Please contact support.');
        }

        // For routes with model binding (like /pages/{page}), ensure the resource belongs to the tenant
        $this->validateTenantResourceAccess($request, $user->tenant_id);

        // Add tenant information to the request for easy access in controllers
        $request->merge([
            'current_tenant' => $tenant,
            'current_tenant_id' => $tenant->id
        ]);

        return $next($request);
    }

    /**
     * Validate that any model-bound resources belong to the current tenant
     */
    protected function validateTenantResourceAccess(Request $request, string $tenantId): void
    {
        $route = $request->route();
        if (!$route) {
            return;
        }

        $parameters = $route->parameters();

        foreach ($parameters as $key => $model) {
            if (!is_object($model)) {
                continue;
            }

            // Check if model has tenant_id property
            if (property_exists($model, 'tenant_id') || $model->hasAttribute('tenant_id')) {
                if ($model->tenant_id !== $tenantId) {
                    Log::warning('Unauthorized tenant resource access attempt', [
                        'user_tenant_id' => $tenantId,
                        'resource_tenant_id' => $model->tenant_id,
                        'resource_type' => get_class($model),
                        'resource_id' => $model->getKey(),
                        'route' => $route->getName()
                    ]);

                    if ($request->expectsJson()) {
                        abort(403, 'Access denied: Resource does not belong to your tenant');
                    }

                    abort(403, 'Unauthorized access to this resource');
                }
            }
        }
    }

    /**
     * Get the guard to be used during authentication.
     */
    protected function getGuard(array $guards): ?string
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }
}
