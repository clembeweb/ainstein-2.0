<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CrewTemplateController extends Controller
{
    /**
     * Display a listing of templates
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $this->authorize('viewAny', CrewTemplate::class);

        $templatesQuery = CrewTemplate::query()
            ->where(function ($q) use ($tenantId) {
                $q->where('is_system', true)
                  ->orWhere('is_public', true)
                  ->orWhere('tenant_id', $tenantId);
            })
            ->with(['crew:id,name', 'creator:id,name']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $templatesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $type = $request->get('type');
            if ($type === 'system') {
                $templatesQuery->where('is_system', true);
            } elseif ($type === 'public') {
                $templatesQuery->where('is_public', true)->where('is_system', false);
            } elseif ($type === 'private') {
                $templatesQuery->where('tenant_id', $tenantId)
                    ->where('is_system', false)
                    ->where('is_public', false);
            }
        }

        // Category filter
        if ($request->filled('category')) {
            $templatesQuery->where('category', $request->get('category'));
        }

        $templates = $templatesQuery
            ->orderBy('is_system', 'desc')
            ->orderBy('rating', 'desc')
            ->orderBy('usage_count', 'desc')
            ->paginate(15);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $templates
            ]);
        }

        return view('tenant.crew-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $this->authorize('create', CrewTemplate::class);

        // Get user's crews
        $crews = Crew::where('tenant_id', $user->tenant_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Pre-select crew if provided
        $selectedCrewId = $request->get('crew_id');
        $selectedCrew = null;
        if ($selectedCrewId) {
            $selectedCrew = $crews->firstWhere('id', $selectedCrewId);
        }

        return view('tenant.crew-templates.create', compact('crews', 'selectedCrew'));
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorize('create', CrewTemplate::class);

        $validator = Validator::make($request->all(), [
            'crew_id' => 'required|exists:crews,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'is_public' => 'nullable|boolean',
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

        // Verify crew belongs to tenant
        $crew = Crew::where('tenant_id', $user->tenant_id)
            ->where('id', $request->crew_id)
            ->first();

        if (!$crew) {
            $message = 'Crew not found or access denied.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 404);
            }
            return back()->with('error', $message);
        }

        try {
            $template = CrewTemplate::create([
                'tenant_id' => $user->tenant_id,
                'crew_id' => $crew->id,
                'created_by' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'is_system' => false,
                'is_public' => $request->boolean('is_public', false),
                'is_active' => true,
                'usage_count' => 0,
                'rating' => 0,
            ]);

            Log::info('Crew template created', [
                'template_id' => $template->id,
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Template created successfully',
                    'data' => $template->load(['crew'])
                ], 201);
            }

            return redirect()->route('tenant.crew-templates.show', $template)
                ->with('success', 'Template created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating template', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to create template'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to create template. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified template
     */
    public function show(CrewTemplate $template)
    {
        $user = Auth::user();
        $this->authorize('view', $template);

        $template->load([
            'crew.agents' => function ($q) {
                $q->orderBy('order');
            },
            'crew.tasks' => function ($q) {
                $q->orderBy('order');
            },
            'creator:id,name'
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $template
            ]);
        }

        return view('tenant.crew-templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(CrewTemplate $template)
    {
        $user = Auth::user();
        $this->authorize('update', $template);

        if ($template->is_system) {
            $message = 'System templates cannot be edited.';
            if (request()->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }
            return back()->with('error', $message);
        }

        if ($template->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized access to this template');
        }

        return view('tenant.crew-templates.edit', compact('template'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, CrewTemplate $template)
    {
        $user = Auth::user();
        $this->authorize('update', $template);

        if ($template->is_system) {
            $message = 'System templates cannot be updated.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }
            return back()->with('error', $message);
        }

        if ($template->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this template');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
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
            $template->update([
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'is_public' => $request->boolean('is_public'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            Log::info('Template updated', [
                'template_id' => $template->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Template updated successfully',
                    'data' => $template->fresh()
                ]);
            }

            return redirect()->route('tenant.crew-templates.show', $template)
                ->with('success', 'Template updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating template', [
                'template_id' => $template->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to update template'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to update template. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified template
     */
    public function destroy(CrewTemplate $template)
    {
        $user = Auth::user();
        $this->authorize('delete', $template);

        if ($template->is_system) {
            $message = 'System templates cannot be deleted.';
            if (request()->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }
            return back()->with('error', $message);
        }

        if ($template->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this template');
        }

        try {
            $templateInfo = [
                'id' => $template->id,
                'name' => $template->name,
            ];

            $template->delete();

            Log::info('Template deleted', [
                'template_info' => $templateInfo,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Template deleted successfully'
                ]);
            }

            return redirect()->route('tenant.crew-templates.index')
                ->with('success', 'Template deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting template', [
                'template_id' => $template->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to delete template'
                ], 500);
            }

            return back()->with('error', 'Failed to delete template. Please try again.');
        }
    }

    /**
     * Use a template to create a new crew
     */
    public function use(CrewTemplate $template)
    {
        $user = Auth::user();
        $this->authorize('use', $template);

        // Increment usage count
        $template->increment('usage_count');

        // Redirect to crew creation with template pre-selected
        return redirect()->route('tenant.crews.create', ['template_id' => $template->id])
            ->with('success', 'Template selected. Configure your new crew below.');
    }

    /**
     * Publish a template (make it public)
     */
    public function publish(CrewTemplate $template)
    {
        $user = Auth::user();
        $this->authorize('publish', $template);

        if ($template->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this template');
        }

        try {
            $template->update(['is_public' => true]);

            Log::info('Template published', [
                'template_id' => $template->id,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Template published successfully'
                ]);
            }

            return back()->with('success', 'Template published successfully! It\'s now visible to all users.');

        } catch (\Exception $e) {
            Log::error('Error publishing template', [
                'template_id' => $template->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to publish template'
                ], 500);
            }

            return back()->with('error', 'Failed to publish template. Please try again.');
        }
    }
}
