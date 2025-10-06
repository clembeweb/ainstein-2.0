<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Page; // Keep for backward compatibility
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

        // Get user's contents (unified content table)
        $pages = \App\Models\Content::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('url')
            ->get();

        // Get available prompts (user's prompts + system prompts)
        $prompts = Prompt::query()
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhere('is_system', true);
            })
            ->where('is_active', true)
            ->orderBy('is_system', 'asc')
            ->orderBy('alias')
            ->get();

        // Pre-select page if provided
        $selectedPageId = $request->get('page_id');
        $selectedPage = null;
        if ($selectedPageId) {
            $selectedPage = \App\Models\Content::where('tenant_id', $tenantId)
                ->where('id', $selectedPageId)
                ->first();
        }

        return view('tenant.content.create', compact('pages', 'prompts', 'selectedPage'));
    }

    /**
     * Generate content (with sync/async mode support)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:contents,id',
            'prompt_id' => 'required|exists:prompts,id',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:1000',
            'additional_instructions' => 'nullable|string|max:2000',
            'execution_mode' => 'nullable|in:sync,async',
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

        $executionMode = $request->get('execution_mode', 'async');

        if ($executionMode === 'sync') {
            return $this->generateSync($request);
        } else {
            return $this->generateAsync($request);
        }
    }

    /**
     * Async generation (background queue)
     */
    protected function generateAsync(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Verify page belongs to tenant
            $page = Content::where('tenant_id', $tenantId)
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

            // Determine prompt type
            $promptType = $this->determinePromptType($prompt);

            // Create generation record
            $generation = ContentGeneration::create([
                'tenant_id' => $tenantId,
                'page_id' => $page->id,
                'prompt_id' => $prompt->id,
                'prompt_type' => $promptType,
                'prompt_template' => $prompt->template,
                'variables' => $request->variables ?? [],
                'additional_instructions' => $request->additional_instructions,
                'status' => 'pending',
                'execution_mode' => 'async',
                'created_by' => $user->id,
                'ai_model' => 'gpt-4o',
            ]);

            Log::info('Async content generation created', [
                'generation_id' => $generation->id,
                'page_id' => $page->id,
                'tenant_id' => $tenantId
            ]);

            // Dispatch job for background processing
            ProcessContentGeneration::dispatch($generation);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Content generation started in background',
                    'data' => $generation->load(['content', 'prompt'])
                ], 201);
            }

            return redirect()->route('tenant.content.show', $generation->id)
                ->with('success', 'Content generation started in background!');

        } catch (\Exception $e) {
            Log::error('Error creating async generation', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to start generation'], 500);
            }

            return back()->with('error', 'Failed to start generation. Please try again.')->withInput();
        }
    }

    /**
     * Sync generation (immediate)
     */
    protected function generateSync(Request $request)
    {
        set_time_limit(120); // Allow up to 2 minutes for sync generation

        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $generation = null;

        try {
            // Verify page
            $page = Content::where('tenant_id', $tenantId)
                ->findOrFail($request->page_id);

            // Verify prompt
            $prompt = Prompt::where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)->orWhere('is_system', true);
                })
                ->where('is_active', true)
                ->findOrFail($request->prompt_id);

            // Create generation record with 'processing' status
            $generation = ContentGeneration::create([
                'tenant_id' => $tenantId,
                'page_id' => $page->id,
                'prompt_id' => $prompt->id,
                'prompt_type' => $this->determinePromptType($prompt),
                'prompt_template' => $prompt->template,
                'variables' => $request->variables ?? [],
                'additional_instructions' => $request->additional_instructions,
                'status' => 'processing',
                'execution_mode' => 'sync',
                'created_by' => $user->id,
                'ai_model' => 'gpt-4o',
                'started_at' => now()
            ]);

            // Build final prompt
            $finalPrompt = $this->buildPrompt($generation, $prompt, $page);

            Log::info('Sync generation started', [
                'generation_id' => $generation->id,
                'tenant_id' => $tenantId
            ]);

            // Call OpenAI service IMMEDIATELY (blocks here)
            $openAiService = app(\App\Services\OpenAiService::class);
            $startTime = microtime(true);
            $generatedContent = $openAiService->generateSimpleContent($finalPrompt);
            $endTime = microtime(true);

            $generationTimeMs = (int) (($endTime - $startTime) * 1000);
            $tokensUsed = $this->estimateTokens($finalPrompt . $generatedContent);

            // Update generation with results
            $generation->update([
                'status' => 'completed',
                'generated_content' => $generatedContent,
                'tokens_used' => $tokensUsed,
                'generation_time_ms' => $generationTimeMs,
                'completed_at' => now()
            ]);

            // Update tenant token usage
            $generation->tenant->increment('tokens_used_current', $tokensUsed);

            Log::info('Sync generation completed', [
                'generation_id' => $generation->id,
                'time_ms' => $generationTimeMs,
                'tokens' => $tokensUsed
            ]);

            // Return response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'generation' => $generation->load(['content', 'prompt']),
                    'redirect_url' => route('tenant.content.show', $generation->id)
                ]);
            }

            return redirect()->route('tenant.content.show', $generation->id)
                ->with('success', "Content generated successfully in " . number_format($generationTimeMs / 1000, 2) . " seconds!");

        } catch (\Exception $e) {
            Log::error('Sync generation failed', [
                'error' => $e->getMessage(),
                'generation_id' => $generation->id ?? null
            ]);

            if (isset($generation)) {
                $generation->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now()
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generation failed: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->with('error', 'Generation failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Build final prompt from template + variables
     */
    protected function buildPrompt($generation, $prompt, $page): string
    {
        $finalPrompt = $prompt->template;

        // Replace variables
        if ($generation->variables) {
            foreach ($generation->variables as $key => $value) {
                $finalPrompt = str_replace('{{' . $key . '}}', $value, $finalPrompt);
                $finalPrompt = str_replace('{' . $key . '}', $value, $finalPrompt);
            }
        }

        // Add page context
        $finalPrompt .= "\n\nPage Context:\n";
        $finalPrompt .= "URL: " . $page->url . "\n";
        $finalPrompt .= "Keyword: " . $page->keyword . "\n";

        // Add additional instructions
        if ($generation->additional_instructions) {
            $finalPrompt .= "\n\nAdditional Instructions:\n";
            $finalPrompt .= $generation->additional_instructions;
        }

        return $finalPrompt;
    }

    /**
     * Estimate token usage (rough calculation)
     */
    protected function estimateTokens(string $text): int
    {
        // Rough estimate: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
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
            $generation->load(['content', 'prompt', 'tenant']);

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
                ->with(['content', 'prompt']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by page (content)
            if ($request->filled('page_id')) {
                $query->where('page_id', $request->get('page_id'));
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('content', function ($q) use ($search) {
                    $q->where('url', 'like', "%{$search}%")
                      ->orWhere('keyword', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $generations = $query->paginate($request->get('per_page', 15));

            // Get filter options (use Content instead of Page)
            $pages = Content::where('tenant_id', $tenantId)->where('status', 'active')->get();
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