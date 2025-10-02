<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TenantPageController extends Controller
{
    /**
     * Display a listing of pages for the authenticated tenant
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $query = Page::where('tenant_id', $tenantId)
                ->with(['tenant:id,name', 'generations']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('url_path', 'like', "%{$search}%")
                      ->orWhere('keyword', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Category filter
            if ($request->filled('category')) {
                $query->where('category', $request->get('category'));
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Language filter
            if ($request->filled('language')) {
                $query->where('language', $request->get('language'));
            }

            // CMS type filter
            if ($request->filled('cms_type')) {
                $query->where('cms_type', $request->get('cms_type'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $pages = $query->paginate($request->get('per_page', 15));

            // Get filter options
            $categories = Page::where('tenant_id', $tenantId)
                ->distinct()
                ->pluck('category')
                ->filter()
                ->sort()
                ->values();

            $statuses = ['active', 'inactive', 'pending', 'archived'];
            $languages = ['en', 'es', 'fr', 'de', 'it', 'pt'];
            $cmsTypes = ['wordpress', 'drupal', 'joomla', 'custom', 'static'];

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $pages->items(),
                    'meta' => [
                        'current_page' => $pages->currentPage(),
                        'last_page' => $pages->lastPage(),
                        'per_page' => $pages->perPage(),
                        'total' => $pages->total(),
                        'from' => $pages->firstItem(),
                        'to' => $pages->lastItem()
                    ],
                    'filters' => [
                        'categories' => $categories,
                        'statuses' => $statuses,
                        'languages' => $languages,
                        'cms_types' => $cmsTypes
                    ]
                ]);
            }

            return view('tenant.pages.index', compact('pages', 'categories', 'statuses', 'languages', 'cmsTypes'));

        } catch (\Exception $e) {
            Log::error('Error fetching pages', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch pages'], 500);
            }

            return back()->with('error', 'Failed to load pages. Please try again.');
        }
    }

    /**
     * Show the form for creating a new page
     */
    public function create()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $categories = Page::where('tenant_id', $tenant->id)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        $statuses = ['active', 'inactive', 'pending', 'archived'];
        $languages = ['en', 'es', 'fr', 'de', 'it', 'pt'];
        $cmsTypes = ['wordpress', 'drupal', 'joomla', 'custom', 'static'];
        $priorities = [1 => 'Low', 2 => 'Normal', 3 => 'High', 4 => 'Critical'];

        return view('tenant.pages.create', compact('categories', 'statuses', 'languages', 'cmsTypes', 'priorities', 'tenant'));
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url_path' => 'required|string|max:500',
            'keyword' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'language' => 'required|string|size:2|in:en,es,fr,de,it,pt',
            'cms_type' => 'nullable|string|max:50|in:wordpress,drupal,joomla,custom,static',
            'cms_page_id' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive,pending,archived',
            'priority' => 'nullable|integer|min:1|max:4',
            'metadata' => 'nullable|array',
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
            $tenantId = $user->tenant_id;

            // Check if URL path already exists for this tenant
            $existingPage = Page::where('tenant_id', $tenantId)
                ->where('url_path', $request->url_path)
                ->first();

            if ($existingPage) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'A page with this URL path already exists.'
                    ], 409);
                }
                return back()->withErrors(['url_path' => 'A page with this URL path already exists.'])->withInput();
            }

            $page = Page::create([
                'tenant_id' => $tenantId,
                'url_path' => $request->url_path,
                'keyword' => $request->keyword,
                'category' => $request->category,
                'language' => $request->language,
                'cms_type' => $request->cms_type,
                'cms_page_id' => $request->cms_page_id,
                'status' => $request->status,
                'priority' => $request->priority ?? 2,
                'metadata' => $request->metadata ?? [],
            ]);

            Log::info('Page created successfully', [
                'page_id' => $page->id,
                'tenant_id' => $tenantId,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Page created successfully',
                    'data' => $page->load('tenant')
                ], 201);
            }

            return redirect()->route('tenant.pages.show', $page->id)
                ->with('success', 'Page created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating page', [
                'user_id' => Auth::id(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create page'], 500);
            }

            return back()->with('error', 'Failed to create page. Please try again.')->withInput();
        }
    }

    /**
     * Display the specified page
     */
    public function show(Page $page)
    {
        $user = Auth::user();

        // Ensure the page belongs to the user's tenant
        if ($page->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this page');
        }

        try {
            $page->load(['tenant', 'generations' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }]);

            // Get page statistics
            $stats = [
                'total_generations' => $page->generations()->count(),
                'completed_generations' => $page->generations()->where('status', 'completed')->count(),
                'failed_generations' => $page->generations()->where('status', 'failed')->count(),
                'pending_generations' => $page->generations()->where('status', 'pending')->count(),
                'last_generation' => $page->generations()->latest()->first(),
            ];

            if (request()->expectsJson()) {
                return response()->json([
                    'data' => $page,
                    'stats' => $stats
                ]);
            }

            return view('tenant.pages.show', compact('page', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error fetching page details', [
                'page_id' => $page->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch page details'], 500);
            }

            return back()->with('error', 'Failed to load page details. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified page
     */
    public function edit(Page $page)
    {
        $user = Auth::user();

        // Ensure the page belongs to the user's tenant
        if ($page->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized access to this page');
        }

        $tenant = $user->tenant;
        $categories = Page::where('tenant_id', $tenant->id)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        $statuses = ['active', 'inactive', 'pending', 'archived'];
        $languages = ['en', 'es', 'fr', 'de', 'it', 'pt'];
        $cmsTypes = ['wordpress', 'drupal', 'joomla', 'custom', 'static'];
        $priorities = [1 => 'Low', 2 => 'Normal', 3 => 'High', 4 => 'Critical'];

        return view('tenant.pages.edit', compact('page', 'categories', 'statuses', 'languages', 'cmsTypes', 'priorities', 'tenant'));
    }

    /**
     * Update the specified page
     */
    public function update(Request $request, Page $page)
    {
        $user = Auth::user();

        // Ensure the page belongs to the user's tenant
        if ($page->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this page');
        }

        $validator = Validator::make($request->all(), [
            'url_path' => 'required|string|max:500',
            'keyword' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'language' => 'required|string|size:2|in:en,es,fr,de,it,pt',
            'cms_type' => 'nullable|string|max:50|in:wordpress,drupal,joomla,custom,static',
            'cms_page_id' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive,pending,archived',
            'priority' => 'nullable|integer|min:1|max:4',
            'metadata' => 'nullable|array',
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
            // Check if URL path already exists for this tenant (excluding current page)
            $existingPage = Page::where('tenant_id', $user->tenant_id)
                ->where('url_path', $request->url_path)
                ->where('id', '!=', $page->id)
                ->first();

            if ($existingPage) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'A page with this URL path already exists.'
                    ], 409);
                }
                return back()->withErrors(['url_path' => 'A page with this URL path already exists.'])->withInput();
            }

            $page->update([
                'url_path' => $request->url_path,
                'keyword' => $request->keyword,
                'category' => $request->category,
                'language' => $request->language,
                'cms_type' => $request->cms_type,
                'cms_page_id' => $request->cms_page_id,
                'status' => $request->status,
                'priority' => $request->priority ?? $page->priority,
                'metadata' => $request->metadata ?? $page->metadata,
            ]);

            Log::info('Page updated successfully', [
                'page_id' => $page->id,
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Page updated successfully',
                    'data' => $page->fresh()->load('tenant')
                ]);
            }

            return redirect()->route('tenant.pages.show', $page->id)
                ->with('success', 'Page updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating page', [
                'page_id' => $page->id,
                'user_id' => Auth::id(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update page'], 500);
            }

            return back()->with('error', 'Failed to update page. Please try again.')->withInput();
        }
    }

    /**
     * Remove the specified page from storage
     */
    public function destroy(Page $page)
    {
        $user = Auth::user();

        // Ensure the page belongs to the user's tenant
        if ($page->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this page');
        }

        try {
            DB::beginTransaction();

            // Store page info for logging
            $pageInfo = [
                'id' => $page->id,
                'url_path' => $page->url_path,
                'tenant_id' => $page->tenant_id
            ];

            // Delete related content generations
            $page->generations()->delete();

            // Delete the page
            $page->delete();

            DB::commit();

            Log::info('Page deleted successfully', [
                'page_info' => $pageInfo,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Page deleted successfully'
                ]);
            }

            return redirect()->route('tenant.pages.index')
                ->with('success', 'Page deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting page', [
                'page_id' => $page->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete page'], 500);
            }

            return back()->with('error', 'Failed to delete page. Please try again.');
        }
    }

    /**
     * Bulk update pages status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_ids' => 'required|array|min:1',
            'page_ids.*' => 'exists:pages,id',
            'status' => 'required|string|in:active,inactive,pending,archived',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator);
        }

        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Update only pages that belong to the user's tenant
            $updated = Page::where('tenant_id', $tenantId)
                ->whereIn('id', $request->page_ids)
                ->update(['status' => $request->status]);

            Log::info('Bulk status update completed', [
                'updated_count' => $updated,
                'status' => $request->status,
                'user_id' => $user->id,
                'tenant_id' => $tenantId
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Successfully updated {$updated} pages",
                    'updated_count' => $updated
                ]);
            }

            return back()->with('success', "Successfully updated {$updated} pages.");

        } catch (\Exception $e) {
            Log::error('Error in bulk status update', [
                'page_ids' => $request->page_ids,
                'status' => $request->status,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update pages'], 500);
            }

            return back()->with('error', 'Failed to update pages. Please try again.');
        }
    }

    /**
     * Bulk delete pages
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_ids' => 'required|array|min:1',
            'page_ids.*' => 'exists:pages,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator);
        }

        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            DB::beginTransaction();

            // Get pages that belong to the user's tenant
            $pages = Page::where('tenant_id', $tenantId)
                ->whereIn('id', $request->page_ids)
                ->get();

            $deletedCount = 0;
            foreach ($pages as $page) {
                // Delete related content generations
                $page->generations()->delete();

                // Delete the page
                $page->delete();
                $deletedCount++;
            }

            DB::commit();

            Log::info('Bulk delete completed', [
                'deleted_count' => $deletedCount,
                'user_id' => $user->id,
                'tenant_id' => $tenantId
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Successfully deleted {$deletedCount} pages",
                    'deleted_count' => $deletedCount
                ]);
            }

            return back()->with('success', "Successfully deleted {$deletedCount} pages.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in bulk delete', [
                'page_ids' => $request->page_ids,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to delete pages'], 500);
            }

            return back()->with('error', 'Failed to delete pages. Please try again.');
        }
    }
}
