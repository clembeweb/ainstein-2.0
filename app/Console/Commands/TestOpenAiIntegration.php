<?php

namespace App\Console\Commands;

use App\Services\OpenAiService;
use Illuminate\Console\Command;

class TestOpenAiIntegration extends Command
{
    protected $signature = 'test:openai {--mock : Use mock data instead of real API}';
    protected $description = 'Test OpenAI integration with mock or real API calls';

    public function handle()
    {
        $this->info('🤖 Testing OpenAI Integration...');

        if ($this->option('mock')) {
            $this->testWithMockData();
        } else {
            $this->testWithRealApi();
        }
    }

    private function testWithMockData()
    {
        $this->info('📝 Testing with mock data...');

        $mockPrompt = "Scrivi un articolo SEO su: marketing digitale";
        $mockResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Il marketing digitale rappresenta una delle strategie più efficaci per promuovere il proprio business online. Attraverso l\'utilizzo di strumenti come la SEO, i social media e l\'advertising digitale, le aziende possono raggiungere il proprio target di riferimento e incrementare le vendite.'
                    ]
                ]
            ],
            'usage' => [
                'total_tokens' => 150
            ]
        ];

        $this->line("📤 Prompt: {$mockPrompt}");
        $this->line("📥 Response: " . substr($mockResponse['choices'][0]['message']['content'], 0, 100) . "...");
        $this->line("🔢 Tokens used: {$mockResponse['usage']['total_tokens']}");

        $this->info('✅ Mock OpenAI integration test completed successfully!');
    }

    private function testWithRealApi()
    {
        $openaiKey = config('services.openai.api_key');

        if (!$openaiKey || $openaiKey === 'your_openai_api_key_here') {
            $this->error('❌ OpenAI API key not configured.');
            $this->info('💡 Set OPENAI_API_KEY in your .env file or use --mock flag for testing.');
            return;
        }

        try {
            $service = new OpenAiService();

            $prompt = "Write a short SEO-optimized paragraph about digital marketing in Italian.";
            $this->info("📤 Testing with prompt: {$prompt}");

            $response = $service->generateContent($prompt, [
                'max_tokens' => 100,
                'temperature' => 0.7
            ]);

            $this->line("📥 Response: " . substr($response['content'], 0, 200) . "...");
            $this->line("🔢 Tokens used: {$response['tokens_used']}");
            $this->line("🧠 Model: {$response['model']}");

            $this->info('✅ Real OpenAI integration test completed successfully!');

        } catch (\Exception $e) {
            $this->error("❌ OpenAI API test failed: " . $e->getMessage());
            $this->info('💡 Try using --mock flag for testing without API key.');
        }
    }
}