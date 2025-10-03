# AI Article Generator - Refactoring Dettagliato (Self-Contained)

## 📋 Panoramica

**Nome**: AI Article Generator
**Categoria**: COPY (Content Generation)
**Funzione**: Pipeline automatica per generazione articoli SEO-optimized completamente interna
**API**: Solo OpenAI (GPT-4o) + DALL-E
**Token tracking**: ✅ Obbligatorio (alto consumo)
**Dipendenze esterne**: ❌ NESSUNA - tutto interno!

### Pipeline Workflow Ottimizzata

```
Step 1: AI SERP Simulation
    ↓ GPT-4o genera PAA + related searches + competitor insights

Step 2: AI Content Research
    ↓ GPT-4o analizza topic e crea outline dettagliato

Step 3: AI Article Generation
    ↓ GPT-4o genera articolo HTML completo e ottimizzato

Step 4: AI SEO Optimization
    ↓ GPT-4o ottimizza per SEO + inserisce link interni

Step 5: Featured Image Generation
    ↓ DALL-E genera immagine copertina

Step 6: Pubblicazione + Archiviazione
```

---

## 🗄️ Database Schema

```php
// Migration: create_copy_article_generator_tables.php

Schema::create('copy_article_processes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('keyword');
    $table->text('briefing')->nullable(); // Contesto aggiuntivo dall'utente
    $table->json('kw_correlate')->nullable(); // AI generated
    $table->json('related_questions')->nullable(); // AI generated PAA
    $table->json('link_interni')->nullable(); // URL interni da inserire

    $table->string('target_audience')->nullable(); // es: "PMI italiane"
    $table->string('tone')->default('professionale'); // professionale, casual, tecnico
    $table->integer('word_count_target')->default(1500);

    $table->foreignId('content_id')->nullable()->constrained('contents');
    $table->enum('status', ['importato', 'processing', 'completato', 'pubblicato', 'errore'])->default('importato');
    $table->text('log')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'status']);
});

Schema::create('copy_article_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('process_id')->constrained('copy_article_processes')->onDelete('cascade');

    // Step outputs
    $table->json('step1_data')->nullable(); // AI SERP simulation
    $table->json('step2_data')->nullable(); // AI research + outline
    $table->longText('step3_data')->nullable(); // Articolo HTML grezzo
    $table->longText('step4_data')->nullable(); // Articolo SEO-optimized
    $table->string('step5_image_url')->nullable(); // Featured image URL

    // Token tracking dettagliato
    $table->integer('step1_tokens')->default(0);
    $table->integer('step2_tokens')->default(0);
    $table->integer('step3_tokens')->default(0);
    $table->integer('step4_tokens')->default(0);
    $table->integer('step5_tokens')->default(0);

    $table->timestamps();
});

Schema::create('copy_article_archive', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('origin_process_id');
    $table->foreignId('content_id')->constrained('contents');

    $table->string('keyword');
    $table->integer('final_word_count')->default(0);
    $table->json('metadata')->nullable();
    $table->integer('total_tokens_used')->default(0);

    $table->timestamps();
});

Schema::create('copy_custom_prompts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('name');
    $table->integer('step'); // 1-5
    $table->text('prompt');
    $table->text('variables_help')->nullable(); // Spiegazione placeholder disponibili
    $table->boolean('is_active')->default(false);

    $table->timestamps();

    $table->index(['tenant_id', 'step']);
});
```

---

## 📦 Models

```php
// app/Models/CopyArticleProcess.php
class CopyArticleProcess extends Model
{
    protected $fillable = [
        'tenant_id', 'keyword', 'briefing', 'kw_correlate', 'related_questions',
        'link_interni', 'target_audience', 'tone', 'word_count_target',
        'content_id', 'status', 'log'
    ];

    protected $casts = [
        'kw_correlate' => 'array',
        'related_questions' => 'array',
        'link_interni' => 'array',
        'word_count_target' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function steps(): HasOne
    {
        return $this->hasOne(CopyArticleStep::class, 'process_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function getTotalTokens(): int
    {
        if (!$this->steps) return 0;

        return $this->steps->step1_tokens +
               $this->steps->step2_tokens +
               $this->steps->step3_tokens +
               $this->steps->step4_tokens +
               $this->steps->step5_tokens;
    }

    public function getEstimatedCost(): float
    {
        // GPT-4o: $2.50 per 1M input tokens, $10 per 1M output
        // DALL-E: ~$0.04 per immagine
        $tokens = $this->getTotalTokens();
        return ($tokens / 1000000) * 5 + 0.04; // Stima media
    }
}

// app/Models/CopyArticleStep.php
class CopyArticleStep extends Model
{
    protected $fillable = [
        'process_id',
        'step1_data', 'step2_data', 'step3_data', 'step4_data', 'step5_image_url',
        'step1_tokens', 'step2_tokens', 'step3_tokens', 'step4_tokens', 'step5_tokens'
    ];

    protected $casts = [
        'step1_data' => 'array',
        'step2_data' => 'array',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(CopyArticleProcess::class, 'process_id');
    }
}
```

---

## 🔧 Services - Implementazione Completa Interna

### Step 1: AI SERP Simulation Service

```php
// app/Services/Tools/ArticleGenerator/AIResearchService.php

namespace App\Services\Tools\ArticleGenerator;

use App\Services\OpenAIService;
use App\Services\TokenTrackingService;

class AIResearchService
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    /**
     * Step 1: Simula analisi SERP con AI
     * Genera: PAA, related keywords, competitor insights
     */
    public function simulateSerpAnalysis(string $keyword, ?string $briefing = null): array
    {
        $prompt = "Agisci come un esperto SEO analyst. Analizza la keyword: \"{$keyword}\"

Contesto aggiuntivo: " . ($briefing ?? 'Non fornito') . "

Genera un'analisi SERP completa simulando i risultati di Google:

1. **People Also Ask (PAA)**: 10 domande frequenti che gli utenti cercano su questo topic
2. **Related Searches**: 8-10 keyword correlate semanticamente rilevanti
3. **Search Intent**: Identifica l'intento di ricerca (informational, commercial, transactional, navigational)
4. **Competitor Insights**: 3-5 punti chiave che i competitor coprono su questo topic
5. **Content Gaps**: Opportunità non coperte dai competitor

Rispondi SOLO in formato JSON:
{
  \"paa\": [\"domanda 1\", \"domanda 2\", ...],
  \"related_searches\": [\"keyword 1\", \"keyword 2\", ...],
  \"search_intent\": \"tipo intent\",
  \"competitor_insights\": [\"insight 1\", ...],
  \"content_gaps\": [\"gap 1\", ...]
}";

        $response = $this->openai->chat([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sei un esperto SEO analyst con accesso a dati SERP. Analizza keyword e genera insights basati su pattern di ricerca reali.'
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
        ]);

        $data = json_decode($response['choices'][0]['message']['content'], true);

        return [
            'data' => $data,
            'tokens_used' => $response['usage']['total_tokens'],
            'model_used' => $response['model'],
        ];
    }

    /**
     * Step 2: Research + Outline Generation
     */
    public function generateContentOutline(array $serpData, string $keyword, int $targetWordCount): array
    {
        $paa = implode("\n", $serpData['paa'] ?? []);
        $relatedKw = implode(", ", $serpData['related_searches'] ?? []);
        $insights = implode("\n", $serpData['competitor_insights'] ?? []);
        $gaps = implode("\n", $serpData['content_gaps'] ?? []);

        $prompt = "Crea un outline dettagliato per un articolo SEO-optimized su: \"{$keyword}\"

**Target word count**: {$targetWordCount} parole

**People Also Ask da coprire:**
{$paa}

**Related Keywords da integrare:**
{$relatedKw}

**Competitor Insights:**
{$insights}

**Content Gaps da sfruttare:**
{$gaps}

Genera un outline strutturato in formato JSON:
{
  \"title\": \"Titolo principale H1 (max 60 caratteri)\",
  \"meta_description\": \"Meta description SEO (max 160 caratteri)\",
  \"introduction\": {
    \"hook\": \"Apertura coinvolgente\",
    \"context\": \"Contesto e problema\",
    \"value_proposition\": \"Cosa imparerà il lettore\"
  },
  \"sections\": [
    {
      \"heading\": \"H2 heading\",
      \"subheadings\": [\"H3 subheading 1\", \"H3 subheading 2\"],
      \"key_points\": [\"punto chiave 1\", \"punto chiave 2\"],
      \"word_count\": 300
    }
  ],
  \"conclusion\": {
    \"summary\": \"Riassunto punti chiave\",
    \"cta\": \"Call to action\"
  },
  \"internal_linking_opportunities\": [\"anchor text 1\", \"anchor text 2\"]
}

L'outline deve:
- Coprire tutte le PAA
- Integrare naturalmente le related keywords
- Sfruttare i content gaps identificati
- Avere una struttura logica e SEO-friendly";

        $response = $this->openai->chat([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Sei un content strategist esperto in SEO content planning.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.8,
        ]);

        $outline = json_decode($response['choices'][0]['message']['content'], true);

        return [
            'outline' => $outline,
            'tokens_used' => $response['usage']['total_tokens'],
            'model_used' => $response['model'],
        ];
    }
}
```

### Step 3: AI Content Generation Service

```php
// app/Services/Tools/ArticleGenerator/AIContentGenerator.php

namespace App\Services\Tools\ArticleGenerator;

class AIContentGenerator
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    /**
     * Step 3: Genera articolo completo da outline
     */
    public function generateArticle(array $outline, string $keyword, string $tone = 'professionale'): array
    {
        $outlineJson = json_encode($outline, JSON_PRETTY_PRINT);

        $toneInstructions = match($tone) {
            'tecnico' => 'Usa terminologia tecnica, spiega concetti complessi, target: esperti del settore',
            'casual' => 'Linguaggio semplice e diretto, esempi pratici, target: pubblico generale',
            'professionale' => 'Equilibrio tra autorevolezza e accessibilità, target: business professionals',
            default => 'Tono professionale',
        };

        $prompt = "Scrivi un articolo completo in HTML seguendo questo outline:

{$outlineJson}

**Keyword principale**: {$keyword}

**Tono**: {$toneInstructions}

**Istruzioni di scrittura:**
1. Genera HTML valido e pulito (no <html>, <head>, <body> tags)
2. Usa tag semantici: <h1>, <h2>, <h3>, <p>, <ul>, <ol>, <strong>, <em>
3. Inserisci la keyword principale naturalmente (densità 1-2%)
4. Integra le related keywords nel testo in modo naturale
5. Rispondi a tutte le PAA nel contenuto
6. Aggiungi esempi pratici e dati quando possibile
7. Usa liste puntate per migliorare readability
8. Includi call-to-action nella conclusione

**IMPORTANTE**:
- NO markdown, solo HTML
- Mantieni i tag chiusi correttamente
- Evita ripetizioni
- Scrivi paragrafi di 3-5 righe max
- Usa grassetto per evidenziare concetti chiave

Restituisci SOLO l'HTML dell'articolo, senza prefissi o spiegazioni.";

        $response = $this->openai->chat([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sei un esperto copywriter SEO. Scrivi articoli ottimizzati, coinvolgenti e informativi in HTML perfettamente formattato.'
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.85,
            'max_tokens' => 6000,
        ]);

        $articleHtml = $response['choices'][0]['message']['content'];

        // Cleanup HTML
        $articleHtml = $this->cleanupHtml($articleHtml);

        return [
            'html' => $articleHtml,
            'word_count' => str_word_count(strip_tags($articleHtml)),
            'tokens_used' => $response['usage']['total_tokens'],
            'model_used' => $response['model'],
        ];
    }

    protected function cleanupHtml(string $html): string
    {
        // Rimuovi markdown artifacts
        $html = preg_replace('/^```html\s*|\s*```$/m', '', $html);

        // Rimuovi tag wrapper se presenti
        $html = preg_replace('#^<(?:html|body)[^>]*>|</(?:html|body)>$#i', '', $html);

        // Rimuovi smart quotes e altri caratteri speciali
        $html = str_replace(['"', '"', ''', '''], ['"', '"', "'", "'"], $html);

        // Rimuovi spazi multipli
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }
}
```

### Step 4: AI SEO Optimization Service

```php
// app/Services/Tools/ArticleGenerator/AISEOOptimizer.php

namespace App\Services\Tools\ArticleGenerator;

class AISEOOptimizer
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    /**
     * Step 4: Ottimizza articolo per SEO + inserisce link interni
     */
    public function optimizeForSEO(string $articleHtml, string $keyword, array $internalLinks = []): array
    {
        $linksContext = '';
        if (!empty($internalLinks)) {
            $linksContext = "\n\n**Link interni da inserire contestualmente:**\n";
            foreach ($internalLinks as $link) {
                $linksContext .= "- {$link}\n";
            }
        }

        $prompt = "Ottimizza questo articolo HTML per la SEO senza alterarne il significato:

**Keyword principale**: {$keyword}

**Articolo da ottimizzare:**
{$articleHtml}
{$linksContext}

**Ottimizzazioni da applicare:**

1. **Internal Linking**:
   - Inserisci i link interni forniti in anchor text naturali e contestuali
   - Usa varianti della keyword per gli anchor text
   - Massimo 1 link ogni 200-300 parole

2. **On-Page SEO**:
   - Verifica keyword in H1, primi 100 parole, URL-friendly headings
   - Aggiungi attributi alt descrittivi a eventuali immagini (anche placeholder)
   - Ottimizza structure: intro → body → conclusion

3. **Readability**:
   - Dividi paragrafi lunghi (max 5 righe)
   - Aggiungi sottotitoli se mancano
   - Usa liste per enumerazioni

4. **Semantic SEO**:
   - Integra sinonimi e varianti della keyword
   - Usa schema markup hints nei commenti HTML (<!-- schema.org/Article -->)

Restituisci l'HTML ottimizzato SENZA markdown o spiegazioni, solo il codice HTML finale.";

        $response = $this->openai->chat([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sei un SEO specialist tecnico. Ottimizza HTML per massimizzare ranking mantenendo naturalezza e valore per l\'utente.'
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.6,
            'max_tokens' => 6000,
        ]);

        $optimizedHtml = $response['choices'][0]['message']['content'];
        $optimizedHtml = $this->cleanupHtml($optimizedHtml);

        return [
            'html' => $optimizedHtml,
            'internal_links_added' => substr_count($optimizedHtml, '<a href='),
            'tokens_used' => $response['usage']['total_tokens'],
            'model_used' => $response['model'],
        ];
    }

    protected function cleanupHtml(string $html): string
    {
        $html = preg_replace('/^```html\s*|\s*```$/m', '', $html);
        $html = preg_replace('#^<(?:html|body)[^>]*>|</(?:html|body)>$#i', '', $html);
        $html = str_replace(['"', '"', ''', '''], ['"', '"', "'", "'"], $html);
        return trim($html);
    }
}
```

### Step 5: DALL-E Image Generation Service

```php
// app/Services/Tools/ArticleGenerator/AIImageGenerator.php

namespace App\Services\Tools\ArticleGenerator;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AIImageGenerator
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    /**
     * Step 5: Genera featured image con DALL-E
     */
    public function generateFeaturedImage(string $articleTitle, string $keyword): array
    {
        // Crea prompt ottimizzato per DALL-E
        $imagePrompt = $this->buildImagePrompt($articleTitle, $keyword);

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $imagePrompt,
                'size' => '1792x1024', // 16:9 ratio ottimale per blog
                'quality' => 'standard',
                'n' => 1,
            ]);

        if ($response->failed()) {
            throw new \Exception('DALL-E generation failed: ' . $response->body());
        }

        $data = $response->json();
        $imageUrl = $data['data'][0]['url'];

        // Download e salva immagine localmente
        $localPath = $this->saveImageLocally($imageUrl, $keyword);

        // Track tokens (DALL-E standard quality ~1000 token equivalent)
        return [
            'url' => $imageUrl,
            'local_path' => $localPath,
            'prompt_used' => $imagePrompt,
            'tokens_used' => 1000, // Equivalente fisso per DALL-E
        ];
    }

    protected function buildImagePrompt(string $title, string $keyword): string
    {
        // Rimuovi HTML tags dal titolo
        $cleanTitle = strip_tags($title);

        return "Create a professional, modern featured image for a blog article.

Article topic: {$cleanTitle}
Main keyword: {$keyword}

Style requirements:
- Clean, minimalist design
- Professional business aesthetic
- Relevant iconography or abstract representation
- Vibrant but professional color palette
- Suitable for SEO blog post header
- NO text overlay
- High quality, sharp details

The image should visually represent the concept of '{$keyword}' in an appealing, modern way.";
    }

    protected function saveImageLocally(string $imageUrl, string $keyword): string
    {
        // Download immagine
        $imageContent = file_get_contents($imageUrl);

        // Genera nome file unico
        $filename = 'featured-' . Str::slug($keyword) . '-' . time() . '.png';

        // Salva in storage
        Storage::disk('public')->put('featured-images/' . $filename, $imageContent);

        return 'featured-images/' . $filename;
    }
}
```

### Main Pipeline Orchestrator

```php
// app/Services/Tools/ArticleGenerator/ArticlePipelineProcessor.php

namespace App\Services\Tools\ArticleGenerator;

use App\Models\CopyArticleProcess;
use App\Models\Content;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArticlePipelineProcessor
{
    public function __construct(
        protected AIResearchService $researcher,
        protected AIContentGenerator $generator,
        protected AISEOOptimizer $seoOptimizer,
        protected AIImageGenerator $imageGenerator,
        protected TokenTrackingService $tokenTracker
    ) {}

    /**
     * Step 1: AI SERP Simulation
     */
    public function step1Research(CopyArticleProcess $process): void
    {
        $result = $this->researcher->simulateSerpAnalysis(
            $process->keyword,
            $process->briefing
        );

        // Salva PAA e related keywords nel process
        $process->update([
            'related_questions' => $result['data']['paa'] ?? [],
            'kw_correlate' => $result['data']['related_searches'] ?? [],
        ]);

        // Salva dati completi negli step
        $process->steps()->updateOrCreate(
            ['process_id' => $process->id],
            [
                'step1_data' => $result['data'],
                'step1_tokens' => $result['tokens_used'],
            ]
        );

        // Track tokens
        $this->tokenTracker->track(
            $process->tenant_id,
            $result['tokens_used'],
            $result['model_used'],
            'article_gen_step1_research',
            ['process_id' => $process->id]
        );

        Log::info("Step 1 completato", [
            'process_id' => $process->id,
            'paa_count' => count($result['data']['paa'] ?? []),
            'tokens' => $result['tokens_used'],
        ]);
    }

    /**
     * Step 2: Outline Generation
     */
    public function step2Outline(CopyArticleProcess $process): void
    {
        $serpData = $process->steps->step1_data;

        $result = $this->researcher->generateContentOutline(
            $serpData,
            $process->keyword,
            $process->word_count_target
        );

        $process->steps->update([
            'step2_data' => $result['outline'],
            'step2_tokens' => $result['tokens_used'],
        ]);

        $this->tokenTracker->track(
            $process->tenant_id,
            $result['tokens_used'],
            $result['model_used'],
            'article_gen_step2_outline'
        );

        Log::info("Step 2 completato", [
            'process_id' => $process->id,
            'sections_count' => count($result['outline']['sections'] ?? []),
            'tokens' => $result['tokens_used'],
        ]);
    }

    /**
     * Step 3: Article Generation
     */
    public function step3Generation(CopyArticleProcess $process): void
    {
        $outline = $process->steps->step2_data;

        $result = $this->generator->generateArticle(
            $outline,
            $process->keyword,
            $process->tone
        );

        $process->steps->update([
            'step3_data' => $result['html'],
            'step3_tokens' => $result['tokens_used'],
        ]);

        $this->tokenTracker->track(
            $process->tenant_id,
            $result['tokens_used'],
            $result['model_used'],
            'article_gen_step3_content'
        );

        Log::info("Step 3 completato", [
            'process_id' => $process->id,
            'word_count' => $result['word_count'],
            'tokens' => $result['tokens_used'],
        ]);
    }

    /**
     * Step 4: SEO Optimization
     */
    public function step4SEOOptimization(CopyArticleProcess $process): void
    {
        $articleHtml = $process->steps->step3_data;
        $internalLinks = $process->link_interni ?? [];

        // Se nessun link fornito, recupera ultimi 10 contenuti pubblicati
        if (empty($internalLinks)) {
            $internalLinks = $this->getAvailableInternalLinks($process->tenant_id);
        }

        $result = $this->seoOptimizer->optimizeForSEO(
            $articleHtml,
            $process->keyword,
            $internalLinks
        );

        $process->steps->update([
            'step4_data' => $result['html'],
            'step4_tokens' => $result['tokens_used'],
        ]);

        $this->tokenTracker->track(
            $process->tenant_id,
            $result['tokens_used'],
            $result['model_used'],
            'article_gen_step4_seo'
        );

        Log::info("Step 4 completato", [
            'process_id' => $process->id,
            'internal_links_added' => $result['internal_links_added'],
            'tokens' => $result['tokens_used'],
        ]);
    }

    /**
     * Step 5: Featured Image Generation
     */
    public function step5ImageGeneration(CopyArticleProcess $process): void
    {
        $outline = $process->steps->step2_data;
        $title = $outline['title'] ?? $process->keyword;

        $result = $this->imageGenerator->generateFeaturedImage(
            $title,
            $process->keyword
        );

        $process->steps->update([
            'step5_image_url' => $result['local_path'],
            'step5_tokens' => $result['tokens_used'],
        ]);

        $this->tokenTracker->track(
            $process->tenant_id,
            $result['tokens_used'],
            'dall-e-3',
            'article_gen_step5_image'
        );

        Log::info("Step 5 completato", [
            'process_id' => $process->id,
            'image_path' => $result['local_path'],
        ]);
    }

    /**
     * Step 6: Pubblicazione
     */
    public function step6Publish(CopyArticleProcess $process): void
    {
        $finalHtml = $process->steps->step4_data ?? $process->steps->step3_data;

        if (!$finalHtml) {
            throw new \Exception('Nessun contenuto disponibile per pubblicazione');
        }

        $outline = $process->steps->step2_data;
        $title = $this->extractTitle($finalHtml) ?: ($outline['title'] ?? $process->keyword);

        // Crea content
        $content = Content::create([
            'tenant_id' => $process->tenant_id,
            'url_path' => Str::slug($title),
            'keyword' => $process->keyword,
            'html_content' => $finalHtml,
            'meta_description' => $outline['meta_description'] ?? null,
            'status' => 'active',
            'language' => 'it',
        ]);

        // Allega featured image se disponibile
        if ($process->steps->step5_image_url) {
            $content->update([
                'featured_image' => $process->steps->step5_image_url,
            ]);
        }

        // Aggiorna process
        $process->update([
            'content_id' => $content->id,
            'status' => 'pubblicato',
        ]);

        // Archivia
        \App\Models\CopyArticleArchive::create([
            'tenant_id' => $process->tenant_id,
            'origin_process_id' => $process->id,
            'content_id' => $content->id,
            'keyword' => $process->keyword,
            'final_word_count' => str_word_count(strip_tags($finalHtml)),
            'total_tokens_used' => $process->getTotalTokens(),
            'metadata' => [
                'related_questions' => $process->related_questions,
                'kw_correlate' => $process->kw_correlate,
                'tone' => $process->tone,
            ],
        ]);

        Log::info("Step 6 completato - Pubblicato", [
            'process_id' => $process->id,
            'content_id' => $content->id,
            'total_tokens' => $process->getTotalTokens(),
        ]);
    }

    protected function extractTitle(string $html): ?string
    {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            return strip_tags($match[1]);
        }
        return null;
    }

    protected function getAvailableInternalLinks(int $tenantId, int $limit = 10): array
    {
        return Content::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->latest()
            ->limit($limit)
            ->pluck('url_path')
            ->map(fn($path) => url($path))
            ->toArray();
    }
}
```

---

## 🎮 Controller (invariato nella logica, usa i nuovi step)

```php
// Controller methods aggiornati per i nuovi step names:

public function runStep(Request $request, CopyArticleProcess $process, int $step)
{
    $this->authorize('update', $process);

    try {
        $process->update(['status' => 'processing']);

        match ($step) {
            1 => $this->processor->step1Research($process),
            2 => $this->processor->step2Outline($process),
            3 => $this->processor->step3Generation($process),
            4 => $this->processor->step4SEOOptimization($process),
            5 => $this->processor->step5ImageGeneration($process),
            6 => $this->processor->step6Publish($process),
            default => throw new \Exception('Step non valido'),
        };

        if ($step === 6) {
            $process->update(['status' => 'pubblicato']);
        } else {
            $process->update(['status' => 'completato']);
        }

        return response()->json([
            'success' => true,
            'message' => "Step {$step} completato!",
            'tokens_used' => match($step) {
                1 => $process->steps->step1_tokens,
                2 => $process->steps->step2_tokens,
                3 => $process->steps->step3_tokens,
                4 => $process->steps->step4_tokens,
                5 => $process->steps->step5_tokens,
                default => 0,
            },
        ]);

    } catch (\Exception $e) {
        $process->update([
            'status' => 'errore',
            'log' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 422);
    }
}
```

---

## 🎨 UI Aggiornata (sintesi modifiche)

Aggiornare la tabella step da 4 a 6 colonne:

```blade
<th>Step 1 (Research)</th>
<th>Step 2 (Outline)</th>
<th>Step 3 (Content)</th>
<th>Step 4 (SEO)</th>
<th>Step 5 (Image)</th>
<th>Step 6 (Publish)</th>
```

---

## ✅ Checklist Implementazione Rivista

### Backend
- [ ] Migration 4 tabelle (aggiornata)
- [ ] Models con relationships
- [ ] **AIResearchService** (Step 1 + 2) - NUOVO
- [ ] **AIContentGenerator** (Step 3) - NUOVO
- [ ] **AISEOOptimizer** (Step 4) - NUOVO
- [ ] **AIImageGenerator** (Step 5) - NUOVO
- [ ] **ArticlePipelineProcessor** orchestrator
- [ ] Controller con 6 step
- [ ] Custom prompts CRUD
- [ ] Token tracking completo
- [ ] ❌ RIMOSSO: SerpAPI dependency
- [ ] ❌ RIMOSSO: Scraping API dependency
- [ ] ❌ RIMOSSO: External endpoints

### Admin Settings
- [ ] ❌ RIMOSSO: SerpAPI key
- [ ] ❌ RIMOSSO: Scraping API URL
- [ ] OpenAI API key (già presente)
- [ ] DALL-E settings (già presente)

### Vantaggi Soluzione Interna

✅ **Zero dipendenze esterne** (solo OpenAI)
✅ **Maggior controllo qualità** (AI genera tutto)
✅ **Nessun rate limit esterno** (solo OpenAI)
✅ **Costi prevedibili** (solo token OpenAI)
✅ **Personalizzazione totale** (custom prompts)
✅ **Migliore SEO** (AI ottimizzata per intent)
✅ **Scalabilità** (no API terze)

**Stima Token Totale per Articolo**:
- Step 1 (Research): ~1000 token
- Step 2 (Outline): ~1500 token
- Step 3 (Content): ~4000 token
- Step 4 (SEO): ~2000 token
- Step 5 (Image): ~1000 token (equivalente DALL-E)
- **TOTALE: ~9500 token** (~$0.10 per articolo con GPT-4o)
