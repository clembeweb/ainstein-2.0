<?php

namespace App\Jobs;

use App\Models\CrewExecution;
use App\Models\CrewExecutionLog;
use App\Models\PlatformSetting;
use App\Services\MockCrewAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ExecuteCrewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;
    public $backoff = 30; // 30 seconds between retries

    protected CrewExecution $execution;
    protected bool $useMockService;

    /**
     * Create a new job instance.
     */
    public function __construct(CrewExecution $execution, bool $useMockService = false)
    {
        $this->execution = $execution;
        $this->useMockService = $useMockService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // If mock mode, use MockCrewAIService
        if ($this->useMockService) {
            $this->handleMockExecution();
            return;
        }

        // Real execution with Python bridge
        $this->handleRealExecution();
    }

    /**
     * Handle mock execution
     */
    protected function handleMockExecution(): void
    {
        try {
            Log::info('ExecuteCrewJob: Using MockCrewAIService', [
                'execution_id' => $this->execution->id,
                'crew_id' => $this->execution->crew_id
            ]);

            $mockService = new MockCrewAIService();
            $mockService->executeCrew($this->execution);

        } catch (\Exception $e) {
            Log::error('ExecuteCrewJob: Mock execution failed', [
                'execution_id' => $this->execution->id,
                'error' => $e->getMessage()
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handle real execution with Python bridge
     */
    protected function handleRealExecution(): void
    {
        try {
            // Get OpenAI API key from PlatformSettings
            $apiKey = PlatformSetting::get('openai_api_key');

            if (empty($apiKey)) {
                throw new \Exception('OpenAI API key not configured in Platform Settings. Please configure it in the admin panel.');
            }

            Log::info('ExecuteCrewJob: Starting Python bridge execution', [
                'execution_id' => $this->execution->id,
                'crew_id' => $this->execution->crew_id
            ]);

            // Update execution status
            $this->execution->update([
                'status' => 'running',
                'started_at' => now(),
                'progress' => 0,
            ]);

            // Prepare crew configuration
            $crewConfig = $this->prepareCrewConfig();

            // Prepare input variables
            $inputVariables = $this->execution->input_variables ?? [];

            // Build Python command
            $pythonPath = $this->getPythonPath();
            $bridgePath = base_path('python/bridge.py');

            $command = [
                $pythonPath,
                $bridgePath,
                $this->execution->id,
                json_encode($crewConfig),
                json_encode($inputVariables)
            ];

            // Create process with environment variables
            $process = new Process($command);
            $process->setTimeout($this->timeout);
            $process->setWorkingDirectory(base_path('python'));

            // Set environment variables
            $process->setEnv([
                'OPENAI_API_KEY' => $apiKey,
                'OPENAI_DEFAULT_MODEL' => PlatformSetting::get('openai_default_model', 'gpt-4o-mini'),
                'PYTHONUNBUFFERED' => '1', // Disable Python output buffering
            ]);

            // Log crew configuration
            $this->createLog('info', "Starting crew execution: {$this->execution->crew->name}");
            $this->createLog('info', "Process type: {$this->execution->crew->process_type}");
            $this->createLog('info', "Agents: {$this->execution->crew->agents->count()} | Tasks: {$this->execution->crew->tasks->count()}");

            // Run process and capture output in real-time
            $finalResult = null;
            $outputBuffer = '';

            $process->run(function ($type, $buffer) use (&$finalResult, &$outputBuffer) {
                $outputBuffer .= $buffer;

                // Parse real-time logs
                $lines = explode("\n", $buffer);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    // Check for final result marker
                    if ($line === '__FINAL_RESULT__') {
                        continue;
                    }

                    // Try to parse as JSON log
                    try {
                        $log = json_decode($line, true);
                        if ($log && isset($log['level'], $log['message'])) {
                            $this->createLog(
                                $log['level'],
                                $log['message'],
                                $log['data'] ?? []
                            );

                            // Update progress based on log content
                            $this->updateProgressFromLog($log);
                        }
                    } catch (\Exception $e) {
                        // Not a JSON log, might be other output
                        if (strlen($line) > 10) {
                            Log::debug('Python output (non-JSON): ' . substr($line, 0, 100));
                        }
                    }
                }
            });

            // Parse final result
            if (strpos($outputBuffer, '__FINAL_RESULT__') !== false) {
                $parts = explode('__FINAL_RESULT__', $outputBuffer);
                $jsonResult = trim(end($parts));

                try {
                    $finalResult = json_decode($jsonResult, true);
                } catch (\Exception $e) {
                    Log::error('ExecuteCrewJob: Failed to parse final result', [
                        'execution_id' => $this->execution->id,
                        'error' => $e->getMessage(),
                        'json' => substr($jsonResult, 0, 500)
                    ]);
                }
            }

            // Check if process was successful
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Process results
            if ($finalResult && $finalResult['success']) {
                $this->handleSuccess($finalResult);
            } else {
                throw new \Exception($finalResult['error'] ?? 'Unknown error from Python bridge');
            }

        } catch (\Exception $e) {
            Log::error('ExecuteCrewJob: Real execution failed', [
                'execution_id' => $this->execution->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->handleFailure($e);
            $this->fail($e);
        }
    }

    /**
     * Prepare crew configuration for Python
     */
    protected function prepareCrewConfig(): array
    {
        $crew = $this->execution->crew->load(['agents', 'tasks']);

        return [
            'id' => $crew->id,
            'name' => $crew->name,
            'process_type' => $crew->process_type,
            'agents' => $crew->agents->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'role' => $agent->role,
                    'goal' => $agent->goal,
                    'backstory' => $agent->backstory,
                    'allow_delegation' => $agent->allow_delegation,
                    'verbose' => $agent->verbose,
                    'max_iterations' => $agent->max_iterations,
                    'tools' => $agent->tools ?? [],
                    'llm_config' => $agent->llm_config ?? [],
                ];
            })->toArray(),
            'tasks' => $crew->tasks->sortBy('order')->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'expected_output' => $task->expected_output,
                    'agent_id' => $task->agent_id,
                    'context' => $task->context ?? [],
                    'dependencies' => $task->dependencies ?? [],
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get Python executable path
     */
    protected function getPythonPath(): string
    {
        // Check for virtual environment
        $venvPython = base_path('python/venv/Scripts/python.exe');
        if (file_exists($venvPython)) {
            return $venvPython;
        }

        // Fallback to system Python
        return 'python';
    }

    /**
     * Handle successful execution
     */
    protected function handleSuccess(array $result): void
    {
        $this->createLog('info', '✅ Execution completed successfully!');
        $this->createLog('info', "Total tokens used: {$result['tokens_used']}");
        $this->createLog('info', "Estimated cost: $" . number_format($result['cost'], 4));

        // Update execution record
        $this->execution->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
            'total_tokens_used' => $result['tokens_used'],
            'cost' => $result['cost'],
            'results' => [
                'final_output' => $result['result'],
                'is_mock' => false,
                'execution_summary' => [
                    'crew_name' => $this->execution->crew->name,
                    'process_type' => $this->execution->crew->process_type,
                    'agents_count' => $this->execution->crew->agents->count(),
                    'tasks_count' => $this->execution->crew->tasks->count(),
                    'total_tokens' => $result['tokens_used'],
                    'cost' => $result['cost'],
                ]
            ]
        ]);

        // Update tenant token usage
        $this->execution->tenant->increment('tokens_used_current', $result['tokens_used']);

        // Update crew statistics
        $crew = $this->execution->crew;
        $executionTime = now()->diffInSeconds($this->execution->started_at);

        $crew->increment('total_executions');
        $crew->increment('successful_executions');
        $crew->update([
            'last_execution_at' => now(),
            'average_execution_time' => (($crew->average_execution_time * ($crew->total_executions - 1)) + $executionTime) / $crew->total_executions,
        ]);

        Log::info('ExecuteCrewJob: Execution completed successfully', [
            'execution_id' => $this->execution->id,
            'tokens_used' => $result['tokens_used'],
            'cost' => $result['cost']
        ]);
    }

    /**
     * Handle execution failure
     */
    protected function handleFailure(\Exception $e): void
    {
        $this->createLog('error', '❌ Execution failed: ' . $e->getMessage());

        $this->execution->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $e->getMessage(),
            'progress' => 0,
        ]);

        // Update crew statistics
        $this->execution->crew->increment('total_executions');
        $this->execution->crew->increment('failed_executions');
    }

    /**
     * Create execution log entry
     */
    protected function createLog(string $level, string $message, array $data = []): void
    {
        CrewExecutionLog::create([
            'crew_execution_id' => $this->execution->id,
            'level' => $level,
            'message' => $message,
            'data' => $data,
            'tokens_used' => $data['tokens_used'] ?? 0,
        ]);
    }

    /**
     * Update progress based on log content
     */
    protected function updateProgressFromLog(array $log): void
    {
        $message = strtolower($log['message']);

        // Simple progress estimation based on log messages
        if (str_contains($message, 'creating agent') || str_contains($message, 'agent:')) {
            $this->execution->update(['progress' => 20]);
        } elseif (str_contains($message, 'creating task') || str_contains($message, 'task:')) {
            $this->execution->update(['progress' => 40]);
        } elseif (str_contains($message, 'crew configured')) {
            $this->execution->update(['progress' => 50]);
        } elseif (str_contains($message, 'executing')) {
            $this->execution->update(['progress' => 60]);
        } elseif (str_contains($message, 'completed')) {
            $this->execution->update(['progress' => 90]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('ExecuteCrewJob: Job failed permanently', [
            'execution_id' => $this->execution->id,
            'error' => $exception->getMessage()
        ]);

        $this->handleFailure($exception);
    }
}
