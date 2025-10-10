<?php

namespace App\Http\Controllers;

use App\Models\Crew;
use App\Models\CrewExecution;
use App\Models\CrewExecutionLog;
use App\Jobs\ExecuteCrewJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CrewExecutionController extends Controller
{
    /**
     * Display a listing of executions
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $this->authorize('viewAny', CrewExecution::class);

        $executionsQuery = CrewExecution::where('tenant_id', $tenantId)
            ->with(['crew:id,name', 'executor:id,name']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $executionsQuery->whereHas('crew', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $executionsQuery->where('status', $request->get('status'));
        }

        // Crew filter
        if ($request->filled('crew_id')) {
            $executionsQuery->where('crew_id', $request->get('crew_id'));
        }

        $executions = $executionsQuery->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $executions
            ]);
        }

        return view('tenant.crew-executions.index', compact('executions'));
    }

    /**
     * Execute a crew (start new execution)
     */
    public function execute(Request $request, Crew $crew)
    {
        $user = Auth::user();

        $this->authorize('execute', $crew);

        if ($crew->tenant_id !== $user->tenant_id) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this crew');
        }

        // Check if crew is active
        if (!$crew->isActive()) {
            $message = 'This crew is not active. Please activate it before execution.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 400);
            }
            return back()->with('error', $message);
        }

        // Check if tenant has available tokens
        if (!$user->tenant || !$user->tenant->canGenerateContent()) {
            $message = 'Insufficient tokens. Please upgrade your plan.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 402);
            }
            return back()->with('error', $message);
        }

        $validator = Validator::make($request->all(), [
            'input_variables' => 'nullable|array',
            'input_variables.*' => 'string|max:2000',
            'use_mock' => 'nullable|boolean',
            'mode' => 'nullable|string|in:mock,real',
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
            // Determine if mock mode (support both 'use_mock' boolean and 'mode' string)
            $useMock = $request->has('mode')
                ? $request->input('mode') === 'mock'
                : $request->boolean('use_mock', false);

            // Create execution record
            $execution = CrewExecution::create([
                'tenant_id' => $user->tenant_id,
                'crew_id' => $crew->id,
                'executed_by' => $user->id,
                'input_variables' => $request->input_variables ?? [],
                'status' => 'pending',
                'retry_count' => 0,
            ]);

            Log::info('Crew execution created', [
                'execution_id' => $execution->id,
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'use_mock' => $useMock
            ]);

            // Dispatch job to execute crew
            ExecuteCrewJob::dispatch($execution, $useMock);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Crew execution started',
                    'execution_url' => route('tenant.crew-executions.show', $execution),
                    'data' => $execution->load(['crew'])
                ], 201);
            }

            return redirect()->route('tenant.crew-executions.show', $execution)
                ->with('success', 'Crew execution started! You can monitor progress here.');

        } catch (\Exception $e) {
            Log::error('Error starting crew execution', [
                'crew_id' => $crew->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to start execution'
                ], 500);
            }

            return back()->with('error', 'Failed to start execution. Please try again.');
        }
    }

    /**
     * Display the specified execution
     */
    public function show(CrewExecution $execution)
    {
        $user = Auth::user();
        $this->authorize('view', $execution);

        if ($execution->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this execution');
        }

        $execution->load([
            'crew:id,name,process_type',
            'executor:id,name',
            'logs' => function ($q) {
                $q->orderBy('created_at', 'asc');
            }
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'data' => $execution
            ]);
        }

        return view('tenant.crew-executions.show', compact('execution'));
    }

    /**
     * Cancel a running execution
     */
    public function cancel(CrewExecution $execution)
    {
        $user = Auth::user();
        $this->authorize('cancel', $execution);

        if ($execution->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this execution');
        }

        if (!in_array($execution->status, ['pending', 'running'])) {
            $message = 'Cannot cancel execution that is not pending or running.';
            if (request()->expectsJson()) {
                return response()->json(['error' => $message], 400);
            }
            return back()->with('error', $message);
        }

        try {
            $execution->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'error_message' => 'Cancelled by user',
            ]);

            // TODO: Send signal to Python process to stop execution

            Log::info('Crew execution cancelled', [
                'execution_id' => $execution->id,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Execution cancelled successfully'
                ]);
            }

            return redirect()->route('tenant.crew-executions.show', $execution)
                ->with('success', 'Execution cancelled successfully.');

        } catch (\Exception $e) {
            Log::error('Error cancelling execution', [
                'execution_id' => $execution->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to cancel execution'
                ], 500);
            }

            return back()->with('error', 'Failed to cancel execution. Please try again.');
        }
    }

    /**
     * Retry a failed execution
     */
    public function retry(CrewExecution $execution)
    {
        $user = Auth::user();
        $this->authorize('retry', $execution);

        if ($execution->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this execution');
        }

        if (!in_array($execution->status, ['failed', 'cancelled'])) {
            $message = 'Cannot retry execution that is not failed or cancelled.';
            if (request()->expectsJson()) {
                return response()->json(['error' => $message], 400);
            }
            return back()->with('error', $message);
        }

        // Check if tenant has available tokens
        if (!$user->tenant || !$user->tenant->canGenerateContent()) {
            $message = 'Insufficient tokens. Please upgrade your plan.';
            if (request()->expectsJson()) {
                return response()->json(['error' => $message], 402);
            }
            return back()->with('error', $message);
        }

        try {
            // Create new execution with same parameters
            $newExecution = CrewExecution::create([
                'tenant_id' => $execution->tenant_id,
                'crew_id' => $execution->crew_id,
                'executed_by' => $user->id,
                'input_variables' => $execution->input_variables,
                'status' => 'pending',
                'retry_count' => $execution->retry_count + 1,
            ]);

            // Dispatch job to execute crew (always with real mode on retry)
            ExecuteCrewJob::dispatch($newExecution, false);

            Log::info('Crew execution retried', [
                'original_execution_id' => $execution->id,
                'new_execution_id' => $newExecution->id,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Execution retried successfully',
                    'execution_url' => route('tenant.crew-executions.show', $newExecution),
                    'data' => $newExecution->load(['crew'])
                ], 201);
            }

            return redirect()->route('tenant.crew-executions.show', $newExecution)
                ->with('success', 'Execution retried successfully!');

        } catch (\Exception $e) {
            Log::error('Error retrying execution', [
                'execution_id' => $execution->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to retry execution'
                ], 500);
            }

            return back()->with('error', 'Failed to retry execution. Please try again.');
        }
    }

    /**
     * Get execution logs (for real-time monitoring)
     */
    public function logs(CrewExecution $execution)
    {
        $user = Auth::user();
        $this->authorize('viewLogs', $execution);

        if ($execution->tenant_id !== $user->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $logs = $execution->logs()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $execution->status,
                'execution' => [
                    'id' => $execution->id,
                    'status' => $execution->status,
                    'progress' => $execution->progress,
                    'total_tokens_used' => $execution->total_tokens_used,
                    'cost' => $execution->cost,
                ],
                'logs' => $logs
            ]
        ]);
    }

    /**
     * Delete an execution
     */
    public function destroy(CrewExecution $execution)
    {
        $user = Auth::user();
        $this->authorize('delete', $execution);

        if ($execution->tenant_id !== $user->tenant_id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized access to this execution');
        }

        try {
            $executionInfo = [
                'id' => $execution->id,
                'crew_id' => $execution->crew_id,
                'status' => $execution->status,
            ];

            $execution->delete();

            Log::info('Crew execution deleted', [
                'execution_info' => $executionInfo,
                'user_id' => $user->id
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Execution deleted successfully'
                ]);
            }

            return redirect()->route('tenant.crew-executions.index')
                ->with('success', 'Execution deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting execution', [
                'execution_id' => $execution->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to delete execution'
                ], 500);
            }

            return back()->with('error', 'Failed to delete execution. Please try again.');
        }
    }
}
