<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Prompt;
use App\Models\ContentGeneration;
use App\Jobs\ProcessContentGeneration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TenantContentController extends Controller
{
    /**
     * Show the content generation form
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Get user's pages
        $pages = Page::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('url_path')
            ->get();

        // Get available prompts (user's prompts + system prompts)
        $prompts = Prompt::query()
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhere('is_system', true);
            })
            ->where('is_active', true)
            ->orderBy('is_system', 'asc')
            ->orderBy('name')
            ->get();

        // Pre-select page if provided
        $selectedPageId = $request->get('page_id');
        $selectedPage = null;
        if ($selectedPageId) {
            $selectedPage = Page::where('tenant_id', $tenantId)
                ->where('id', $selectedPageId)
                ->first();
        }

        return view('tenant.content.create', compact('pages', 'prompts', 'selectedPage'));
    }

    /**
     * Generate content
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:pages,id',
            'prompt_id' => 'required|exists:prompts,id',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:1000',
            'additional_instructions' => 'nullable|string|max:2000',
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

            // Verify page belongs to tenant
            $page = Page::where('tenant_id', $tenantId)
                ->where('id', $request->page_id)
                ->first();

            if (!$page) {
                return back()->with('error', 'Page not found or access denied.');
            }

            // Verify prompt access
            $prompt = Prompt::query()
                ->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhere('is_system', true);
                })
                ->where('id', $request->prompt_id)
                ->where('is_active', true)
                ->first();

            if (!$prompt) {
                return back()->with('error', 'Prompt not found or access denied.');
            }

            // Determine prompt type based on prompt category or name
            $promptType = $this->determinePromptType($prompt);

            // Create content generation record
            $generation = ContentGeneration::create([
                'tenant_id' => $tenantId,
                'page_id' => $page->id,
                'prompt_id' => $prompt->id,
                'prompt_type' => $promptType,
                'prompt_template' => $prompt->template,
                'variables' => $request->variables ?? [],
                'additional_instructions' => $request->additional_instructions,
                'status' => 'pending',
                'created_by' => $user->id,
                'ai_model' => 'gpt-3.5-turbo', // Set default model
            ]);

            Log::info('Content generation created', [
                'generation_id' => $generation->id,
                'page_id' => $page->id,
                'prompt_id' => $prompt->id,
                'tenant_id' => $tenantId,
                'user_id' => $user->id
            ]);

            // Dispatch job for background processing
            ProcessContentGeneration::dispatch($generation);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Content generation started successfully',
                    'data' => $generation->load(['page', 'prompt'])
                ], 201);
            }

            return redirect()->route('tenant.content.show', $generation->id)
                ->with('success', 'Content generation started successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating content generation', [
                'user_id' => Auth::id(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to start content generation'], 500);
            }

            return back()->with('error', 'Failed to start content generation. Please try again.')->withInput();
        }
    }

    /**
     * Display the specified content generation
     */
    public function show(ContentGeneration $generation)
    {
        $user = Auth::user();

        // Ensure the generation belongs to the user's tenant
        if ($generation->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this content generation');
        }

        try {
            $generation->load(['page', 'prompt', 'tenant']);

            if (request()->expectsJson()) {
                return response()->json([
                    'data' => $generation
                ]);
            }

            return view('tenant.content.show', compact('generation'));

        } catch (\Exception $e) {
            Log::error('Error fetching content generation details', [
                'generation_id' => $generation->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch content generation details'], 500);
            }

            return back()->with('error', 'Failed to load content generation details. Please try again.');
        }
    }

    /**
     * Display a listing of content generations
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $query = ContentGeneration::where('tenant_id', $tenantId)
                ->with(['page', 'prompt']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by page
            if ($request->filled('page_id')) {
                $query->where('page_id', $request->get('page_id'));
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('page', function ($q) use ($search) {
                    $q->where('url_path', 'like', "%{$search}%")
                      ->orWhere('keyword', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $generations = $query->paginate($request->get('per_page', 15));

            // Get filter options
            $pages = Page::where('tenant_id', $tenantId)->get();
            $statuses = ['pending', 'processing', 'completed', 'failed'];

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $generations->items(),
                    'meta' => [
                        'current_page' => $generations->currentPage(),
                        'last_page' => $generations->lastPage(),
                        'per_page' => $generations->perPage(),
                        'total' => $generations->total(),
                        'from' => $generations->firstItem(),
                        'to' => $generations->lastItem()
                    ],
                    'filters' => [
                        'pages' => $pages,
                        'statuses' => $statuses
                    ]
                ]);
            }

            return view('tenant.content.index', compact('generations', 'pages', 'statuses'));

        } catch (\Exception $e) {
            Log::error('Error fetching content generations', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch content generations'], 500);
            }

            return back()->with('error', 'Failed to load content generations. Please try again.');
        }
    }

    /**
     * Get prompt details for AJAX requests
     */
    public function getPromptDetails(Prompt $prompt)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Verify prompt access
        if (!$prompt->is_system && $prompt->tenant_id !== $tenantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $prompt->id,
                'name' => $prompt->name,
                'description' => $prompt->description,
                'template' => $prompt->template,
                'variables' => $prompt->variables ?? [],
                'category' => $prompt->category,
                'is_system' => $prompt->is_system,
            ]
        ]);
    }

    /**
     * Determine prompt type based on prompt category or name
     */
    private function determinePromptType(Prompt $prompt): string
    {
        // Check category first
        if ($prompt->category) {
            $category = strtolower($prompt->category);
            if (str_contains($category, 'meta_title') || str_contains($category, 'title')) {
                return 'meta_title';
            }
            if (str_contains($category, 'meta_description') || str_contains($category, 'description')) {
                return 'meta_description';
            }
            if (str_contains($category, 'outline')) {
                return 'outline';
            }
        }

        // Check prompt name if category doesn't help
        $name = strtolower($prompt->name);
        if (str_contains($name, 'title')) {
            return 'meta_title';
        }
        if (str_contains($name, 'description')) {
            return 'meta_description';
        }
        if (str_contains($name, 'outline')) {
            return 'outline';
        }

        // Default to content
        return 'content';
    }
}