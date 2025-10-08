<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenAiService;
use Exception;

class TestOpenAI extends Command
{
    protected $signature = 'test:openai';
    protected $description = 'Test OpenAI integration';

    public function handle()
    {
        $this->info('🤖 Testing OpenAI Integration...');

        try {
            $openAiService = new OpenAiService();

            $this->info('✅ OpenAI Service initialized successfully');

            // Test simple content generation
            $this->info('🔄 Testing simple content generation...');
            $content = $openAiService->generateSimpleContent('Write a short paragraph about AI in content creation');

            $this->info('✅ Content generated successfully:');
            $this->line($content);

            // Test full content generation
            $this->info('🔄 Testing full content generation...');
            $result = $openAiService->generateContent(
                'Write a blog article about {{keyword}} in {{language}}',
                ['keyword' => 'AI Content Creation', 'language' => 'Italian']
            );

            $this->info('✅ Full content generation successful:');
            $this->line('Content: ' . substr($result['content'], 0, 200) . '...');
            $this->line('Meta Title: ' . $result['meta_title']);
            $this->line('Meta Description: ' . $result['meta_description']);
            $this->line('Tokens Used: ' . $result['tokens_used']);

            $this->info('🎉 All OpenAI tests passed successfully!');

        } catch (Exception $e) {
            $this->error('❌ OpenAI test failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}