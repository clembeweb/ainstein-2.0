<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTenantRequest;
use App\Http\Requests\Api\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Super admins can see all tenants, regular users only their own
        $query = $user->is_super_admin
            ? Tenant::query()
            : Tenant::where('id', $user->tenant_id);

        $tenants = $query
            ->withCount(['users', 'pages', 'contentGenerations'])
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $tenants->items(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ]
        ]);
    }

    /**
     * Store a newly created tenant
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        // Only super admins can create tenants
        if (!$request->user()->is_super_admin) {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can create tenants.'
            ], 403);
        }

        $tenant = Tenant::create($request->validated());
        $tenant->load(['users:id,name,email,tenant_id']);

        return response()->json([
            'message' => 'Tenant created successfully',
            'data' => $tenant
        ], 201);
    }

    /**
     * Display the specified tenant
     */
    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $user = $request->user();

        // Check authorization
        if (!$user->is_super_admin && $user->tenant_id !== $tenant->id) {
            return response()->json([
                'message' => 'Unauthorized. You can only view your own tenant.'
            ], 403);
        }

        $tenant->load([
            'users:id,name,email,role,is_active,tenant_id',
            'pages:id,url_path,keyword,status,tenant_id',
            'prompts:id,name,category,is_active,tenant_id',
        ]);
        $tenant->loadCount(['contentGenerations', 'cmsConnections']);

        return response()->json([
            'data' => $tenant
        ]);
    }

    /**
     * Update the specified tenant
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $user = $request->user();

        // Check authorization - super admins can update any tenant,
        // regular admins can only update their own tenant
        if (!$user->is_super_admin &&
            ($user->tenant_id !== $tenant->id || $user->role !== 'admin')) {
            return response()->json([
                'message' => 'Unauthorized. You can only update your own tenant.'
            ], 403);
        }

        $tenant->update($request->validated());
        $tenant->load(['users:id,name,email,tenant_id']);

        return response()->json([
            'message' => 'Tenant updated successfully',
            'data' => $tenant
        ]);
    }

    /**
     * Remove the specified tenant
     */
    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        // Only super admins can delete tenants
        if (!$request->user()->is_super_admin) {
            return response()->json([
                'message' => 'Unauthorized. Only super admins can delete tenants.'
            ], 403);
        }

        // Prevent deletion if tenant has active users
        if ($tenant->users()->where('is_active', true)->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete tenant with active users. Please deactivate all users first.'
            ], 422);
        }

        $tenant->delete();

        return response()->json([
            'message' => 'Tenant deleted successfully'
        ]);
    }
}
