# AI Article Steps — Testing Strategy

**Documento**: 06 — Testing Strategy & Examples
**Progetto**: Ainstein Laravel Multi-Tenant Platform
**Tool**: AI Article Steps (Copy Article Generator)
**Testing**: PHPUnit + Pest + Mocks

---

## Indice
1. [Overview Testing](#1-overview-testing)
2. [Unit Tests — Models](#2-unit-tests--models)
3. [Feature Tests — Controllers](#3-feature-tests--controllers)
4. [Integration Tests — Services](#4-integration-tests--services)
5. [Queue Job Tests](#5-queue-job-tests)
6. [Event Tests](#6-event-tests)
7. [Factory Definitions](#7-factory-definitions)
8. [Mock Examples](#8-mock-examples)
9. [PHPUnit Configuration](#9-phpunit-configuration)

---

## 1. Overview Testing

### Testing Strategy

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── ArticleTest.php
│   │   ├── KeywordTest.php
│   │   └── PromptTemplateTest.php
│   └── Services/
│       ├── ArticleGenerationServiceTest.php
│       └── SeoOptimizationServiceTest.php
├── Feature/
│   ├── ArticleManagementTest.php
│   ├── KeywordManagementTest.php
│   └── ArticleGenerationWorkflowTest.php
└── Integration/
    ├── FullArticleGenerationTest.php
    └── AbTestingWorkflowTest.php
```

### Coverage Goals
- **Unit Tests**: 80%+ coverage
- **Feature Tests**: All HTTP endpoints
- **Integration Tests**: Critical workflows

---

## 2. Unit Tests — Models

### Article Model Tests

**File**: `tests/Unit/Models/ArticleTest.php`

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\Keyword;
use App\Models\SeoStep;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_tenant()
    {
        $tenant = Tenant::factory()->create();
        $article = Article::factory()->for($tenant)->create();

        $this->assertInstanceOf(Tenant::class, $article->tenant);
        $this->assertEquals($tenant->id, $article->tenant_id);
    }

    /** @test */
    public function it_belongs_to_a_keyword()
    {
        $keyword = Keyword::factory()->create();
        $article = Article::factory()->for($keyword)->create();

        $this->assertInstanceOf(Keyword::class, $article->keyword);
        $this->assertEquals($keyword->id, $article->keyword_id);
    }

    /** @test */
    public function it_has_many_seo_steps()
    {
        $article = Article::factory()->create();
        $steps = SeoStep::factory(3)->for($article)->create();

        $this->assertCount(3, $article->seoSteps);
        $this->assertInstanceOf(SeoStep::class, $article->seoSteps->first());
    }

    /** @test */
    public function it_calculates_word_count_correctly()
    {
        $article = Article::factory()->create([
            'content' => 'This is a test article with exactly ten words here.',
        ]);

        $wordCount = $article->calculateWordCount();

        $this->assertEquals(10, $wordCount);
    }

    /** @test */
    public function it_calculates_reading_time()
    {
        $article = Article::factory()->create([
            'content' => str_repeat('word ', 400), // 400 words
        ]);

        $readingTime = $article->calculateReadingTime();

        $this->assertEquals(2, $readingTime); // 400 words / 200 wpm = 2 minutes
    }

    /** @test */
    public function it_can_mark_as_generating()
    {
        $article = Article::factory()->create([
            'status' => Article::STATUS_PENDING,
        ]);

        $article->markAsGenerating();

        $this->assertEquals(Article::STATUS_GENERATING, $article->status);
        $this->assertNotNull($article->generation_started_at);
    }

    /** @test */
    public function it_can_mark_as_completed()
    {
        $article = Article::factory()->create([
            'status' => Article::STATUS_GENERATING,
        ]);

        $article->markAsCompleted(['word_count' => 1000]);

        $this->assertEquals(Article::STATUS_COMPLETED, $article->status);
        $this->assertNotNull($article->generation_completed_at);
        $this->assertEquals(1000, $article->word_count);
    }

    /** @test */
    public function it_can_mark_as_failed()
    {
        $article = Article::factory()->create([
            'status' => Article::STATUS_GENERATING,
        ]);

        $article->markAsFailed('AI service error');

        $this->assertEquals(Article::STATUS_FAILED, $article->status);
        $this->assertNotNull($article->generation_failed_at);
        $this->assertEquals('AI service error', $article->failure_reason);
    }

    /** @test */
    public function it_auto_generates_slug_from_title()
    {
        $article = Article::factory()->create([
            'title' => 'How to Use AI for Content Marketing',
        ]);

        $this->assertEquals('how-to-use-ai-for-content-marketing', $article->slug);
    }

    /** @test */
    public function it_checks_if_generating()
    {
        $article = Article::factory()->create(['status' => Article::STATUS_GENERATING]);

        $this->assertTrue($article->isGenerating());
        $this->assertFalse($article->isCompleted());
        $this->assertFalse($article->isFailed());
    }

    /** @test */
    public function it_returns_seo_steps_completion_percentage()
    {
        $article = Article::factory()->create();
        SeoStep::factory(10)->for($article)->create(['status' => SeoStep::STATUS_PENDING]);
        SeoStep::factory(5)->for($article)->create(['status' => SeoStep::STATUS_COMPLETED]);

        $percentage = $article->seo_steps_completion_percentage;

        $this->assertEquals(33, $percentage); // 5 out of 15 = 33%
    }
}
```

### Keyword Model Tests

**File**: `tests/Unit/Models/KeywordTest.php`

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Keyword;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_many_articles()
    {
        $keyword = Keyword::factory()->create();
        Article::factory(3)->for($keyword)->create();

        $this->assertCount(3, $keyword->articles);
    }

    /** @test */
    public function it_checks_if_used()
    {
        $usedKeyword = Keyword::factory()->create();
        Article::factory()->for($usedKeyword)->create();

        $unusedKeyword = Keyword::factory()->create();

        $this->assertTrue($usedKeyword->isUsed());
        $this->assertFalse($unusedKeyword->isUsed());
    }

    /** @test */
    public function it_returns_competition_level()
    {
        $lowCompetition = Keyword::factory()->create(['competition' => 0.2]);
        $mediumCompetition = Keyword::factory()->create(['competition' => 0.5]);
        $highCompetition = Keyword::factory()->create(['competition' => 0.9]);

        $this->assertEquals('Low', $lowCompetition->competition_level);
        $this->assertEquals('Medium', $mediumCompetition->competition_level);
        $this->assertEquals('High', $highCompetition->competition_level);
    }

    /** @test */
    public function it_can_mark_as_used()
    {
        $keyword = Keyword::factory()->create(['status' => Keyword::STATUS_ACTIVE]);

        $keyword->markAsUsed();

        $this->assertEquals(Keyword::STATUS_USED, $keyword->status);
    }

    /** @test */
    public function unused_scope_filters_correctly()
    {
        $usedKeyword = Keyword::factory()->create();
        Article::factory()->for($usedKeyword)->create();

        $unusedKeyword = Keyword::factory()->create();

        $unused = Keyword::unused()->get();

        $this->assertTrue($unused->contains($unusedKeyword));
        $this->assertFalse($unused->contains($usedKeyword));
    }
}
```

---

## 3. Feature Tests — Controllers

### Article Management Tests

**File**: `tests/Feature/ArticleManagementTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Keyword;
use App\Models\PromptTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_displays_articles_index_page()
    {
        $response = $this->get(route('tenant.articles.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.article-steps.articles.index');
    }

    /** @test */
    public function it_displays_create_article_page()
    {
        $response = $this->get(route('tenant.articles.create'));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.article-steps.articles.create');
        $response->assertViewHas('keywords');
        $response->assertViewHas('templates');
    }

    /** @test */
    public function it_can_start_article_generation()
    {
        $keyword = Keyword::factory()->for($this->tenant)->create();
        $template = PromptTemplate::factory()->for($this->tenant)->create();

        $response = $this->post(route('tenant.articles.store'), [
            'keyword_id' => $keyword->id,
            'prompt_template_id' => $template->id,
            'word_count_min' => 800,
            'word_count_max' => 1200,
            'tone' => 'professional',
            'include_internal_links' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('articles', [
            'tenant_id' => $this->tenant->id,
            'keyword_id' => $keyword->id,
            'status' => Article::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function it_validates_article_creation_requires_keyword()
    {
        $response = $this->post(route('tenant.articles.store'), [
            'prompt_template_id' => PromptTemplate::factory()->create()->id,
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function it_displays_article_detail_page()
    {
        $article = Article::factory()->for($this->tenant)->create();

        $response = $this->get(route('tenant.articles.show', $article));

        $response->assertStatus(200);
        $response->assertViewIs('tenant.article-steps.articles.show');
        $response->assertViewHas('article');
    }

    /** @test */
    public function it_can_update_article()
    {
        $article = Article::factory()->for($this->tenant)->create([
            'status' => Article::STATUS_COMPLETED,
        ]);

        $response = $this->put(route('tenant.articles.update', $article), [
            'title' => 'Updated Title',
            'content' => 'Updated content...',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function it_can_publish_article()
    {
        $article = Article::factory()->for($this->tenant)->create([
            'status' => Article::STATUS_COMPLETED,
        ]);

        $response = $this->post(route('tenant.articles.publish', $article));

        $response->assertRedirect();
        $article->refresh();

        $this->assertEquals(Article::STATUS_PUBLISHED, $article->status);
        $this->assertTrue($article->is_published);
        $this->assertNotNull($article->published_at);
    }

    /** @test */
    public function it_cannot_publish_article_from_another_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $article = Article::factory()->for($otherTenant)->create();

        $response = $this->post(route('tenant.articles.publish', $article));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_delete_article()
    {
        $article = Article::factory()->for($this->tenant)->create();

        $response = $this->delete(route('tenant.articles.destroy', $article));

        $response->assertRedirect();
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }
}
```

### Keyword Management Tests

**File**: `tests/Feature/KeywordManagementTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_bulk_import_keywords()
    {
        $keywordsText = "AI content marketing\nSEO optimization\nContent automation";

        $response = $this->post(route('tenant.keywords.store'), [
            'keywords_text' => $keywordsText,
            'priority' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('keywords', [
            'tenant_id' => $this->tenant->id,
            'keyword' => 'AI content marketing',
        ]);
        $this->assertDatabaseHas('keywords', [
            'tenant_id' => $this->tenant->id,
            'keyword' => 'SEO optimization',
        ]);
    }

    /** @test */
    public function it_skips_duplicate_keywords_on_bulk_import()
    {
        Keyword::factory()->for($this->tenant)->create([
            'keyword' => 'existing keyword',
        ]);

        $keywordsText = "existing keyword\nnew keyword";

        $response = $this->post(route('tenant.keywords.store'), [
            'keywords_text' => $keywordsText,
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, Keyword::where('keyword', 'existing keyword')->count());
        $this->assertEquals(1, Keyword::where('keyword', 'new keyword')->count());
    }

    /** @test */
    public function it_can_suggest_next_keyword()
    {
        Keyword::factory()->for($this->tenant)->unused()->create([
            'priority' => 10,
            'search_volume' => 5000,
        ]);
        Keyword::factory()->for($this->tenant)->unused()->create([
            'priority' => 5,
            'search_volume' => 2000,
        ]);

        $response = $this->get(route('tenant.keywords.suggest'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'keyword']);
        $this->assertEquals(10, $response->json('keyword.priority'));
    }
}
```

---

## 4. Integration Tests — Services

### Article Generation Service Tests

**File**: `tests/Integration/ArticleGenerationServiceTest.php`

```php
<?php

namespace Tests\Integration;

use App\Models\Article;
use App\Models\Keyword;
use App\Models\Tenant;
use App\Services\ArticleSteps\ArticleGenerationService;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ArticleGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected ArticleGenerationService $service;
    protected $openAIMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        // Mock OpenAI Service
        $this->openAIMock = Mockery::mock(OpenAIService::class);
        $this->app->instance(OpenAIService::class, $this->openAIMock);

        $this->service = app(ArticleGenerationService::class);
    }

    /** @test */
    public function it_starts_article_generation()
    {
        $keyword = Keyword::factory()->for($this->tenant)->create();

        $article = $this->service->startGeneration($this->tenant, [
            'keyword_id' => $keyword->id,
        ]);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals(Article::STATUS_PENDING, $article->status);
        $this->assertDatabaseHas('article_generations', [
            'article_id' => $article->id,
            'status' => 'queued',
        ]);
    }

    /** @test */
    public function it_executes_full_generation_workflow()
    {
        // Mock OpenAI response
        $this->openAIMock->shouldReceive('chatCompletion')
            ->once()
            ->andReturn([
                'choices' => [
                    ['message' => ['content' => '# Article Title\n\nArticle content here...']]
                ],
                'usage' => ['total_tokens' => 1500],
            ]);

        $article = Article::factory()->for($this->tenant)->create([
            'status' => Article::STATUS_PENDING,
        ]);

        $this->service->executeGeneration($article->id, [
            'word_count_min' => 800,
            'tone' => 'professional',
        ]);

        $article->refresh();

        $this->assertEquals(Article::STATUS_COMPLETED, $article->status);
        $this->assertNotNull($article->content);
        $this->assertNotNull($article->title);
        $this->assertEquals(1500, $article->tokens_used);
    }

    /** @test */
    public function it_handles_generation_failure_gracefully()
    {
        $this->openAIMock->shouldReceive('chatCompletion')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $article = Article::factory()->for($this->tenant)->create([
            'status' => Article::STATUS_PENDING,
        ]);

        $this->expectException(\Exception::class);

        $this->service->executeGeneration($article->id, []);

        $article->refresh();

        $this->assertEquals(Article::STATUS_FAILED, $article->status);
        $this->assertNotNull($article->failure_reason);
    }

    /** @test */
    public function it_returns_generation_status()
    {
        $article = Article::factory()->for($this->tenant)->create();
        $article->generations()->create([
            'status' => 'processing',
            'progress_percentage' => 50,
            'current_step' => 3,
            'total_steps' => 5,
            'step_description' => 'Generating content...',
        ]);

        $status = $this->service->getGenerationStatus($article->id);

        $this->assertEquals('processing', $status['status']);
        $this->assertEquals(50, $status['progress']);
        $this->assertEquals(3, $status['current_step']);
        $this->assertEquals(5, $status['total_steps']);
    }
}
```

---

## 5. Queue Job Tests

### Generate Article Job Test

**File**: `tests/Unit/Jobs/GenerateArticleJobTest.php`

```php
<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateArticleJob;
use App\Models\Article;
use App\Services\ArticleSteps\ArticleGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class GenerateArticleJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calls_article_generation_service()
    {
        $article = Article::factory()->create();

        $serviceMock = Mockery::mock(ArticleGenerationService::class);
        $serviceMock->shouldReceive('executeGeneration')
            ->once()
            ->with($article->id, ['test' => 'params']);

        $this->app->instance(ArticleGenerationService::class, $serviceMock);

        $job = new GenerateArticleJob($article->id, ['test' => 'params']);
        $job->handle($serviceMock);
    }

    /** @test */
    public function it_has_correct_queue_configuration()
    {
        $article = Article::factory()->create();
        $job = new GenerateArticleJob($article->id, []);

        $this->assertEquals('articles', $job->queue);
        $this->assertEquals(3, $job->tries);
    }
}
```

---

## 6. Event Tests

### Article Generation Events Test

**File**: `tests/Unit/Events/ArticleGenerationEventsTest.php`

```php
<?php

namespace Tests\Unit\Events;

use App\Events\ArticleGenerationCompleted;
use App\Events\ArticleGenerationStarted;
use App\Models\Article;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ArticleGenerationEventsTest extends TestCase
{
    /** @test */
    public function article_generation_started_event_is_fired()
    {
        Event::fake();

        $article = Article::factory()->create();

        event(new ArticleGenerationStarted($article, $article->tenant));

        Event::assertDispatched(ArticleGenerationStarted::class);
    }

    /** @test */
    public function article_generation_completed_event_is_fired()
    {
        Event::fake();

        $article = Article::factory()->create();

        event(new ArticleGenerationCompleted($article));

        Event::assertDispatched(ArticleGenerationCompleted::class);
    }
}
```

---

## 7. Factory Definitions

### Article Factory

**File**: `database/factories/ArticleFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Keyword;
use App\Models\PromptTemplate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'keyword_id' => Keyword::factory(),
            'prompt_template_id' => PromptTemplate::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(5, true),
            'excerpt' => $this->faker->paragraph(),
            'status' => Article::STATUS_COMPLETED,
            'word_count' => $this->faker->numberBetween(800, 1500),
            'reading_time_minutes' => $this->faker->numberBetween(3, 7),
            'seo_score' => $this->faker->numberBetween(60, 95),
            'created_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Article::STATUS_PENDING,
            'content' => null,
            'title' => null,
        ]);
    }

    public function generating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Article::STATUS_GENERATING,
            'generation_started_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Article::STATUS_FAILED,
            'generation_failed_at' => now(),
            'failure_reason' => 'Test failure',
        ]);
    }
}
```

### Keyword Factory

**File**: `database/factories/KeywordFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Keyword;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeywordFactory extends Factory
{
    protected $model = Keyword::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'keyword' => $this->faker->words(3, true),
            'search_volume' => $this->faker->numberBetween(100, 10000),
            'cpc' => $this->faker->randomFloat(2, 0.5, 5.0),
            'competition' => $this->faker->randomFloat(2, 0.1, 1.0),
            'intent' => $this->faker->randomElement([
                Keyword::INTENT_INFORMATIONAL,
                Keyword::INTENT_COMMERCIAL,
                Keyword::INTENT_TRANSACTIONAL,
            ]),
            'priority' => $this->faker->numberBetween(1, 10),
            'status' => Keyword::STATUS_ACTIVE,
        ];
    }

    public function unused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Keyword::STATUS_ACTIVE,
        ]);
    }
}
```

---

## 8. Mock Examples

### OpenAI Service Mock

```php
use App\Services\OpenAI\OpenAIService;
use Mockery;

$openAIMock = Mockery::mock(OpenAIService::class);

$openAIMock->shouldReceive('chatCompletion')
    ->with(Mockery::on(function ($params) {
        return isset($params['model'])
            && isset($params['messages'])
            && $params['model'] === 'gpt-4o';
    }))
    ->andReturn([
        'choices' => [
            [
                'message' => [
                    'content' => '# Test Article\n\nThis is a test article content generated by AI...'
                ]
            ]
        ],
        'usage' => [
            'total_tokens' => 1234,
            'prompt_tokens' => 100,
            'completion_tokens' => 1134,
        ]
    ]);
```

### Event Listener Mock

```php
use Illuminate\Support\Facades\Event;

Event::fake([
    \App\Events\ArticleGenerationStarted::class,
    \App\Events\ArticleGenerationCompleted::class,
]);

// ... perform action ...

Event::assertDispatched(\App\Events\ArticleGenerationStarted::class, function ($event) use ($article) {
    return $event->article->id === $article->id;
});
```

---

## 9. PHPUnit Configuration

**File**: `phpunit.xml` (partial)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app/Models</directory>
            <directory>app/Services/ArticleSteps</directory>
            <directory>app/Http/Controllers/Tenant/ArticleSteps</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
    </php>
</phpunit>
```

---

## Implementation Checklist

### Unit Tests
- [x] Article model tests (relationships, business logic)
- [x] Keyword model tests (scopes, helpers)
- [x] SeoStep model tests
- [x] Service unit tests with mocks

### Feature Tests
- [x] Article CRUD endpoints
- [x] Keyword management endpoints
- [x] Generation workflow
- [x] Authorization tests

### Integration Tests
- [x] Full article generation workflow
- [x] SEO optimization pipeline
- [x] Internal link suggestion
- [x] AB testing workflow

### Factories
- [x] Article factory with states
- [x] Keyword factory
- [x] PromptTemplate factory
- [x] All supporting factories

### Coverage
- [ ] Run coverage report
- [ ] Achieve 80%+ coverage target
- [ ] Document uncovered edge cases

---

**Test Coverage Target**: 80%+
**Total Tests**: 50+ tests
**Execution Time**: < 30 seconds

---

_AI Article Steps — Ainstein Platform_
_Laravel Multi-Tenant SaaS_
_Generated: October 2025_
