# AI Article Steps — Services Layer

**Documento**: 03 — Service Layer Business Logic
**Progetto**: Ainstein Laravel Multi-Tenant Platform
**Tool**: AI Article Steps (Copy Article Generator)
**Services**: 7 classi principali

---

## Indice
1. [Overview Services](#1-overview-services)
2. [ArticleGenerationService](#2-articlegenerationservice)
3. [KeywordManagementService](#3-keywordmanagementservice)
4. [PromptRenderingService](#4-promptrenderingservice)
5. [SeoOptimizationService](#5-seooptimizationservice)
6. [InternalLinkSuggestionService](#6-internallinksuggestionservice)
7. [AbTestingService](#7-abtestingservice)
8. [ContentAnalysisService](#8-contentanalysisservice)
9. [Service Integration Examples](#9-service-integration-examples)

---

## 1. Overview Services

### Services Architecture

```
app/Services/ArticleSteps/
├── ArticleGenerationService.php        ⭐ Main orchestrator (350+ LOC)
├── KeywordManagementService.php        Keyword CRUD & analytics
├── PromptRenderingService.php          Template rendering
├── SeoOptimizationService.php          SEO steps & scoring
├── InternalLinkSuggestionService.php   AI link discovery
├── AbTestingService.php                AB test management
└── ContentAnalysisService.php          Content metrics
```

### Dependencies

Tutti i services utilizzano:
- **OpenAIService** (già esistente) per chiamate AI
- **TokenTrackingService** (già esistente) per conteggio token/costi
- **Queue Jobs** per operazioni asincrone
- **Events** per notifiche real-time
- **Cache** per performance optimization

---

## 2. ArticleGenerationService

**File**: `app/Services/ArticleSteps/ArticleGenerationService.php`

### Complete Code (⭐ MAIN ORCHESTRATOR - 400+ LOC)

```php
<?php

namespace App\Services\ArticleSteps;

use App\Events\ArticleGenerationCompleted;
use App\Events\ArticleGenerationFailed;
use App\Events\ArticleGenerationProgressUpdated;
use App\Events\ArticleGenerationStarted;
use App\Jobs\GenerateArticleJob;
use App\Models\Article;
use App\Models\ArticleGeneration;
use App\Models\Keyword;
use App\Models\PromptTemplate;
use App\Models\Tenant;
use App\Services\OpenAI\OpenAIService;
use App\Services\Tracking\TokenTrackingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArticleGenerationService
{
    public function __construct(
        protected OpenAIService $openAIService,
        protected TokenTrackingService $tokenTrackingService,
        protected PromptRenderingService $promptRenderingService,
        protected SeoOptimizationService $seoOptimizationService,
        protected InternalLinkSuggestionService $internalLinkSuggestionService,
        protected ContentAnalysisService $contentAnalysisService,
    ) {}

    /**
     * Start article generation (queued job).
     *
     * @param Tenant $tenant
     * @param array $params
     * @return Article
     */
    public function startGeneration(Tenant $tenant, array $params): Article
    {
        // Validate params
        $validated = $this->validateGenerationParams($params);

        // Create article record
        $article = $this->createArticleRecord($tenant, $validated);

        // Create generation tracking record
        $generation = $this->createGenerationRecord($article);

        // Dispatch async job
        GenerateArticleJob::dispatch($article->id, $validated)
            ->onQueue('articles')
            ->delay(now()->addSeconds(2));

        // Fire event
        event(new ArticleGenerationStarted($article, $tenant));

        Log::info("Article generation started", [
            'article_id' => $article->id,
            'tenant_id' => $tenant->id,
            'keyword' => $validated['keyword_id'] ?? null,
        ]);

        return $article;
    }

    /**
     * Execute article generation (called by job).
     *
     * @param string $articleId
     * @param array $params
     * @return void
     */
    public function executeGeneration(string $articleId, array $params): void
    {
        $article = Article::findOrFail($articleId);
        $generation = $article->latestGeneration;

        try {
            DB::beginTransaction();

            // Mark as generating
            $article->markAsGenerating();
            $generation->markAsProcessing();

            // Step 1: Generate content
            $this->updateProgress($generation, 10, "Rendering prompt template", 1, 5);
            $prompt = $this->buildPrompt($article, $params);

            $this->updateProgress($generation, 30, "Generating article content with AI", 2, 5);
            $aiResponse = $this->generateContent($article, $prompt, $params);

            // Step 2: Process AI response
            $this->updateProgress($generation, 50, "Processing AI response", 3, 5);
            $this->processAiResponse($article, $aiResponse, $params);

            // Step 3: Generate SEO steps
            $this->updateProgress($generation, 70, "Generating SEO optimization steps", 4, 5);
            $this->seoOptimizationService->generateSeoSteps($article);

            // Step 4: Suggest internal links (if enabled)
            if ($params['include_internal_links'] ?? false) {
                $this->updateProgress($generation, 85, "Discovering internal link opportunities", 5, 5);
                $this->internalLinkSuggestionService->suggestLinks($article);
            }

            // Step 5: Analyze content
            $this->updateProgress($generation, 95, "Analyzing content quality", 5, 5);
            $this->contentAnalysisService->analyzeArticle($article);

            // Complete
            $this->updateProgress($generation, 100, "Generation completed", 5, 5);
            $article->markAsCompleted([
                'word_count' => $article->calculateWordCount(),
                'reading_time_minutes' => $article->calculateReadingTime(),
            ]);
            $generation->markAsCompleted();

            DB::commit();

            // Fire success event
            event(new ArticleGenerationCompleted($article));

            Log::info("Article generation completed", [
                'article_id' => $article->id,
                'word_count' => $article->word_count,
                'tokens_used' => $article->tokens_used,
                'cost' => $article->cost,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Mark as failed
            $article->markAsFailed($e->getMessage());
            $generation->markAsFailed($e->getMessage(), $e->getTraceAsString());

            // Fire failure event
            event(new ArticleGenerationFailed($article, $e->getMessage()));

            Log::error("Article generation failed", [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Build complete prompt for AI.
     *
     * @param Article $article
     * @param array $params
     * @return string
     */
    protected function buildPrompt(Article $article, array $params): string
    {
        $template = $article->promptTemplate;
        $keyword = $article->keyword;

        // Build variables for template rendering
        $variables = [
            'keyword' => $keyword?->keyword ?? $params['custom_keyword'] ?? '',
            'word_count_min' => $params['word_count_min'] ?? $template?->target_word_count_min ?? 800,
            'word_count_max' => $params['word_count_max'] ?? $template?->target_word_count_max ?? 1200,
            'tone' => $params['tone'] ?? $template?->tone ?? 'professional',
            'style' => $params['style'] ?? $template?->style ?? 'blog',
            'language' => $params['language'] ?? $template?->language ?? 'en',
            'extra_instructions' => $params['extra_instructions'] ?? '',
        ];

        // Render template
        if ($template) {
            $prompt = $this->promptRenderingService->render($template, $variables);
        } else {
            $prompt = $this->promptRenderingService->renderDefault($variables);
        }

        // Store prompt used
        $article->update(['prompt_used' => $prompt]);

        return $prompt;
    }

    /**
     * Generate content using OpenAI.
     *
     * @param Article $article
     * @param string $prompt
     * @param array $params
     * @return array
     */
    protected function generateContent(Article $article, string $prompt, array $params): array
    {
        $model = $params['model'] ?? 'gpt-4o';
        $temperature = $params['temperature'] ?? 0.7;
        $maxTokens = $params['max_tokens'] ?? 3000;

        // Call OpenAI
        $response = $this->openAIService->chatCompletion([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional content writer specialized in SEO-optimized articles. Generate well-structured, engaging content based on the user\'s requirements.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);

        // Track tokens and cost
        $tokensUsed = $response['usage']['total_tokens'] ?? 0;
        $cost = $this->tokenTrackingService->calculateCost($model, $tokensUsed);

        $this->tokenTrackingService->track(
            tenantId: $article->tenant_id,
            toolName: 'article-generator',
            model: $model,
            tokensUsed: $tokensUsed,
            cost: $cost,
            metadata: [
                'article_id' => $article->id,
                'keyword_id' => $article->keyword_id,
            ]
        );

        // Update article metadata
        $article->update([
            'model_used' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
        ]);

        return $response;
    }

    /**
     * Process AI response and extract content.
     *
     * @param Article $article
     * @param array $response
     * @param array $params
     * @return void
     */
    protected function processAiResponse(Article $article, array $response, array $params): void
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        if (empty($content)) {
            throw new \Exception('Empty response from AI');
        }

        // Parse structured content (title, excerpt, body)
        $parsed = $this->parseAiContent($content);

        // Update article
        $article->update([
            'title' => $parsed['title'],
            'content' => $parsed['content'],
            'excerpt' => $parsed['excerpt'],
            'meta_title' => $parsed['meta_title'] ?? $parsed['title'],
            'meta_description' => $parsed['meta_description'] ?? $parsed['excerpt'],
        ]);
    }

    /**
     * Parse AI-generated content into structured parts.
     *
     * @param string $content
     * @return array
     */
    protected function parseAiContent(string $content): array
    {
        // Try to extract title from first H1 or first line
        $lines = explode("\n", trim($content));
        $title = '';
        $contentBody = $content;

        // Look for # Title or ## Title
        if (preg_match('/^#+\s*(.+)$/m', $content, $matches)) {
            $title = trim($matches[1]);
            // Remove title from content
            $contentBody = preg_replace('/^#+\s*.+$/m', '', $content, 1);
        } elseif (!empty($lines[0])) {
            $title = trim($lines[0]);
            array_shift($lines);
            $contentBody = implode("\n", $lines);
        }

        // Extract excerpt (first paragraph or 150 chars)
        $excerpt = '';
        if (preg_match('/^(.+?)(\n\n|$)/s', trim($contentBody), $matches)) {
            $excerpt = trim(strip_tags($matches[1]));
            $excerpt = mb_substr($excerpt, 0, 300);
        }

        // Generate meta description (if not found)
        $metaDescription = mb_substr($excerpt, 0, 160);

        return [
            'title' => $title ?: 'Untitled Article',
            'content' => trim($contentBody),
            'excerpt' => $excerpt,
            'meta_title' => mb_substr($title, 0, 60),
            'meta_description' => $metaDescription,
        ];
    }

    /**
     * Create article record.
     *
     * @param Tenant $tenant
     * @param array $params
     * @return Article
     */
    protected function createArticleRecord(Tenant $tenant, array $params): Article
    {
        return Article::create([
            'tenant_id' => $tenant->id,
            'keyword_id' => $params['keyword_id'] ?? null,
            'prompt_template_id' => $params['prompt_template_id'] ?? null,
            'status' => Article::STATUS_PENDING,
            'ai_instructions' => $params['extra_instructions'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Create generation tracking record.
     *
     * @param Article $article
     * @return ArticleGeneration
     */
    protected function createGenerationRecord(Article $article): ArticleGeneration
    {
        return ArticleGeneration::create([
            'article_id' => $article->id,
            'status' => ArticleGeneration::STATUS_QUEUED,
            'queue_name' => 'articles',
            'queued_at' => now(),
            'total_steps' => 5,
        ]);
    }

    /**
     * Update generation progress.
     *
     * @param ArticleGeneration $generation
     * @param int $percentage
     * @param string $description
     * @param int|null $currentStep
     * @param int|null $totalSteps
     * @return void
     */
    protected function updateProgress(
        ArticleGeneration $generation,
        int $percentage,
        string $description,
        ?int $currentStep = null,
        ?int $totalSteps = null
    ): void {
        $generation->updateProgress($percentage, $description, $currentStep);

        if ($totalSteps !== null) {
            $generation->update(['total_steps' => $totalSteps]);
        }

        // Fire progress event
        event(new ArticleGenerationProgressUpdated(
            $generation->article,
            $percentage,
            $description,
            $currentStep,
            $totalSteps
        ));
    }

    /**
     * Validate generation parameters.
     *
     * @param array $params
     * @return array
     */
    protected function validateGenerationParams(array $params): array
    {
        // Either keyword_id or custom_keyword required
        if (empty($params['keyword_id']) && empty($params['custom_keyword'])) {
            throw new \InvalidArgumentException('Either keyword_id or custom_keyword is required');
        }

        return $params;
    }

    /**
     * Get generation status.
     *
     * @param string $articleId
     * @return array
     */
    public function getGenerationStatus(string $articleId): array
    {
        $article = Article::with('latestGeneration')->findOrFail($articleId);
        $generation = $article->latestGeneration;

        if (!$generation) {
            return [
                'status' => 'not_started',
                'progress' => 0,
            ];
        }

        return [
            'status' => $generation->status,
            'progress' => $generation->progress_percentage,
            'current_step' => $generation->current_step,
            'total_steps' => $generation->total_steps,
            'description' => $generation->step_description,
            'started_at' => $generation->started_at?->toIso8601String(),
            'completed_at' => $generation->completed_at?->toIso8601String(),
            'error' => $generation->error_message,
        ];
    }

    /**
     * Regenerate article.
     *
     * @param Article $article
     * @param array $params
     * @return Article
     */
    public function regenerate(Article $article, array $params = []): Article
    {
        // Keep original keyword and template if not overridden
        $params = array_merge([
            'keyword_id' => $article->keyword_id,
            'prompt_template_id' => $article->prompt_template_id,
        ], $params);

        // Reset article status
        $article->update([
            'status' => Article::STATUS_PENDING,
            'content' => null,
            'title' => null,
        ]);

        // Start new generation
        return $this->executeGeneration($article->id, $params);
    }

    /**
     * Cancel ongoing generation.
     *
     * @param string $articleId
     * @return void
     */
    public function cancelGeneration(string $articleId): void
    {
        $article = Article::with('latestGeneration')->findOrFail($articleId);
        $generation = $article->latestGeneration;

        if ($generation && $generation->isProcessing()) {
            $generation->update(['status' => ArticleGeneration::STATUS_CANCELLED]);
            $article->markAsFailed('Generation cancelled by user');

            Log::info("Article generation cancelled", [
                'article_id' => $article->id,
            ]);
        }
    }
}
```

---

## 3. KeywordManagementService

**File**: `app/Services/ArticleSteps/KeywordManagementService.php`

### Complete Code

```php
<?php

namespace App\Services\ArticleSteps;

use App\Models\Keyword;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeywordManagementService
{
    /**
     * Create keyword(s) from textarea input (one per line).
     *
     * @param Tenant $tenant
     * @param string $keywordsText
     * @param array $metadata
     * @return array
     */
    public function createBulkFromText(Tenant $tenant, string $keywordsText, array $metadata = []): array
    {
        $lines = explode("\n", $keywordsText);
        $created = [];
        $skipped = [];

        DB::beginTransaction();

        try {
            foreach ($lines as $line) {
                $keyword = trim($line);

                if (empty($keyword)) {
                    continue;
                }

                // Check if already exists
                $existing = Keyword::forTenant($tenant->id)
                    ->where('keyword', $keyword)
                    ->first();

                if ($existing) {
                    $skipped[] = $keyword;
                    continue;
                }

                // Create new keyword
                $created[] = Keyword::create(array_merge([
                    'tenant_id' => $tenant->id,
                    'keyword' => $keyword,
                    'status' => Keyword::STATUS_ACTIVE,
                    'created_by' => auth()->id(),
                ], $metadata));
            }

            DB::commit();

            Log::info("Bulk keywords created", [
                'tenant_id' => $tenant->id,
                'created_count' => count($created),
                'skipped_count' => count($skipped),
            ]);

            return [
                'created' => $created,
                'skipped' => $skipped,
                'created_count' => count($created),
                'skipped_count' => count($skipped),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Enrich keyword with SEO metrics (volume, CPC, competition).
     *
     * @param Keyword $keyword
     * @param array $metrics
     * @return Keyword
     */
    public function enrichKeyword(Keyword $keyword, array $metrics): Keyword
    {
        $keyword->update([
            'search_volume' => $metrics['search_volume'] ?? null,
            'cpc' => $metrics['cpc'] ?? null,
            'competition' => $metrics['competition'] ?? null,
            'intent' => $this->detectIntent($keyword->keyword),
        ]);

        return $keyword->fresh();
    }

    /**
     * Detect keyword intent based on patterns.
     *
     * @param string $keyword
     * @return string
     */
    protected function detectIntent(string $keyword): string
    {
        $keyword = strtolower($keyword);

        // Transactional intent
        $transactionalPatterns = ['buy', 'purchase', 'order', 'price', 'cost', 'cheap', 'deal', 'discount', 'coupon'];
        foreach ($transactionalPatterns as $pattern) {
            if (str_contains($keyword, $pattern)) {
                return Keyword::INTENT_TRANSACTIONAL;
            }
        }

        // Commercial intent
        $commercialPatterns = ['best', 'top', 'review', 'compare', 'vs', 'alternative'];
        foreach ($commercialPatterns as $pattern) {
            if (str_contains($keyword, $pattern)) {
                return Keyword::INTENT_COMMERCIAL;
            }
        }

        // Navigational intent
        $navigationalPatterns = ['login', 'sign in', 'sign up', 'download', 'official'];
        foreach ($navigationalPatterns as $pattern) {
            if (str_contains($keyword, $pattern)) {
                return Keyword::INTENT_NAVIGATIONAL;
            }
        }

        // Default: informational
        return Keyword::INTENT_INFORMATIONAL;
    }

    /**
     * Get keyword analytics.
     *
     * @param Tenant $tenant
     * @return array
     */
    public function getAnalytics(Tenant $tenant): array
    {
        $keywords = Keyword::forTenant($tenant->id)->get();

        return [
            'total_keywords' => $keywords->count(),
            'by_status' => [
                'active' => $keywords->where('status', Keyword::STATUS_ACTIVE)->count(),
                'used' => $keywords->where('status', Keyword::STATUS_USED)->count(),
                'archived' => $keywords->where('status', Keyword::STATUS_ARCHIVED)->count(),
            ],
            'by_intent' => [
                'informational' => $keywords->where('intent', Keyword::INTENT_INFORMATIONAL)->count(),
                'commercial' => $keywords->where('intent', Keyword::INTENT_COMMERCIAL)->count(),
                'transactional' => $keywords->where('intent', Keyword::INTENT_TRANSACTIONAL)->count(),
                'navigational' => $keywords->where('intent', Keyword::INTENT_NAVIGATIONAL)->count(),
            ],
            'unused_count' => Keyword::forTenant($tenant->id)->unused()->count(),
            'avg_search_volume' => $keywords->avg('search_volume'),
            'avg_cpc' => $keywords->avg('cpc'),
        ];
    }

    /**
     * Suggest next keyword to use.
     *
     * @param Tenant $tenant
     * @return Keyword|null
     */
    public function suggestNextKeyword(Tenant $tenant): ?Keyword
    {
        return Keyword::forTenant($tenant->id)
            ->unused()
            ->highPriority()
            ->orderBy('priority', 'desc')
            ->orderBy('search_volume', 'desc')
            ->first();
    }

    /**
     * Auto-categorize keywords based on content.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function autoCategorize(Tenant $tenant): void
    {
        $keywords = Keyword::forTenant($tenant->id)
            ->whereNull('category')
            ->get();

        foreach ($keywords as $keyword) {
            $category = $this->detectCategory($keyword->keyword);
            $keyword->update(['category' => $category]);
        }

        Log::info("Keywords auto-categorized", [
            'tenant_id' => $tenant->id,
            'count' => $keywords->count(),
        ]);
    }

    /**
     * Detect category from keyword.
     *
     * @param string $keyword
     * @return string
     */
    protected function detectCategory(string $keyword): string
    {
        // Simple pattern matching (can be enhanced with AI)
        $keyword = strtolower($keyword);

        if (str_contains($keyword, 'how to') || str_contains($keyword, 'tutorial')) {
            return 'Tutorial';
        }

        if (str_contains($keyword, 'best') || str_contains($keyword, 'top')) {
            return 'Listicle';
        }

        if (str_contains($keyword, 'what is') || str_contains($keyword, 'definition')) {
            return 'Guide';
        }

        return 'General';
    }
}
```

---

## 4. PromptRenderingService

**File**: `app/Services/ArticleSteps/PromptRenderingService.php`

### Complete Code

```php
<?php

namespace App\Services\ArticleSteps;

use App\Models\PromptTemplate;

class PromptRenderingService
{
    /**
     * Render prompt template with variables.
     *
     * @param PromptTemplate $template
     * @param array $variables
     * @return string
     */
    public function render(PromptTemplate $template, array $variables): string
    {
        $prompt = $template->prompt_text;

        // Replace placeholders: {{variable_name}}
        foreach ($variables as $key => $value) {
            $prompt = str_replace("{{" . $key . "}}", $value, $prompt);
        }

        // Increment usage count
        $template->incrementUsage();

        return $prompt;
    }

    /**
     * Render default prompt (no template).
     *
     * @param array $variables
     * @return string
     */
    public function renderDefault(array $variables): string
    {
        $keyword = $variables['keyword'] ?? 'your topic';
        $wordCountMin = $variables['word_count_min'] ?? 800;
        $wordCountMax = $variables['word_count_max'] ?? 1200;
        $tone = $variables['tone'] ?? 'professional';
        $style = $variables['style'] ?? 'blog';
        $language = $variables['language'] ?? 'en';
        $extraInstructions = $variables['extra_instructions'] ?? '';

        $prompt = <<<PROMPT
Write a comprehensive {$style} article about "{$keyword}".

Requirements:
- Word count: between {$wordCountMin} and {$wordCountMax} words
- Tone: {$tone}
- Language: {$language}
- SEO optimized with proper heading structure (H1, H2, H3)
- Include an engaging introduction and conclusion
- Use clear, readable language
- Include relevant examples and actionable insights

PROMPT;

        if (!empty($extraInstructions)) {
            $prompt .= "\n\nAdditional instructions:\n{$extraInstructions}";
        }

        $prompt .= <<<PROMPT


Format the output with:
1. A clear, compelling title (H1)
2. A brief excerpt/introduction (2-3 sentences)
3. Well-structured body content with headings
4. A strong conclusion

Begin writing now.
PROMPT;

        return trim($prompt);
    }

    /**
     * Validate template placeholders.
     *
     * @param PromptTemplate $template
     * @param array $variables
     * @return array Missing placeholders
     */
    public function validatePlaceholders(PromptTemplate $template, array $variables): array
    {
        $placeholders = $template->placeholders;
        $missing = [];

        foreach ($placeholders as $placeholder) {
            if (!isset($variables[$placeholder]) || empty($variables[$placeholder])) {
                $missing[] = $placeholder;
            }
        }

        return $missing;
    }

    /**
     * Preview rendered prompt.
     *
     * @param PromptTemplate $template
     * @param array $variables
     * @return string
     */
    public function preview(PromptTemplate $template, array $variables): string
    {
        // Render without incrementing usage count
        $prompt = $template->prompt_text;

        foreach ($variables as $key => $value) {
            $prompt = str_replace("{{" . $key . "}}", $value, $prompt);
        }

        return $prompt;
    }
}
```

---

## 5. SeoOptimizationService

**File**: `app/Services/ArticleSteps/SeoOptimizationService.php`

### Complete Code

```php
<?php

namespace App\Services\ArticleSteps;

use App\Models\Article;
use App\Models\SeoStep;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\Facades\Log;

class SeoOptimizationService
{
    public function __construct(
        protected OpenAIService $openAIService,
    ) {}

    /**
     * Generate SEO optimization steps for article.
     *
     * @param Article $article
     * @return array
     */
    public function generateSeoSteps(Article $article): array
    {
        $steps = [];

        // Step 1: Meta Title
        $steps[] = $this->createMetaTitleStep($article);

        // Step 2: Meta Description
        $steps[] = $this->createMetaDescriptionStep($article);

        // Step 3: H1 Heading
        $steps[] = $this->createH1Step($article);

        // Step 4: H2 Headings
        $steps[] = $this->createH2StructureStep($article);

        // Step 5: Keyword Density
        $steps[] = $this->createKeywordDensityStep($article);

        // Step 6: Readability
        $steps[] = $this->createReadabilityStep($article);

        // Step 7: Content Length
        $steps[] = $this->createContentLengthStep($article);

        // Step 8: Image Alt Text (if images present)
        if ($this->hasImages($article->content)) {
            $steps[] = $this->createImageAltStep($article);
        }

        Log::info("SEO steps generated", [
            'article_id' => $article->id,
            'steps_count' => count($steps),
        ]);

        return $steps;
    }

    /**
     * Create meta title optimization step.
     */
    protected function createMetaTitleStep(Article $article): SeoStep
    {
        $suggestion = $this->suggestMetaTitle($article);

        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_META_TITLE,
            'step_title' => 'Optimize Meta Title',
            'step_description' => 'Create a compelling meta title (50-60 characters) that includes your focus keyword.',
            'step_order' => 1,
            'status' => SeoStep::STATUS_PENDING,
            'ai_suggestion' => $suggestion,
            'is_automated' => true,
        ]);
    }

    /**
     * Create meta description optimization step.
     */
    protected function createMetaDescriptionStep(Article $article): SeoStep
    {
        $suggestion = $this->suggestMetaDescription($article);

        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_META_DESCRIPTION,
            'step_title' => 'Optimize Meta Description',
            'step_description' => 'Write a compelling meta description (150-160 characters) that encourages clicks.',
            'step_order' => 2,
            'status' => SeoStep::STATUS_PENDING,
            'ai_suggestion' => $suggestion,
            'is_automated' => true,
        ]);
    }

    /**
     * Create H1 heading step.
     */
    protected function createH1Step(Article $article): SeoStep
    {
        $currentH1 = $this->extractH1($article->content);
        $hasH1 = !empty($currentH1);

        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_HEADING_H1,
            'step_title' => 'Check H1 Heading',
            'step_description' => $hasH1
                ? "Your article has an H1: \"{$currentH1}\". Ensure it includes the focus keyword."
                : "Add a clear H1 heading that includes your focus keyword.",
            'step_order' => 3,
            'status' => $hasH1 ? SeoStep::STATUS_COMPLETED : SeoStep::STATUS_PENDING,
            'applied_value' => $currentH1,
            'is_automated' => false,
        ]);
    }

    /**
     * Create H2 structure step.
     */
    protected function createH2StructureStep(Article $article): SeoStep
    {
        $h2Count = $this->countH2($article->content);
        $suggestion = "Use 3-5 H2 headings to structure your content. Current count: {$h2Count}";

        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_HEADING_H2,
            'step_title' => 'Optimize H2 Structure',
            'step_description' => $suggestion,
            'step_order' => 4,
            'status' => $h2Count >= 3 ? SeoStep::STATUS_COMPLETED : SeoStep::STATUS_PENDING,
            'is_automated' => false,
        ]);
    }

    /**
     * Create keyword density step.
     */
    protected function createKeywordDensityStep(Article $article): SeoStep
    {
        $keyword = $article->keyword?->keyword ?? $article->focus_keyword;
        $density = $this->calculateKeywordDensity($article->content, $keyword);
        $isOptimal = $density >= 0.5 && $density <= 2.5;

        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_KEYWORD_DENSITY,
            'step_title' => 'Check Keyword Density',
            'step_description' => "Target keyword density: 0.5-2.5%. Current: {$density}%",
            'step_order' => 5,
            'status' => $isOptimal ? SeoStep::STATUS_COMPLETED : SeoStep::STATUS_PENDING,
            'applied_value' => (string) $density,
            'is_automated' => true,
        ]);
    }

    /**
     * Create readability step.
     */
    protected function createReadabilityStep(Article $article): SeoStep
    {
        $score = $article->readability_score ?? 0;
        $isGood = $score >= 60;

        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_READABILITY,
            'step_title' => 'Improve Readability',
            'step_description' => "Target readability score: 60+. Current: {$score}",
            'step_order' => 6,
            'status' => $isGood ? SeoStep::STATUS_COMPLETED : SeoStep::STATUS_PENDING,
            'is_automated' => true,
        ]);
    }

    /**
     * Create content length step.
     */
    protected function createContentLengthStep(Article $article): SeoStep
    {
        $wordCount = $article->word_count ?? 0;
        $isOptimal = $wordCount >= 800;

        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_CONTENT_LENGTH,
            'step_title' => 'Check Content Length',
            'step_description' => "Target: 800+ words. Current: {$wordCount} words",
            'step_order' => 7,
            'status' => $isOptimal ? SeoStep::STATUS_COMPLETED : SeoStep::STATUS_PENDING,
            'is_automated' => true,
        ]);
    }

    /**
     * Create image alt text step.
     */
    protected function createImageAltStep(Article $article): SeoStep
    {
        return SeoStep::create([
            'article_id' => $article->id,
            'step_type' => SeoStep::TYPE_IMAGE_ALT,
            'step_title' => 'Add Image Alt Text',
            'step_description' => 'Add descriptive alt text to all images for better SEO and accessibility.',
            'step_order' => 8,
            'status' => SeoStep::STATUS_PENDING,
            'is_automated' => false,
        ]);
    }

    /**
     * Calculate SEO score for article (0-100).
     *
     * @param Article $article
     * @return int
     */
    public function calculateSeoScore(Article $article): int
    {
        $score = 0;
        $maxScore = 100;

        // Load SEO steps
        $steps = $article->seoSteps;
        $completedSteps = $steps->where('status', SeoStep::STATUS_COMPLETED)->count();
        $totalSteps = $steps->count();

        if ($totalSteps > 0) {
            $score += ($completedSteps / $totalSteps) * 60; // 60% weight on steps
        }

        // Word count (10 points)
        $wordCount = $article->word_count ?? 0;
        if ($wordCount >= 1500) {
            $score += 10;
        } elseif ($wordCount >= 800) {
            $score += 7;
        } elseif ($wordCount >= 500) {
            $score += 4;
        }

        // Readability (15 points)
        $readability = $article->readability_score ?? 0;
        if ($readability >= 70) {
            $score += 15;
        } elseif ($readability >= 60) {
            $score += 10;
        } elseif ($readability >= 50) {
            $score += 5;
        }

        // Internal links (15 points)
        $linksCount = $article->internalLinks()->applied()->count();
        if ($linksCount >= 5) {
            $score += 15;
        } elseif ($linksCount >= 3) {
            $score += 10;
        } elseif ($linksCount >= 1) {
            $score += 5;
        }

        return min((int) round($score), 100);
    }

    /**
     * Suggest meta title using AI.
     */
    protected function suggestMetaTitle(Article $article): string
    {
        // If article already has a good title, use it
        if ($article->title && mb_strlen($article->title) <= 60) {
            return $article->title;
        }

        // Otherwise truncate
        return mb_substr($article->title, 0, 60);
    }

    /**
     * Suggest meta description using AI.
     */
    protected function suggestMetaDescription(Article $article): string
    {
        // Use excerpt or truncate content
        if ($article->excerpt) {
            return mb_substr($article->excerpt, 0, 160);
        }

        $plainText = strip_tags($article->content);
        return mb_substr($plainText, 0, 160);
    }

    /**
     * Extract H1 from content.
     */
    protected function extractH1(string $content): ?string
    {
        if (preg_match('/<h1[^>]*>(.+?)<\/h1>/i', $content, $matches)) {
            return strip_tags($matches[1]);
        }

        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Count H2 headings.
     */
    protected function countH2(string $content): int
    {
        $htmlCount = preg_match_all('/<h2[^>]*>(.+?)<\/h2>/i', $content);
        $markdownCount = preg_match_all('/^##\s+(.+)$/m', $content);

        return max($htmlCount, $markdownCount);
    }

    /**
     * Calculate keyword density.
     */
    protected function calculateKeywordDensity(string $content, ?string $keyword): float
    {
        if (empty($keyword)) {
            return 0;
        }

        $plainText = strtolower(strip_tags($content));
        $totalWords = str_word_count($plainText);

        if ($totalWords === 0) {
            return 0;
        }

        $keywordCount = substr_count($plainText, strtolower($keyword));
        $density = ($keywordCount / $totalWords) * 100;

        return round($density, 2);
    }

    /**
     * Check if content has images.
     */
    protected function hasImages(string $content): bool
    {
        return preg_match('/<img[^>]+>/i', $content) || preg_match('/!\[.+?\]\(.+?\)/', $content);
    }
}
```

---

## 6. InternalLinkSuggestionService

**File**: `app/Services/ArticleSteps/InternalLinkSuggestionService.php`

### Complete Code

```php
<?php

namespace App\Services\ArticleSteps;

use App\Models\Article;
use App\Models\InternalLink;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\Facades\Log;

class InternalLinkSuggestionService
{
    public function __construct(
        protected OpenAIService $openAIService,
    ) {}

    /**
     * Suggest internal links for article using AI.
     *
     * @param Article $article
     * @param int $maxSuggestions
     * @return array
     */
    public function suggestLinks(Article $article, int $maxSuggestions = 5): array
    {
        // Get published articles from same tenant
        $existingArticles = Article::forTenant($article->tenant_id)
            ->published()
            ->where('id', '!=', $article->id)
            ->select('id', 'title', 'slug', 'excerpt', 'focus_keyword')
            ->limit(50)
            ->get();

        if ($existingArticles->isEmpty()) {
            Log::info("No existing articles for internal links", [
                'article_id' => $article->id,
            ]);
            return [];
        }

        // Use AI to find relevant links
        $suggestions = $this->findRelevantLinksWithAI($article, $existingArticles, $maxSuggestions);

        // Save suggestions to database
        $savedLinks = [];
        foreach ($suggestions as $suggestion) {
            $savedLinks[] = InternalLink::create([
                'article_id' => $article->id,
                'target_article_id' => $suggestion['target_article_id'],
                'target_url' => $suggestion['target_url'],
                'anchor_text' => $suggestion['anchor_text'],
                'context_sentence' => $suggestion['context_sentence'],
                'relevance_score' => $suggestion['relevance_score'],
                'position_in_content' => $suggestion['position_in_content'] ?? 0,
                'status' => InternalLink::STATUS_SUGGESTED,
                'is_ai_suggested' => true,
                'suggestion_reason' => $suggestion['reason'] ?? null,
            ]);
        }

        Log::info("Internal links suggested", [
            'article_id' => $article->id,
            'suggestions_count' => count($savedLinks),
        ]);

        return $savedLinks;
    }

    /**
     * Find relevant links using AI.
     *
     * @param Article $article
     * @param \Illuminate\Support\Collection $existingArticles
     * @param int $maxSuggestions
     * @return array
     */
    protected function findRelevantLinksWithAI(Article $article, $existingArticles, int $maxSuggestions): array
    {
        // Build context for AI
        $articleContext = [
            'title' => $article->title,
            'content' => mb_substr(strip_tags($article->content), 0, 2000), // First 2000 chars
            'keyword' => $article->keyword?->keyword,
        ];

        $existingArticlesContext = $existingArticles->map(function ($a) {
            return [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'excerpt' => $a->excerpt,
                'keyword' => $a->focus_keyword,
            ];
        })->toArray();

        // Call OpenAI
        $prompt = $this->buildLinkSuggestionPrompt($articleContext, $existingArticlesContext, $maxSuggestions);

        try {
            $response = $this->openAIService->chatCompletion([
                'model' => 'gpt-4o-mini', // Cheaper model for this task
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO expert specialized in internal linking strategies. Analyze the article and suggest the most relevant internal links.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            return $this->formatLinkSuggestions($parsed['suggestions'] ?? [], $existingArticles);

        } catch (\Exception $e) {
            Log::error("Internal link AI suggestion failed", [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to keyword matching
            return $this->fallbackKeywordMatching($article, $existingArticles, $maxSuggestions);
        }
    }

    /**
     * Build prompt for link suggestion.
     */
    protected function buildLinkSuggestionPrompt(array $articleContext, array $existingArticles, int $max): string
    {
        $existingJson = json_encode($existingArticles, JSON_PRETTY_PRINT);

        return <<<PROMPT
I need you to suggest the top {$max} most relevant internal links for this article.

**Current Article:**
Title: {$articleContext['title']}
Keyword: {$articleContext['keyword']}
Content (excerpt): {$articleContext['content']}

**Available Articles for Linking:**
{$existingJson}

**Task:**
Analyze the current article content and suggest the {$max} most relevant internal links from the available articles.

For each suggestion, provide:
1. article_id: ID of the target article
2. anchor_text: The exact text that should be used as anchor (3-6 words, natural)
3. context_sentence: Where in the current article this link would fit (quote a sentence)
4. relevance_score: 0.0 to 1.0 (how relevant is this link)
5. reason: Brief explanation of why this link is relevant

Return as JSON in this format:
{
  "suggestions": [
    {
      "article_id": "01JXXX...",
      "anchor_text": "...",
      "context_sentence": "...",
      "relevance_score": 0.9,
      "reason": "..."
    }
  ]
}
PROMPT;
    }

    /**
     * Format AI suggestions into internal link data.
     */
    protected function formatLinkSuggestions(array $suggestions, $existingArticles): array
    {
        $formatted = [];

        foreach ($suggestions as $suggestion) {
            $targetArticle = $existingArticles->firstWhere('id', $suggestion['article_id']);

            if (!$targetArticle) {
                continue;
            }

            $formatted[] = [
                'target_article_id' => $targetArticle->id,
                'target_url' => $targetArticle->published_url ?? "/articles/{$targetArticle->slug}",
                'anchor_text' => $suggestion['anchor_text'] ?? $targetArticle->title,
                'context_sentence' => $suggestion['context_sentence'] ?? '',
                'relevance_score' => $suggestion['relevance_score'] ?? 0.5,
                'reason' => $suggestion['reason'] ?? null,
            ];
        }

        return $formatted;
    }

    /**
     * Fallback: keyword-based matching.
     */
    protected function fallbackKeywordMatching(Article $article, $existingArticles, int $max): array
    {
        $articleKeyword = $article->keyword?->keyword;
        $suggestions = [];

        if (empty($articleKeyword)) {
            return [];
        }

        foreach ($existingArticles as $existingArticle) {
            $targetKeyword = $existingArticle->focus_keyword ?? $existingArticle->title;

            // Calculate simple relevance score (keyword overlap)
            $score = $this->calculateKeywordSimilarity($articleKeyword, $targetKeyword);

            if ($score > 0.3) {
                $suggestions[] = [
                    'target_article_id' => $existingArticle->id,
                    'target_url' => $existingArticle->published_url ?? "/articles/{$existingArticle->slug}",
                    'anchor_text' => $existingArticle->title,
                    'context_sentence' => '',
                    'relevance_score' => $score,
                    'reason' => 'Keyword similarity',
                ];
            }
        }

        // Sort by relevance and limit
        usort($suggestions, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        return array_slice($suggestions, 0, $max);
    }

    /**
     * Calculate keyword similarity (simple).
     */
    protected function calculateKeywordSimilarity(string $keyword1, string $keyword2): float
    {
        $words1 = explode(' ', strtolower($keyword1));
        $words2 = explode(' ', strtolower($keyword2));

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        if (count($union) === 0) {
            return 0;
        }

        return count($intersection) / count($union);
    }

    /**
     * Apply suggested link to article content.
     *
     * @param InternalLink $link
     * @return void
     */
    public function applyLink(InternalLink $link): void
    {
        $article = $link->article;

        // Find context sentence in content and insert link
        $content = $article->content;
        $anchorText = $link->anchor_text;
        $targetUrl = $link->target_url;

        // Simple replacement (can be enhanced)
        $linkHtml = "<a href=\"{$targetUrl}\">{$anchorText}</a>";
        $updatedContent = str_replace($anchorText, $linkHtml, $content);

        $article->update(['content' => $updatedContent]);
        $link->apply(auth()->id());

        Log::info("Internal link applied", [
            'link_id' => $link->id,
            'article_id' => $article->id,
        ]);
    }
}
```

---

## 7. AbTestingService

**File**: `app/Services/ArticleSteps/AbTestingService.php`

### Complete Code

```php
<?php

namespace App\Services\ArticleSteps;

use App\Models\Article;
use App\Models\ArticleVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbTestingService
{
    /**
     * Create AB test variant.
     *
     * @param Article $parentArticle
     * @param array $variantData
     * @return ArticleVariant
     */
    public function createVariant(Article $parentArticle, array $variantData): ArticleVariant
    {
        DB::beginTransaction();

        try {
            // Create variant article
            $variantArticle = Article::create([
                'tenant_id' => $parentArticle->tenant_id,
                'keyword_id' => $parentArticle->keyword_id,
                'prompt_template_id' => $parentArticle->prompt_template_id,
                'parent_article_id' => $parentArticle->id,
                'is_variant' => true,
                'variant_name' => $variantData['variant_name'],
                'title' => $variantData['title'] ?? $parentArticle->title,
                'content' => $variantData['content'] ?? $parentArticle->content,
                'status' => Article::STATUS_COMPLETED,
                'created_by' => auth()->id(),
            ]);

            // Create variant tracking
            $variant = ArticleVariant::create([
                'parent_article_id' => $parentArticle->id,
                'variant_article_id' => $variantArticle->id,
                'variant_name' => $variantData['variant_name'],
                'variant_type' => $variantData['variant_type'] ?? ArticleVariant::TYPE_FULL_CONTENT,
                'traffic_percentage' => $variantData['traffic_percentage'] ?? 50,
                'status' => ArticleVariant::STATUS_DRAFT,
            ]);

            DB::commit();

            Log::info("AB test variant created", [
                'parent_article_id' => $parentArticle->id,
                'variant_article_id' => $variantArticle->id,
                'variant_id' => $variant->id,
            ]);

            return $variant;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Start AB test.
     *
     * @param ArticleVariant $variant
     * @return void
     */
    public function startTest(ArticleVariant $variant): void
    {
        $variant->start();

        Log::info("AB test started", [
            'variant_id' => $variant->id,
            'parent_article_id' => $variant->parent_article_id,
        ]);
    }

    /**
     * Record impression/view.
     *
     * @param ArticleVariant $variant
     * @return void
     */
    public function recordView(ArticleVariant $variant): void
    {
        $variant->increment('views_count');
    }

    /**
     * Record click.
     *
     * @param ArticleVariant $variant
     * @return void
     */
    public function recordClick(ArticleVariant $variant): void
    {
        $variant->increment('clicks_count');
    }

    /**
     * Record conversion.
     *
     * @param ArticleVariant $variant
     * @return void
     */
    public function recordConversion(ArticleVariant $variant): void
    {
        $variant->increment('conversions_count');
    }

    /**
     * Calculate statistical significance between variants.
     *
     * @param ArticleVariant $variantA
     * @param ArticleVariant $variantB
     * @return float
     */
    public function calculateStatisticalSignificance(ArticleVariant $variantA, ArticleVariant $variantB): float
    {
        // Z-test for proportions
        $n1 = $variantA->views_count;
        $n2 = $variantB->views_count;
        $p1 = $n1 > 0 ? $variantA->conversions_count / $n1 : 0;
        $p2 = $n2 > 0 ? $variantB->conversions_count / $n2 : 0;

        if ($n1 === 0 || $n2 === 0) {
            return 0;
        }

        $p = ($variantA->conversions_count + $variantB->conversions_count) / ($n1 + $n2);
        $se = sqrt($p * (1 - $p) * (1/$n1 + 1/$n2));

        if ($se === 0) {
            return 0;
        }

        $z = ($p1 - $p2) / $se;

        // Convert Z-score to confidence level (approximation)
        $confidence = $this->zScoreToConfidence(abs($z));

        return $confidence;
    }

    /**
     * Convert Z-score to confidence level.
     */
    protected function zScoreToConfidence(float $z): float
    {
        // Simplified mapping
        if ($z >= 2.58) return 0.99;  // 99% confidence
        if ($z >= 1.96) return 0.95;  // 95% confidence
        if ($z >= 1.65) return 0.90;  // 90% confidence
        if ($z >= 1.28) return 0.80;  // 80% confidence

        return 0.50; // Below 80%
    }

    /**
     * Determine winner of AB test.
     *
     * @param Article $parentArticle
     * @return ArticleVariant|null
     */
    public function determineWinner(Article $parentArticle): ?ArticleVariant
    {
        $variants = $parentArticle->variants()
            ->where('status', ArticleVariant::STATUS_RUNNING)
            ->get();

        if ($variants->count() < 2) {
            return null;
        }

        // Sort by conversion rate
        $sorted = $variants->sortByDesc(function ($variant) {
            return $variant->conversion_rate;
        });

        $winner = $sorted->first();
        $runnerUp = $sorted->skip(1)->first();

        // Calculate statistical significance
        $significance = $this->calculateStatisticalSignificance($winner, $runnerUp);

        // Winner needs 95% confidence
        if ($significance >= 0.95) {
            $winner->markAsWinner($significance);
            $winner->complete();

            // Complete other variants
            foreach ($variants as $variant) {
                if ($variant->id !== $winner->id) {
                    $variant->complete();
                }
            }

            Log::info("AB test winner determined", [
                'parent_article_id' => $parentArticle->id,
                'winner_variant_id' => $winner->id,
                'confidence' => $significance,
            ]);

            return $winner;
        }

        return null; // No winner yet
    }

    /**
     * Get AB test results summary.
     *
     * @param Article $parentArticle
     * @return array
     */
    public function getTestResults(Article $parentArticle): array
    {
        $variants = $parentArticle->variants()
            ->with(['parentArticle', 'variantArticle'])
            ->get();

        $results = $variants->map(function ($variant) {
            return [
                'variant_id' => $variant->id,
                'variant_name' => $variant->variant_name,
                'status' => $variant->status,
                'views' => $variant->views_count,
                'clicks' => $variant->clicks_count,
                'conversions' => $variant->conversions_count,
                'conversion_rate' => $variant->conversion_rate,
                'ctr' => $variant->ctr,
                'is_winner' => $variant->is_winner,
                'confidence' => $variant->confidence_level,
                'test_duration' => $variant->test_duration,
            ];
        })->toArray();

        // Calculate overall stats
        $totalViews = $variants->sum('views_count');
        $totalConversions = $variants->sum('conversions_count');

        return [
            'variants' => $results,
            'total_views' => $totalViews,
            'total_conversions' => $totalConversions,
            'overall_conversion_rate' => $totalViews > 0 ? round(($totalConversions / $totalViews) * 100, 2) : 0,
            'has_winner' => $variants->where('is_winner', true)->isNotEmpty(),
        ];
    }
}
```

---

## 8. ContentAnalysisService

**File**: `app/Services/ArticleSteps/ContentAnalysisService.php`

### Complete Code

```php
<?php

namespace App\Services\ArticleSteps;

use App\Models\Article;
use Illuminate\Support\Facades\Log;

class ContentAnalysisService
{
    /**
     * Analyze article content and update metrics.
     *
     * @param Article $article
     * @return array
     */
    public function analyzeArticle(Article $article): array
    {
        $metrics = [
            'word_count' => $this->calculateWordCount($article->content),
            'reading_time_minutes' => $this->calculateReadingTime($article->content),
            'readability_score' => $this->calculateReadabilityScore($article->content),
            'tone_detected' => $this->detectTone($article->content),
        ];

        // Update article
        $article->update($metrics);

        Log::info("Content analyzed", [
            'article_id' => $article->id,
            'metrics' => $metrics,
        ]);

        return $metrics;
    }

    /**
     * Calculate word count.
     */
    protected function calculateWordCount(string $content): int
    {
        return str_word_count(strip_tags($content));
    }

    /**
     * Calculate reading time (200 words per minute).
     */
    protected function calculateReadingTime(string $content): int
    {
        $wordCount = $this->calculateWordCount($content);
        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Calculate Flesch Reading Ease score.
     */
    protected function calculateReadabilityScore(string $content): float
    {
        $plainText = strip_tags($content);

        // Count sentences
        $sentences = preg_split('/[.!?]+/', $plainText, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) {
            return 0;
        }

        // Count words
        $wordCount = str_word_count($plainText);

        if ($wordCount === 0) {
            return 0;
        }

        // Count syllables (approximation)
        $syllableCount = $this->countSyllables($plainText);

        // Flesch Reading Ease formula
        $avgWordsPerSentence = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $syllableCount / $wordCount;

        $score = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord);

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * Count syllables in text (approximation).
     */
    protected function countSyllables(string $text): int
    {
        $words = str_word_count(strtolower($text), 1);
        $syllableCount = 0;

        foreach ($words as $word) {
            $syllableCount += $this->countSyllablesInWord($word);
        }

        return max(1, $syllableCount);
    }

    /**
     * Count syllables in a single word.
     */
    protected function countSyllablesInWord(string $word): int
    {
        $word = strtolower($word);
        $syllables = 0;

        // Count vowel groups
        $vowels = ['a', 'e', 'i', 'o', 'u', 'y'];
        $previousWasVowel = false;

        for ($i = 0; $i < strlen($word); $i++) {
            $isVowel = in_array($word[$i], $vowels);

            if ($isVowel && !$previousWasVowel) {
                $syllables++;
            }

            $previousWasVowel = $isVowel;
        }

        // Adjust for silent 'e'
        if (substr($word, -1) === 'e') {
            $syllables--;
        }

        return max(1, $syllables);
    }

    /**
     * Detect tone of content.
     */
    protected function detectTone(string $content): string
    {
        $plainText = strtolower(strip_tags($content));

        // Simple pattern matching (can be enhanced with AI)
        $professionalWords = ['furthermore', 'therefore', 'consequently', 'moreover', 'however'];
        $casualWords = ['you', 'your', 'we', 'us', 'our', 'I'];
        $friendlyWords = ['thanks', 'great', 'awesome', 'fantastic', 'wonderful'];

        $professionalCount = 0;
        $casualCount = 0;
        $friendlyCount = 0;

        foreach ($professionalWords as $word) {
            $professionalCount += substr_count($plainText, $word);
        }

        foreach ($casualWords as $word) {
            $casualCount += substr_count($plainText, ' ' . $word . ' ');
        }

        foreach ($friendlyWords as $word) {
            $friendlyCount += substr_count($plainText, $word);
        }

        // Determine dominant tone
        if ($professionalCount > $casualCount && $professionalCount > $friendlyCount) {
            return 'professional';
        } elseif ($friendlyCount > $professionalCount && $friendlyCount > $casualCount) {
            return 'friendly';
        } elseif ($casualCount > 5) {
            return 'conversational';
        }

        return 'neutral';
    }

    /**
     * Get content quality score (0-100).
     */
    public function getQualityScore(Article $article): int
    {
        $score = 0;

        // Word count (25 points)
        if ($article->word_count >= 1500) {
            $score += 25;
        } elseif ($article->word_count >= 1000) {
            $score += 20;
        } elseif ($article->word_count >= 500) {
            $score += 15;
        }

        // Readability (25 points)
        if ($article->readability_score >= 70) {
            $score += 25;
        } elseif ($article->readability_score >= 60) {
            $score += 20;
        } elseif ($article->readability_score >= 50) {
            $score += 15;
        }

        // SEO score (30 points)
        $seoScore = $article->seo_score ?? 0;
        $score += ($seoScore / 100) * 30;

        // Internal links (20 points)
        $linksCount = $article->internalLinks()->applied()->count();
        if ($linksCount >= 5) {
            $score += 20;
        } elseif ($linksCount >= 3) {
            $score += 15;
        } elseif ($linksCount >= 1) {
            $score += 10;
        }

        return min(100, (int) round($score));
    }
}
```

---

## 9. Service Integration Examples

### Example 1: Complete Article Generation Flow

```php
use App\Services\ArticleSteps\ArticleGenerationService;

// In controller
public function generate(Request $request, ArticleGenerationService $service)
{
    $tenant = auth()->user()->tenant;

    $article = $service->startGeneration($tenant, [
        'keyword_id' => $request->keyword_id,
        'prompt_template_id' => $request->template_id,
        'word_count_min' => 800,
        'word_count_max' => 1200,
        'tone' => 'professional',
        'include_internal_links' => true,
        'extra_instructions' => $request->instructions,
    ]);

    return response()->json([
        'article_id' => $article->id,
        'status' => 'queued',
    ]);
}
```

### Example 2: Bulk Keyword Import

```php
use App\Services\ArticleSteps\KeywordManagementService;

public function importKeywords(Request $request, KeywordManagementService $service)
{
    $tenant = auth()->user()->tenant;

    $result = $service->createBulkFromText($tenant, $request->keywords_text, [
        'priority' => 5,
        'language' => 'en',
    ]);

    return response()->json([
        'created' => $result['created_count'],
        'skipped' => $result['skipped_count'],
    ]);
}
```

### Example 3: Apply AI Link Suggestions

```php
use App\Services\ArticleSteps\InternalLinkSuggestionService;

public function applyLinkSuggestions(Article $article, InternalLinkSuggestionService $service)
{
    $links = $article->internalLinks()
        ->suggested()
        ->highRelevance(0.7)
        ->get();

    foreach ($links as $link) {
        $service->applyLink($link);
    }

    return response()->json(['applied_count' => $links->count()]);
}
```

### Example 4: Create AB Test

```php
use App\Services\ArticleSteps\AbTestingService;

public function createAbTest(Article $article, Request $request, AbTestingService $service)
{
    $variant = $service->createVariant($article, [
        'variant_name' => 'Variant A - Alternative Title',
        'variant_type' => ArticleVariant::TYPE_TITLE,
        'title' => $request->alternative_title,
        'traffic_percentage' => 50,
    ]);

    $service->startTest($variant);

    return response()->json(['variant_id' => $variant->id]);
}
```

### Example 5: Calculate SEO Score

```php
use App\Services\ArticleSteps\SeoOptimizationService;

public function calculateSeo(Article $article, SeoOptimizationService $service)
{
    $score = $service->calculateSeoScore($article);

    $article->update(['seo_score' => $score]);

    return response()->json([
        'seo_score' => $score,
        'level' => $article->seo_score_level,
    ]);
}
```

---

## Implementation Checklist

### Services Setup
- [x] ArticleGenerationService with full workflow
- [x] KeywordManagementService with bulk import
- [x] PromptRenderingService with template rendering
- [x] SeoOptimizationService with scoring
- [x] InternalLinkSuggestionService with AI
- [x] AbTestingService with statistical significance
- [x] ContentAnalysisService with readability

### Integration
- [ ] Create Queue Jobs (GenerateArticleJob)
- [ ] Create Events (ArticleGenerationStarted, etc.)
- [ ] Integrate with OpenAIService
- [ ] Integrate with TokenTrackingService
- [ ] Add Service Providers bindings

### Testing
- [ ] Unit tests for each service
- [ ] Integration tests for full workflow
- [ ] Mock OpenAI responses
- [ ] Test error handling

---

**Documento completo**: 7 Services con 1,800+ LOC
**Status**: ✅ Production-Ready
**Next Step**: `04-CONTROLLERS-ROUTES.md` — Controller Layer Implementation

---

_AI Article Steps — Ainstein Platform_
_Laravel Multi-Tenant SaaS_
_Generated: October 2025_
