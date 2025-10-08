<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePageRequest;
use App\Http\Requests\Api\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display a listing of pages for current tenant
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->is_super_admin && $request->has('tenant_id')
            ? $request->get('tenant_id')
            : $user->tenant_id;

        $query = Page::where('tenant_id', $tenantId)
            ->with(['tenant:id,name', 'generations:id,page_id,status,created_at'])
            ->withCount('generations');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->get('category'));
        }

        if ($request->has('language')) {
            $query->where('language', $request->get('language'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('url_path', 'LIKE', "%{$search}%")
                  ->orWhere('keyword', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $pages = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $pages->items(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ]
        ]);
    }

    /**
     * Store a newly created page
     */
    public function store(StorePageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        $page = Page::create($data);
        $page->load(['tenant:id,name']);

        return response()->json([
            'message' => 'Page created successfully',
            'data' => $page
        ], 201);
    }

    /**
     * Display the specified page
     */
    public function show(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();

        // Check tenant access
        if (!$user->is_super_admin && $user->tenant_id !== $page->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only view pages from your tenant.'
            ], 403);
        }

        $page->load([
            'tenant:id,name',
            'generations' => function ($query) {
                $query->select('id', 'page_id', 'prompt_type', 'status', 'tokens_used', 'ai_model', 'created_at')
                      ->latest();
            }
        ]);

        return response()->json([
            'data' => $page
        ]);
    }

    /**
     * Update the specified page
     */
    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $user = $request->user();

        // Check tenant access
        if (!$user->is_super_admin && $user->tenant_id !== $page->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only update pages from your tenant.'
            ], 403);
        }

        $page->update($request->validated());
        $page->load(['tenant:id,name']);

        return response()->json([
            'message' => 'Page updated successfully',
            'data' => $page
        ]);
    }

    /**
     * Remove the specified page
     */
    public function destroy(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();

        // Check tenant access
        if (!$user->is_super_admin && $user->tenant_id !== $page->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only delete pages from your tenant.'
            ], 403);
        }

        // Check if page has content generations
        if ($page->generations()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete page with existing content generations. Please delete the generations first.'
            ], 422);
        }

        $page->delete();

        return response()->json([
            'message' => 'Page deleted successfully'
        ]);
    }
}
