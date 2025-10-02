<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TenantPromptController extends Controller
{
    /**
     * Display a listing of prompts for the authenticated tenant
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $query = Prompt::query()
                ->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhere('is_system', true);
                })
                ->with(['tenant:id,name']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('alias', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Category filter
            if ($request->filled('category')) {
                $query->where('category', $request->get('category'));
            }

            // Type filter (system vs tenant)
            if ($request->filled('type')) {
                if ($request->get('type') === 'system') {
                    $query->where('is_system', true);
                } elseif ($request->get('type') === 'custom') {
                    $query->where('tenant_id', $tenantId)->where('is_system', false);
                }
            }

            // Status filter
            if ($request->filled('status')) {
                $isActive = $request->get('status') === 'active';
                $query->where('is_active', $isActive);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $prompts = $query->paginate($request->get('per_page', 15));

            // Get filter options
            $categories = Prompt::query()
                ->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhere('is_system', true);
                })
                ->distinct()
                ->pluck('category')
                ->filter()
                ->sort()
                ->values();

            $types = ['system', 'custom'];
            $statuses = ['active', 'inactive'];

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $prompts->items(),
                    'meta' => [
                        'current_page' => $prompts->currentPage(),
                        'last_page' => $prompts->lastPage(),
                        'per_page' => $prompts->perPage(),
                        'total' => $prompts->total(),
                        'from' => $prompts->firstItem(),
                        'to' => $prompts->lastItem()
                    ],
                    'filters' => [
                        'categories' => $categories,
                        'types' => $types,
                        'statuses' => $statuses
                    ]
                ]);
            }

            return view('tenant.prompts.index', compact('prompts', 'categories', 'types', 'statuses'));

        } catch (\Exception $e) {
            Log::error('Error fetching prompts', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch prompts'], 500);
            }

            return back()->with('error', 'Failed to load prompts. Please try again.');
        }
    }

    /**
     * Show the form for creating a new prompt
     */
    public function create()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $categories = Prompt::query()
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)
                  ->orWhere('is_system', true);
            })
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        return view('tenant.prompts.create', compact('categories', 'tenant'));
    }

    /**
     * Store a newly created prompt
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'alias' => 'nullable|string|max:100|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string|max:500',
            'template' => 'required|string',
            'category' => 'nullable|string|max:100',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:100',
            'is_active' => 'boolean',
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

            // Check if alias already exists for this tenant
            if ($request->filled('alias')) {
                $existingPrompt = Prompt::where('tenant_id', $tenantId)
                    ->where('alias', $request->alias)
                    ->first();

                if ($existingPrompt) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'A prompt with this alias already exists.'
                        ], 409);
                    }
                    return back()->withErrors(['alias' => 'A prompt with this alias already exists.'])->withInput();
                }
            }

            $prompt = Prompt::create([
                'tenant_id' => $tenantId,
                'name' => $request->name,
                'alias' => $request->alias,
                'description' => $request->description,
                'template' => $request->template,
                'category' => $request->category,
                'variables' => $request->variables ?? [],
                'is_active' => $request->boolean('is_active', true),
                'is_system' => false,
            ]);

            Log::info('Prompt created successfully', [
                'prompt_id' => $prompt->id,
                'tenant_id' => $tenantId,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Prompt created successfully',
                    'data' => $prompt->load('tenant')
                ], 201);
            }

            return redirect()->route('tenant.prompts.show', $prompt->id)
                ->with('success', 'Prompt created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating prompt', [
                'user_id' => Auth::id(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create prompt'], 500);
            }

            return back()->with('error', 'Failed to create prompt. Please try again.')->withInput();
        }
    }

    /**
     * Display the specified prompt
     */
    public function show(Prompt $prompt)
    {
        $user = Auth::user();

        // Ensure the prompt belongs to the user's tenant or is a system prompt
        if (!$prompt->is_system && $prompt->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this prompt');
        }

        try {
            $prompt->load(['tenant']);

            if (request()->expectsJson()) {
                return response()->json([
                    'data' => $prompt
                ]);
            }

            return view('tenant.prompts.show', compact('prompt'));

        } catch (\Exception $e) {
            Log::error('Error fetching prompt details', [
                'prompt_id' => $prompt->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to fetch prompt details'], 500);
            }

            return back()->with('error', 'Failed to load prompt details. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified prompt
     */
    public function edit(Prompt $prompt)
    {
        $user = Auth::user();

        // Only allow editing tenant-owned prompts, not system prompts
        if ($prompt->is_system || $prompt->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized access to this prompt');
        }

        $tenant = $user->tenant;
        $categories = Prompt::query()
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id)
                  ->orWhere('is_system', true);
            })
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        return view('tenant.prompts.edit', compact('prompt', 'categories', 'tenant'));
    }

    /**
     * Update the specified prompt
     */
    public function update(Request $request, Prompt $prompt)
    {
        $user = Auth::user();

        // Only allow updating tenant-owned prompts, not system prompts
        if ($prompt->is_system || $prompt->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this prompt');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'alias' => 'nullable|string|max:100|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string|max:500',
            'template' => 'required|string',
            'category' => 'nullable|string|max:100',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:100',
            'is_active' => 'boolean',
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
            // Check if alias already exists for this tenant (excluding current prompt)
            if ($request->filled('alias')) {
                $existingPrompt = Prompt::where('tenant_id', $user->tenant_id)
                    ->where('alias', $request->alias)
                    ->where('id', '!=', $prompt->id)
                    ->first();

                if ($existingPrompt) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'A prompt with this alias already exists.'
                        ], 409);
                    }
                    return back()->withErrors(['alias' => 'A prompt with this alias already exists.'])->withInput();
                }
            }

            $prompt->update([
                'name' => $request->name,
                'alias' => $request->alias,
                'description' => $request->description,
                'template' => $request->template,
                'category' => $request->category,
                'variables' => $request->variables ?? [],
                'is_active' => $request->boolean('is_active', $prompt->is_active),
            ]);

            Log::info('Prompt updated successfully', [
                'prompt_id' => $prompt->id,
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Prompt updated successfully',
                    'data' => $prompt->fresh()->load('tenant')
                ]);
            }

            return redirect()->route('tenant.prompts.show', $prompt->id)
                ->with('success', 'Prompt updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating prompt', [
                'prompt_id' => $prompt->id,
                'user_id' => Auth::id(),
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update prompt'], 500);
            }

            return back()->with('error', 'Failed to update prompt. Please try again.')->withInput();
        }
    }

    /**
     * Remove the specified prompt from storage
     */
    public function destroy(Prompt $prompt)
    {
        $user = Auth::user();

        // Only allow deleting tenant-owned prompts, not system prompts
        if ($prompt->is_system || $prompt->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this prompt');
        }

        try {
            // Store prompt info for logging
            $promptInfo = [
                'id' => $prompt->id,
                'name' => $prompt->name,
                'tenant_id' => $prompt->tenant_id
            ];

            // Delete the prompt
            $prompt->delete();

            Log::info('Prompt deleted successfully', [
                'prompt_info' => $promptInfo,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Prompt deleted successfully'
                ]);
            }

            return redirect()->route('tenant.prompts.index')
                ->with('success', 'Prompt deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting prompt', [
                'prompt_id' => $prompt->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to delete prompt'], 500);
            }

            return back()->with('error', 'Failed to delete prompt. Please try again.');
        }
    }

    /**
     * Duplicate a prompt
     */
    public function duplicate(Prompt $prompt)
    {
        $user = Auth::user();

        // Ensure the prompt belongs to the user's tenant or is a system prompt
        if (!$prompt->is_system && $prompt->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this prompt');
        }

        try {
            $duplicatedPrompt = Prompt::create([
                'tenant_id' => $user->tenant_id,
                'name' => $prompt->name . ' (Copy)',
                'alias' => null, // Clear alias to avoid duplicates
                'description' => $prompt->description,
                'template' => $prompt->template,
                'category' => $prompt->category,
                'variables' => $prompt->variables,
                'is_active' => true,
                'is_system' => false,
            ]);

            Log::info('Prompt duplicated successfully', [
                'original_prompt_id' => $prompt->id,
                'new_prompt_id' => $duplicatedPrompt->id,
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Prompt duplicated successfully',
                    'data' => $duplicatedPrompt->load('tenant')
                ], 201);
            }

            return redirect()->route('tenant.prompts.edit', $duplicatedPrompt->id)
                ->with('success', 'Prompt duplicated successfully! You can now edit it.');

        } catch (\Exception $e) {
            Log::error('Error duplicating prompt', [
                'prompt_id' => $prompt->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to duplicate prompt'], 500);
            }

            return back()->with('error', 'Failed to duplicate prompt. Please try again.');
        }
    }
}