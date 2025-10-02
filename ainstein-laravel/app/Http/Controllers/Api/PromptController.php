<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePromptRequest;
use App\Http\Requests\Api\UpdatePromptRequest;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    /**
     * Display a listing of prompts for current tenant
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->is_super_admin && $request->has('tenant_id')
            ? $request->get('tenant_id')
            : $user->tenant_id;

        $query = Prompt::where('tenant_id', $tenantId)
            ->with(['tenant:id,name']);

        // Include system prompts if requested
        if ($request->boolean('include_system')) {
            $query->orWhere('is_system', true);
        }

        // Apply filters
        if ($request->has('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('alias', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $prompts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $prompts->items(),
            'meta' => [
                'current_page' => $prompts->currentPage(),
                'last_page' => $prompts->lastPage(),
                'per_page' => $prompts->perPage(),
                'total' => $prompts->total(),
            ]
        ]);
    }

    /**
     * Store a newly created prompt
     */
    public function store(StorePromptRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['is_system'] = false; // User-created prompts are never system prompts

        $prompt = Prompt::create($data);
        $prompt->load(['tenant:id,name']);

        return response()->json([
            'message' => 'Prompt created successfully',
            'data' => $prompt
        ], 201);
    }

    /**
     * Display the specified prompt
     */
    public function show(Request $request, Prompt $prompt): JsonResponse
    {
        $user = $request->user();

        // Check access - system prompts are accessible to everyone,
        // tenant prompts only to tenant users or super admins
        if (!$prompt->is_system &&
            !$user->is_super_admin &&
            $user->tenant_id !== $prompt->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only view prompts from your tenant or system prompts.'
            ], 403);
        }

        $prompt->load(['tenant:id,name']);

        return response()->json([
            'data' => $prompt
        ]);
    }

    /**
     * Update the specified prompt
     */
    public function update(UpdatePromptRequest $request, Prompt $prompt): JsonResponse
    {
        $user = $request->user();

        // Check access - cannot modify system prompts unless super admin
        if ($prompt->is_system && !$user->is_super_admin) {
            return response()->json([
                'message' => 'Unauthorized. System prompts can only be modified by super admins.'
            ], 403);
        }

        // Check tenant access for non-system prompts
        if (!$prompt->is_system &&
            !$user->is_super_admin &&
            $user->tenant_id !== $prompt->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only update prompts from your tenant.'
            ], 403);
        }

        $data = $request->validated();
        // Prevent changing is_system flag via API for security
        unset($data['is_system']);

        $prompt->update($data);
        $prompt->load(['tenant:id,name']);

        return response()->json([
            'message' => 'Prompt updated successfully',
            'data' => $prompt
        ]);
    }

    /**
     * Remove the specified prompt
     */
    public function destroy(Request $request, Prompt $prompt): JsonResponse
    {
        $user = $request->user();

        // Cannot delete system prompts
        if ($prompt->is_system) {
            return response()->json([
                'message' => 'System prompts cannot be deleted.'
            ], 422);
        }

        // Check tenant access
        if (!$user->is_super_admin && $user->tenant_id !== $prompt->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only delete prompts from your tenant.'
            ], 403);
        }

        $prompt->delete();

        return response()->json([
            'message' => 'Prompt deleted successfully'
        ]);
    }
}
