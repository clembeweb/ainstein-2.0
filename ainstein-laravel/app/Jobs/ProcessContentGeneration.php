<?php

namespace App\Jobs;

use App\Models\ContentGeneration;
use App\Models\Page;
use App\Models\Prompt;
use App\Services\OpenAiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessContentGeneration implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    protected $contentGeneration;

    /**
     * Create a new job instance.
     */
    public function __construct(ContentGeneration $contentGeneration)
    {
        $this->contentGeneration = $contentGeneration;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAiService $openAiService): void
    {
        try {
            Log::info("Processing content generation ID: {$this->contentGeneration->id}");

            // Update status to processing
            $this->contentGeneration->update(['status' => 'processing']);

            // Load relationships
            $this->contentGeneration->load(['page', 'prompt', 'tenant']);

            $page = $this->contentGeneration->page;
            $prompt = $this->contentGeneration->prompt;
            $tenant = $this->contentGeneration->tenant;

            // Prepare the prompt with variables
            $finalPrompt = $this->replacePlaceholders(
                $this->contentGeneration->prompt_template,
                $this->contentGeneration->variables ?? []
            );

            // Add page context
            $finalPrompt = $this->addPageContext($finalPrompt, $page);

            // Add additional instructions if provided
            if ($this->contentGeneration->additional_instructions) {
                $finalPrompt .= "\n\nAdditional instructions: " . $this->contentGeneration->additional_instructions;
            }

            Log::info('Sending request to OpenAI', [
                'generation_id' => $this->contentGeneration->id,
                'prompt_length' => strlen($finalPrompt)
            ]);

            // Generate content using OpenAI (simplified)
            $generatedContent = $openAiService->generateSimpleContent($finalPrompt);

            // Count tokens used (approximate)
            $tokensUsed = $this->estimateTokens($finalPrompt . $generatedContent);

            // Update the content generation with results
            $this->contentGeneration->update([
                'status' => 'completed',
                'generated_content' => $generatedContent,
                'tokens_used' => $tokensUsed,
                'completed_at' => now()
            ]);

            // Update tenant token usage
            if ($tenant) {
                $tenant->increment('tokens_used_current', $tokensUsed);
            }

            Log::info('Content generation completed successfully', [
                'generation_id' => $this->contentGeneration->id,
                'tokens_used' => $tokensUsed,
                'content_length' => strlen($generatedContent)
            ]);

        } catch (Exception $e) {
            Log::error('Content generation failed', [
                'generation_id' => $this->contentGeneration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update status to failed
            $this->contentGeneration->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);

            // Re-throw the exception to trigger retry
            throw $e;
        }
    }

    private function replacePlaceholders(string $template, array $variables): string
    {
        $content = $template;

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }

    private function addPageContext(string $prompt, $page): string
    {
        $context = "\n\nPage Context:\n";
        $context .= "URL Path: " . $page->url_path . "\n";
        $context .= "Target Keyword: " . $page->keyword . "\n";

        if ($page->meta_title) {
            $context .= "Meta Title: " . $page->meta_title . "\n";
        }

        if ($page->meta_description) {
            $context .= "Meta Description: " . $page->meta_description . "\n";
        }

        if ($page->content_brief) {
            $context .= "Content Brief: " . $page->content_brief . "\n";
        }

        return $prompt . $context;
    }

    private function estimateTokens(string $text): int
    {
        // Rough estimate: 1 token ≈ 4 characters for English text
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Content generation job permanently failed for ID: {$this->contentGeneration->id}", [
            'error' => $exception->getMessage()
        ]);

        $this->contentGeneration->update([
            'status' => 'failed',
            'error' => 'Job failed after maximum retry attempts: ' . $exception->getMessage()
        ]);
    }
}
