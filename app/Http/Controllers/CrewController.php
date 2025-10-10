<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewAgent;
use App\Models\CrewTask;
use App\Models\CrewTemplate;
use App\Models\CrewExecution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CrewController extends Controller
{
    /**
     * Display a listing of crews
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $this->authorize('viewAny', Crew::class);

        $crewsQuery = Crew::where('tenant_id', $tenantId)
            ->withCount(['agents', 'tasks', 'executions'])
            ->with(['creator:id,name']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $crewsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $crewsQuery->where('status', $request->get('status'));
        }

        // Process type filter
        if ($request->filled('process_type')) {
            $crewsQuery->where('process_type', $request->get('process_type'));
        }

        $crews = $crewsQuery->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $crews
            ]);
        }

        return view('tenant.crews.index', compact('crews'));
    }

    /**
     * Show the form for creating a new crew
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $this->authorize('create', Crew::class);

        // Get available templates
        $templates = CrewTemplate::query()
            ->where(function ($q) use ($user) {
                $q->where('is_system', true)
                  ->orWhere('is_public', true)
                  ->orWhere('tenant_id', $user->tenant_id);
            })
            ->where('is_active', true)
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        // Pre-select template if provided
        $selectedTemplateId = $request->get('template_id');
        $selectedTemplate = null;
        if ($selectedTemplateId) {
            $selectedTemplate = $templates->firstWhere('id', $selectedTemplateId);
        }

        return view('tenant.crews.create', compact('templates', 'selectedTemplate'));
    }

    /**
     * Store a newly created crew
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorize('create', Crew::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'process_type' => 'required|in:sequential,hierarchical',
            'configuration' => 'nullable|array',
            'template_id' => 'nullable|exists:crew_templates,id',
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
            DB::beginTransaction();

            // Create crew
            $crew = Crew::create([
                'tenant_id' => $user->tenant_id,
                'created_by' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'process_type' => $request->process_type,
                'configuration' => $request->configuration ?? [],
                'status' => 'draft',
            ]);

            // If template is provided, clone agents and tasks
            if ($request->filled('template_id')) {
                $template = CrewTemplate::find($request->template_id);
                if ($template && $template->crew) {
                    $this->cloneCrewFromTemplate($crew, $template->crew);
                }
            }

            DB::commit();

            Log::info('Crew created successfully', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Crew created successfully',
                    'data' => $crew->load(['agents', 'tasks'])
                ], 201);
            }

            return redirect()->route('tenant.crews.edit', $crew)
                ->with('success', 'Crew created successfully! Now add agents and tasks.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating crew', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to create crew'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to create crew. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified crew
     */
    public function show(Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('view', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this crew');
        }

        $crew->load([
            'agents' => function ($q) {
                $q->orderBy('order');
            },
            'tasks' => function ($q) {
                $q->orderBy('order');
            },
            'executions' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(10);
            },
            'creator:id,name'
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $crew
            ]);
        }

        // Check if OpenAI API key is configured
        $openAiConfigured = !empty(\App\Models\PlatformSetting::get('openai_api_key'));

        return view('tenant.crews.show', compact('crew', 'openAiConfigured'));
    }

    /**
     * Show the form for editing the specified crew
     */
    public function edit(Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('update', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized access to this crew');
        }

        $crew->load([
            'agents' => function ($q) {
                $q->orderBy('order');
            },
            'tasks' => function ($q) {
                $q->orderBy('order');
            }
        ]);

        return view('tenant.crews.edit', compact('crew'));
    }

    /**
     * Update the specified crew
     */
    public function update(Request $request, Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('update', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this crew');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'process_type' => 'required|in:sequential,hierarchical',
            'status' => 'required|in:draft,active,archived',
            'configuration' => 'nullable|array',
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
            $crew->update([
                'name' => $request->name,
                'description' => $request->description,
                'process_type' => $request->process_type,
                'status' => $request->status,
                'configuration' => $request->configuration ?? [],
            ]);

            Log::info('Crew updated successfully', [
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Crew updated successfully',
                    'data' => $crew->fresh()
                ]);
            }

            return redirect()->route('tenant.crews.show', $crew)
                ->with('success', 'Crew updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating crew', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to update crew'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to update crew. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified crew
     */
    public function destroy(Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('delete', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this crew');
        }

        try {
            $crewInfo = [
                'id' => $crew->id,
                'name' => $crew->name,
            ];

            $crew->delete();

            Log::info('Crew deleted successfully', [
                'crew_info' => $crewInfo,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Crew deleted successfully'
                ]);
            }

            return redirect()->route('tenant.crews.index')
                ->with('success', 'Crew deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting crew', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to delete crew'
                ], 500);
            }

            return back()->with('error', 'Failed to delete crew. Please try again.');
        }
    }

    /**
     * Clone a crew
     */
    public function clone(Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('create', Crew::class);

        if ($crew->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this crew');
        }

        try {
            DB::beginTransaction();

            $newCrew = $crew->replicate();
            $newCrew->name = $crew->name . ' (Copy)';
            $newCrew->status = 'draft';
            $newCrew->created_by = $user->id;
            $newCrew->total_executions = 0;
            $newCrew->successful_executions = 0;
            $newCrew->failed_executions = 0;
            $newCrew->average_execution_time = 0;
            $newCrew->save();

            $this->cloneCrewFromTemplate($newCrew, $crew);

            DB::commit();

            Log::info('Crew cloned successfully', [
                'original_crew_id' => $crew->id,
                'new_crew_id' => $newCrew->id,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Crew cloned successfully',
                    'data' => $newCrew->load(['agents', 'tasks'])
                ], 201);
            }

            return redirect()->route('tenant.crews.edit', $newCrew)
                ->with('success', 'Crew cloned successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error cloning crew', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to clone crew'
                ], 500);
            }

            return back()->with('error', 'Failed to clone crew. Please try again.');
        }
    }

    /**
     * Clone agents and tasks from template/source crew
     */
    protected function cloneCrewFromTemplate(Crew $newCrew, Crew $sourceCrew): void
    {
        // Clone agents
        $agentMap = [];
        foreach ($sourceCrew->agents as $sourceAgent) {
            $newAgent = $sourceAgent->replicate();
            $newAgent->crew_id = $newCrew->id;
            $newAgent->save();
            $agentMap[$sourceAgent->id] = $newAgent->id;
        }

        // Clone tasks
        foreach ($sourceCrew->tasks as $sourceTask) {
            $newTask = $sourceTask->replicate();
            $newTask->crew_id = $newCrew->id;

            // Update agent_id if it was cloned
            if ($sourceTask->agent_id && isset($agentMap[$sourceTask->agent_id])) {
                $newTask->agent_id = $agentMap[$sourceTask->agent_id];
            }

            // Update dependencies if they exist
            if ($sourceTask->dependencies) {
                // This would need more complex logic to remap task IDs
                // For now, clear dependencies on cloned tasks
                $newTask->dependencies = null;
            }

            $newTask->save();
        }
    }
}
