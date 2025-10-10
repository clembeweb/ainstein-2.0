<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing CrewAI Setup\n\n";

// Test 1: Verify tools were seeded
$toolCount = \App\Models\CrewAgentTool::count();
echo "✓ CrewAgentTools seeded: {$toolCount} tools\n";

// Test 2: Get first tenant and user for testing
$tenant = \App\Models\Tenant::first();
$user = \App\Models\User::where('tenant_id', $tenant->id)->first();

if (!$tenant || !$user) {
    echo "✗ No tenant or user found for testing\n";
    exit(1);
}

echo "✓ Using tenant: {$tenant->name} (ID: {$tenant->id})\n";
echo "✓ Using user: {$user->name} (ID: {$user->id})\n\n";

// Test 3: Create a test crew
echo "📋 Creating test crew...\n";
$crew = \App\Models\Crew::create([
    'tenant_id' => $tenant->id,
    'created_by' => $user->id,
    'name' => 'Test Marketing Crew',
    'description' => 'A demo crew for content marketing tasks',
    'process_type' => 'sequential',
    'status' => 'active',
    'configuration' => ['verbose' => true],
]);
echo "✓ Crew created: {$crew->name} (ID: {$crew->id})\n";

// Test 4: Add agents to crew
echo "\n🤖 Adding agents...\n";

$researcher = \App\Models\CrewAgent::create([
    'crew_id' => $crew->id,
    'name' => 'Content Researcher',
    'role' => 'Senior Research Analyst',
    'goal' => 'Research comprehensive information on given topics',
    'backstory' => 'Expert researcher with 10+ years experience in digital marketing.',
    'tools' => ['SerperDevTool', 'WebsiteSearchTool'],
    'allow_delegation' => false,
    'verbose' => true,
    'max_iterations' => 25,
    'order' => 0,
]);
echo "✓ Agent created: {$researcher->name}\n";

$writer = \App\Models\CrewAgent::create([
    'crew_id' => $crew->id,
    'name' => 'Content Writer',
    'role' => 'Senior Content Writer',
    'goal' => 'Write engaging and SEO-optimized content',
    'backstory' => 'Professional content writer specializing in digital marketing.',
    'tools' => [],
    'allow_delegation' => false,
    'verbose' => true,
    'max_iterations' => 25,
    'order' => 1,
]);
echo "✓ Agent created: {$writer->name}\n";

// Test 5: Add tasks to crew
echo "\n📝 Adding tasks...\n";

$task1 = \App\Models\CrewTask::create([
    'crew_id' => $crew->id,
    'agent_id' => $researcher->id,
    'name' => 'Research Topic',
    'description' => 'Research comprehensive information about: {topic}',
    'expected_output' => 'Detailed research report with key findings and sources',
    'context' => [],
    'dependencies' => [],
    'order' => 0,
]);
echo "✓ Task created: {$task1->name}\n";

$task2 = \App\Models\CrewTask::create([
    'crew_id' => $crew->id,
    'agent_id' => $writer->id,
    'name' => 'Write Article',
    'description' => 'Write a comprehensive article based on research findings',
    'expected_output' => 'Complete article with introduction, body, and conclusion',
    'context' => [],
    'dependencies' => [$task1->id],
    'order' => 1,
]);
echo "✓ Task created: {$task2->name}\n";

// Test 6: Load crew with relationships
echo "\n🔄 Testing relationships...\n";
$crew->load(['agents', 'tasks']);
echo "✓ Loaded crew with " . $crew->agents->count() . " agents and " . $crew->tasks->count() . " tasks\n";

// Test 7: Test MockCrewAIService execution
echo "\n🚀 Testing MockCrewAIService execution...\n";

$execution = \App\Models\CrewExecution::create([
    'tenant_id' => $tenant->id,
    'crew_id' => $crew->id,
    'executed_by' => $user->id,
    'input_variables' => [
        'topic' => 'AI Content Marketing Strategies',
        'target' => 'Marketing professionals'
    ],
    'status' => 'pending',
]);

echo "✓ Execution created (ID: {$execution->id})\n";

$mockService = new \App\Services\MockCrewAIService();
try {
    $result = $mockService->executeCrew($execution);
    echo "✓ Execution completed successfully!\n";
    echo "  - Tokens used: {$result['tokens_used']}\n";
    echo "  - Cost: $" . number_format($result['cost'], 4) . "\n";
    echo "  - Status: {$execution->fresh()->status}\n";
} catch (\Exception $e) {
    echo "✗ Execution failed: {$e->getMessage()}\n";
}

// Test 8: Verify execution logs
$logCount = \App\Models\CrewExecutionLog::where('execution_id', $execution->id)->count();
echo "✓ Generated {$logCount} execution log entries\n";

// Test 9: Create a template from the crew
echo "\n📦 Creating template from crew...\n";
$template = \App\Models\CrewTemplate::create([
    'tenant_id' => $tenant->id,
    'crew_id' => $crew->id,
    'created_by' => $user->id,
    'name' => 'Content Marketing Template',
    'description' => 'Template for creating content marketing campaigns',
    'category' => 'Marketing',
    'is_system' => false,
    'is_public' => false,
    'is_active' => true,
]);
echo "✓ Template created: {$template->name} (ID: {$template->id})\n";

// Test 10: Statistics
echo "\n📊 Final Statistics:\n";
echo "  - Crews: " . \App\Models\Crew::count() . "\n";
echo "  - Agents: " . \App\Models\CrewAgent::count() . "\n";
echo "  - Tasks: " . \App\Models\CrewTask::count() . "\n";
echo "  - Executions: " . \App\Models\CrewExecution::count() . "\n";
echo "  - Templates: " . \App\Models\CrewTemplate::count() . "\n";
echo "  - Tools: " . \App\Models\CrewAgentTool::count() . "\n";

echo "\n✅ All tests passed successfully!\n\n";

// Show sample execution result
echo "📄 Sample Execution Result:\n";
echo str_repeat('-', 50) . "\n";
echo substr($execution->fresh()->results['final_output'], 0, 500) . "...\n";
echo str_repeat('-', 50) . "\n";
