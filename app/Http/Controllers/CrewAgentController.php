<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewAgent;
use App\Models\CrewAgentTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CrewAgentController extends Controller
{
    /**
     * Store a newly created agent
     */
    public function store(Request $request, Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('manageAgents', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this crew');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'goal' => 'nullable|string|max:1000',
            'backstory' => 'nullable|string|max:2000',
            'tools' => 'nullable|array',
            'tools.*' => 'exists:crew_agent_tools,id',
            'allow_delegation' => 'nullable|boolean',
            'verbose' => 'nullable|boolean',
            'max_iterations' => 'nullable|integer|min:1|max:100',
            'llm_config' => 'nullable|array',
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
            // Get next order number
            $maxOrder = $crew->agents()->max('order') ?? -1;

            $agent = CrewAgent::create([
                'crew_id' => $crew->id,
                'name' => $request->name,
                'role' => $request->role,
                'goal' => $request->goal,
                'backstory' => $request->backstory,
                'tools' => $request->tools ?? [],
                'allow_delegation' => $request->boolean('allow_delegation', false),
                'verbose' => $request->boolean('verbose', false),
                'max_iterations' => $request->max_iterations ?? 25,
                'llm_config' => $request->llm_config ?? [],
                'order' => $maxOrder + 1,
            ]);

            Log::info('Crew agent created', [
                'agent_id' => $agent->id,
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Agent created successfully',
                    'data' => $agent
                ], 201);
            }

            return redirect()->route('tenant.crews.edit', $crew)
                ->with('success', 'Agent created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating agent', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to create agent'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to create agent. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update the specified agent
     */
    public function update(Request $request, Crew $crew, CrewAgent $agent)
    {
        $user = Auth::user();
        $this->authorize('manageAgents', $crew);

        if ($crew->tenant_id !== $user->tenant_id || $agent->crew_id !== $crew->id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'goal' => 'nullable|string|max:1000',
            'backstory' => 'nullable|string|max:2000',
            'tools' => 'nullable|array',
            'tools.*' => 'exists:crew_agent_tools,id',
            'allow_delegation' => 'nullable|boolean',
            'verbose' => 'nullable|boolean',
            'max_iterations' => 'nullable|integer|min:1|max:100',
            'llm_config' => 'nullable|array',
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
            $agent->update([
                'name' => $request->name,
                'role' => $request->role,
                'goal' => $request->goal,
                'backstory' => $request->backstory,
                'tools' => $request->tools ?? [],
                'allow_delegation' => $request->boolean('allow_delegation'),
                'verbose' => $request->boolean('verbose'),
                'max_iterations' => $request->max_iterations ?? 25,
                'llm_config' => $request->llm_config ?? [],
            ]);

            Log::info('Crew agent updated', [
                'agent_id' => $agent->id,
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Agent updated successfully',
                    'data' => $agent->fresh()
                ]);
            }

            return redirect()->route('tenant.crews.edit', $crew)
                ->with('success', 'Agent updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating agent', [
                'agent_id' => $agent->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to update agent'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to update agent. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified agent
     */
    public function destroy(Crew $crew, CrewAgent $agent)
    {
        $user = Auth::user();
        $this->authorize('manageAgents', $crew);

        if ($crew->tenant_id !== $user->tenant_id || $agent->crew_id !== $crew->id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        try {
            $agentInfo = [
                'id' => $agent->id,
                'name' => $agent->name,
                'role' => $agent->role,
            ];

            $agent->delete();

            Log::info('Crew agent deleted', [
                'agent_info' => $agentInfo,
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Agent deleted successfully'
                ]);
            }

            return redirect()->route('tenant.crews.edit', $crew)
                ->with('success', 'Agent deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting agent', [
                'agent_id' => $agent->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to delete agent'
                ], 500);
            }

            return back()->with('error', 'Failed to delete agent. Please try again.');
        }
    }

    /**
     * Reorder agents
     */
    public function reorder(Request $request, Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('manageAgents', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'agent_ids' => 'required|array',
            'agent_ids.*' => 'exists:crew_agents,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $agentIds = $request->agent_ids;

            foreach ($agentIds as $index => $agentId) {
                CrewAgent::where('id', $agentId)
                    ->where('crew_id', $crew->id)
                    ->update(['order' => $index]);
            }

            Log::info('Agents reordered', [
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Agents reordered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error reordering agents', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to reorder agents'
            ], 500);
        }
    }

    /**
     * Get available tools
     */
    public function getAvailableTools()
    {
        $tools = CrewAgentTool::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $tools
        ]);
    }
}
