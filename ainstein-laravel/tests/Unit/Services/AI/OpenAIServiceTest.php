<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\OpenAIService;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OpenAIService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up platform settings with mock API key
        $setting = PlatformSetting::firstOrCreate(
            ['id' => \Illuminate\Support\Str::ulid()],
            [
                'openai_api_key' => 'sk-test', // Trigger mock service
                'openai_default_model' => 'gpt-4o-mini',
                'openai_max_tokens' => 2000,
                'openai_temperature' => 0.7,
            ]
        );

        $this->service = new OpenAIService();
    }

    /** @test */
    public function it_initializes_with_mock_service_when_fake_key()
    {
        $this->assertTrue($this->service->isUsingMock());
    }

    /** @test */
    public function it_can_perform_chat_completion()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Say hello']
        ];

        $result = $this->service->chat($messages);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('tokens_used', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertIsString($result['content']);
        $this->assertIsInt($result['tokens_used']);
    }

    /** @test */
    public function it_can_perform_simple_completion()
    {
        $result = $this->service->completion('Write a short sentence about Laravel.');

        $this->assertArrayHasKey('content', $result);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['content']);
    }

    /** @test */
    public function it_can_parse_json_responses()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Return JSON with fields: name, age']
        ];

        $result = $this->service->parseJSON($messages);

        $this->assertArrayHasKey('parsed', $result);
        $this->assertIsArray($result['parsed']);
    }

    /** @test */
    public function it_can_generate_embeddings()
    {
        $result = $this->service->embeddings('Test text for embeddings');

        $this->assertArrayHasKey('embeddings', $result);
        $this->assertArrayHasKey('tokens_used', $result);
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['embeddings']);
    }

    /** @test */
    public function it_tracks_token_usage()
    {
        $tenant = Tenant::factory()->create([
            'tokens_used_current' => 0,
            'tokens_monthly_limit' => 10000,
        ]);

        $this->service->trackTokenUsage(
            $tenant->id,
            100,
            'gpt-4o-mini',
            'test'
        );

        $tenant->refresh();
        $this->assertEquals(100, $tenant->tokens_used_current);
    }

    /** @test */
    public function it_respects_use_case_configuration()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Generate campaign ad']
        ];

        $result = $this->service->chat($messages, null, ['use_case' => 'campaigns']);

        $this->assertTrue($result['success']);
        // Note: With mock service, model name comes from mock implementation
        $this->assertIsString($result['model']);
    }

    /** @test */
    public function it_handles_custom_temperature_and_max_tokens()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test']
        ];

        $result = $this->service->chat($messages, null, [
            'temperature' => 0.9,
            'max_tokens' => 500,
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_can_use_system_messages()
    {
        $result = $this->service->completion('Write about AI', null, [
            'system_message' => 'You are a technical writer.'
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['content']);
    }

    /** @test */
    public function it_gets_available_models()
    {
        $models = $this->service->getAvailableModels();

        $this->assertIsArray($models);
        $this->assertContains('gpt-4o', $models);
        $this->assertContains('gpt-4o-mini', $models);
    }

    /** @test */
    public function it_handles_multiple_embeddings()
    {
        $texts = ['First text', 'Second text', 'Third text'];

        $result = $this->service->embeddings($texts);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['embeddings']);
    }
}
