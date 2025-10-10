<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CrewTaskController extends Controller
{
    /**
     * Store a newly created task
     */
    public function store(Request $request, Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('manageTasks', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this crew');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'expected_output' => 'nullable|string|max:1000',
            'agent_id' => 'nullable|exists:crew_agents,id',
            'context' => 'nullable|array',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'exists:crew_tasks,id',
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

        // Verify agent belongs to this crew
        if ($request->filled('agent_id')) {
            $agent = $crew->agents()->find($request->agent_id);
            if (!$agent) {
                $message = 'Agent not found in this crew.';
                if ($request->expectsJson()) {
                    return response()->json(['error' => $message], 404);
                }
                return back()->with('error', $message);
            }
        }

        try {
            // Get next order number
            $maxOrder = $crew->tasks()->max('order') ?? -1;

            $task = CrewTask::create([
                'crew_id' => $crew->id,
                'agent_id' => $request->agent_id,
                'name' => $request->name,
                'description' => $request->description,
                'expected_output' => $request->expected_output,
                'context' => $request->context ?? [],
                'dependencies' => $request->dependencies ?? [],
                'order' => $maxOrder + 1,
            ]);

            Log::info('Crew task created', [
                'task_id' => $task->id,
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Task created successfully',
                    'data' => $task->load('agent')
                ], 201);
            }

            return redirect()->route('tenant.crews.edit', $crew)
                ->with('success', 'Task created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating task', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to create task'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to create task. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update the specified task
     */
    public function update(Request $request, Crew $crew, CrewTask $task)
    {
        $user = Auth::user();
        $this->authorize('manageTasks', $crew);

        if ($crew->tenant_id !== $user->tenant_id || $task->crew_id !== $crew->id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'expected_output' => 'nullable|string|max:1000',
            'agent_id' => 'nullable|exists:crew_agents,id',
            'context' => 'nullable|array',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'exists:crew_tasks,id',
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

        // Verify agent belongs to this crew
        if ($request->filled('agent_id')) {
            $agent = $crew->agents()->find($request->agent_id);
            if (!$agent) {
                $message = 'Agent not found in this crew.';
                if ($request->expectsJson()) {
                    return response()->json(['error' => $message], 404);
                }
                return back()->with('error', $message);
            }
        }

        try {
            $task->update([
                'agent_id' => $request->agent_id,
                'name' => $request->name,
                'description' => $request->description,
                'expected_output' => $request->expected_output,
                'context' => $request->context ?? [],
                'dependencies' => $request->dependencies ?? [],
            ]);

            Log::info('Crew task updated', [
                'task_id' => $task->id,
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Task updated successfully',
                    'data' => $task->fresh(['agent'])
                ]);
            }

            return redirect()->route('tenant.crews.edit', $crew)
                ->with('success', 'Task updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating task', [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to update task'
                ], 500);
            }

            return back()
                ->with('error', 'Failed to update task. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy(Crew $crew, CrewTask $task)
    {
        $user = Auth::user();
        $this->authorize('manageTasks', $crew);

        if ($crew->tenant_id !== $user->tenant_id || $task->crew_id !== $crew->id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access');
        }

        try {
            $taskInfo = [
                'id' => $task->id,
                'name' => $task->name,
            ];

            $task->delete();

            Log::info('Crew task deleted', [
                'task_info' => $taskInfo,
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Task deleted successfully'
                ]);
            }

            return redirect()->route('tenant.crews.edit', $crew)
                ->with('success', 'Task deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting task', [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to delete task'
                ], 500);
            }

            return back()->with('error', 'Failed to delete task. Please try again.');
        }
    }

    /**
     * Reorder tasks
     */
    public function reorder(Request $request, Crew $crew)
    {
        $user = Auth::user();
        $this->authorize('manageTasks', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:crew_tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $taskIds = $request->task_ids;

            foreach ($taskIds as $index => $taskId) {
                CrewTask::where('id', $taskId)
                    ->where('crew_id', $crew->id)
                    ->update(['order' => $index]);
            }

            Log::info('Tasks reordered', [
                'crew_id' => $crew->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Tasks reordered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error reordering tasks', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to reorder tasks'
            ], 500);
        }
    }
}
