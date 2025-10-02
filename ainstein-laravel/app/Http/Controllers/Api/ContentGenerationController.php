<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreContentGenerationRequest;
use App\Http\Requests\Api\UpdateContentGenerationRequest;
use App\Jobs\ProcessContentGeneration;
use App\Models\ContentGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentGenerationController extends Controller
{
    /**
     * Display a listing of content generations for current tenant
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->is_super_admin && $request->has('tenant_id')
            ? $request->get('tenant_id')
            : $user->tenant_id;

        $query = ContentGeneration::where('tenant_id', $tenantId)
            ->with(['tenant:id,name', 'page:id,url_path,keyword,tenant_id']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('prompt_type')) {
            $query->where('prompt_type', $request->get('prompt_type'));
        }

        if ($request->has('ai_model')) {
            $query->where('ai_model', $request->get('ai_model'));
        }

        if ($request->has('page_id')) {
            $query->where('page_id', $request->get('page_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $generations = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $generations->items(),
            'meta' => [
                'current_page' => $generations->currentPage(),
                'last_page' => $generations->lastPage(),
                'per_page' => $generations->perPage(),
                'total' => $generations->total(),
                'total_tokens_used' => $generations->sum('tokens_used'),
            ]
        ]);
    }

    /**
     * Store a newly created content generation
     */
    public function store(StoreContentGenerationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'pending'; // Always start as pending

        $generation = ContentGeneration::create($data);
        $generation->load(['tenant:id,name', 'page:id,url_path,keyword,tenant_id']);

        // Dispatch job to process content generation
        ProcessContentGeneration::dispatch($generation);

        return response()->json([
            'message' => 'Content generation created successfully',
            'data' => $generation
        ], 201);
    }

    /**
     * Display the specified content generation
     */
    public function show(Request $request, ContentGeneration $generation): JsonResponse
    {
        $user = $request->user();

        // Check tenant access
        if (!$user->is_super_admin && $user->tenant_id !== $generation->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only view content generations from your tenant.'
            ], 403);
        }

        $generation->load([
            'tenant:id,name',
            'page:id,url_path,keyword,category,language,tenant_id'
        ]);

        return response()->json([
            'data' => $generation
        ]);
    }

    /**
     * Update the specified content generation
     */
    public function update(UpdateContentGenerationRequest $request, ContentGeneration $generation): JsonResponse
    {
        $user = $request->user();

        // Check tenant access
        if (!$user->is_super_admin && $user->tenant_id !== $generation->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only update content generations from your tenant.'
            ], 403);
        }

        // Prevent updating certain fields after generation is completed
        $data = $request->validated();
        if (in_array($generation->status, ['completed', 'published']) && !$user->is_super_admin) {
            // Only allow updating published_at for completed generations
            $data = array_intersect_key($data, array_flip(['published_at']));
        }

        $generation->update($data);
        $generation->load(['tenant:id,name', 'page:id,url_path,keyword,tenant_id']);

        return response()->json([
            'message' => 'Content generation updated successfully',
            'data' => $generation
        ]);
    }

    /**
     * Remove the specified content generation
     */
    public function destroy(Request $request, ContentGeneration $generation): JsonResponse
    {
        $user = $request->user();

        // Check tenant access
        if (!$user->is_super_admin && $user->tenant_id !== $generation->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only delete content generations from your tenant.'
            ], 403);
        }

        // Prevent deletion if generation is published
        if ($generation->status === 'published') {
            return response()->json([
                'message' => 'Cannot delete published content generation. Please unpublish it first.'
            ], 422);
        }

        $generation->delete();

        return response()->json([
            'message' => 'Content generation deleted successfully'
        ]);
    }
}
