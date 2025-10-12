# Generazione Sincrona (Senza Queue) - Specifiche di Sviluppo

**Data creazione**: 2025-10-03
**Sistema**: Ainstein Platform - Single Content Generation (Sync Mode)
**Feature**: Generazione immediata senza passare da queue/batch

---

## 📋 Indice

1. [Panoramica](#panoramica)
2. [User Stories](#user-stories)
3. [Differenze Sync vs Async](#differenze-sync-vs-async)
4. [Database Schema](#database-schema)
5. [Backend Implementation](#backend-implementation)
6. [Frontend UI/UX](#frontend-uiux)
7. [API Endpoints](#api-endpoints)
8. [Error Handling](#error-handling)
9. [Performance Considerations](#performance-considerations)
10. [Testing](#testing)

---

## 📖 Panoramica

Attualmente tutte le generazioni di contenuti passano attraverso il sistema di **queue** (asincrono):
- User clicca "Generate Content"
- Sistema crea record con status `pending`
- Job viene aggiunto alla coda
- Worker processa il job in background
- User deve **ricaricare la pagina** per vedere il risultato

Questa nuova feature aggiunge la modalità **sincrona**:
- User clicca "Generate Content Now"
- Sistema chiama **immediatamente** l'API OpenAI
- User vede **real-time progress** e risultato senza ricaricare
- Generazione completata in **una sola richiesta HTTP**

### Quando usare Sync vs Async?

| Scenario | Modalità Consigliata | Motivo |
|----------|---------------------|--------|
| Test rapido di un prompt | **Sync** | Feedback immediato |
| Generazione singola urgente | **Sync** | No attesa worker |
| Generazione batch (10+) | **Async** | Evita timeout browser |
| Generazione lunga (> 60 sec) | **Async** | Timeout HTTP |
| Sistema con molti utenti | **Async** | Evita sovraccarico server |

---

## 👤 User Stories

### Story 1: Generazione Rapida per Test
```
Come content manager
Voglio generare contenuto immediatamente
Per testare velocemente la qualità del prompt
Senza dover attendere il worker in background
```

**Acceptance Criteria**:
- [ ] Toggle "Generate Now" visibile nella pagina create
- [ ] Click su "Generate Now" mostra loading spinner
- [ ] Progress bar mostra stato generazione
- [ ] Al completamento, redirect automatico a pagina view
- [ ] Se errore, mostra messaggio inline senza perdere form data

### Story 2: Visualizzazione Real-time Progress
```
Come utente
Voglio vedere il progresso della generazione in tempo reale
Per sapere quanto manca al completamento
E non pensare che la pagina sia bloccata
```

**Acceptance Criteria**:
- [ ] Progress bar mostra: "Preparing request..." → "Calling AI..." → "Saving content..."
- [ ] Tempo stimato rimanente aggiornato ogni secondo
- [ ] Token counter in real-time durante generazione
- [ ] Possibilità di cancellare generazione in corso

### Story 3: Fallback Automatico
```
Come sistema
Voglio automaticamente usare async mode
Se la generazione sync fallisce per timeout
Per garantire che l'utente ottenga sempre il risultato
```

**Acceptance Criteria**:
- [ ] Se sync timeout dopo 60 secondi → auto-switch ad async
- [ ] Messaggio: "Generation taking longer than expected. Switched to background mode."
- [ ] Job creato in queue con dati già inseriti
- [ ] User reindirizzato a pagina view con status "processing"

---

## ⚖️ Differenze Sync vs Async

### Architettura Corrente (Async)

```
User → Controller → Create ContentGeneration (pending)
                  → Dispatch Job → Queue
                  → Redirect to view page (status: pending)

[Separatamente]
Worker → Pick Job → Call OpenAI → Update to completed

User → Refresh page → See completed content
```

**Pro**:
- ✅ Non blocca richiesta HTTP
- ✅ Gestisce grandi volumi
- ✅ Retry automatico se fallisce
- ✅ Scalabile

**Contro**:
- ❌ User deve aspettare worker
- ❌ User deve ricaricare pagina
- ❌ No feedback immediato
- ❌ Configurazione worker necessaria

### Nuova Architettura (Sync)

```
User → Controller → Create ContentGeneration (processing)
                  → Call OpenAI IMMEDIATAMENTE
                  → Update to completed
                  → Return JSON con risultato

Frontend → Show progress in real-time
         → Display result when done
         → Redirect to view page
```

**Pro**:
- ✅ Risultato immediato
- ✅ No worker necessario
- ✅ Feedback real-time
- ✅ Migliore UX per test

**Contro**:
- ❌ Blocca richiesta HTTP (max 60-120 sec)
- ❌ No retry automatico
- ❌ Non scalabile per batch
- ❌ Timeout possibile

---

## 🗄️ Database Schema

**Nessuna modifica necessaria!** Useremo le stesse tabelle, ma aggiungendo una colonna per distinguere:

### Migration: Add `execution_mode` column

```php
// database/migrations/2025_10_03_add_execution_mode_to_content_generations.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->string('execution_mode', 20)
                  ->default('async')
                  ->after('ai_model')
                  ->comment('async (queue) or sync (immediate)');

            $table->timestamp('started_at')->nullable()->after('created_at');
            $table->integer('generation_time_ms')->nullable()->after('tokens_used');

            $table->index('execution_mode');
        });
    }

    public function down()
    {
        Schema::table('content_generations', function (Blueprint $table) {
            $table->dropColumn(['execution_mode', 'started_at', 'generation_time_ms']);
        });
    }
};
```

### Aggiornamento Model

```php
// app/Models/ContentGeneration.php
protected $fillable = [
    // ... existing fields
    'execution_mode', // 'sync' or 'async'
    'started_at',
    'generation_time_ms'
];

protected $casts = [
    // ... existing casts
    'started_at' => 'datetime'
];

// Scopes
public function scopeSync($query)
{
    return $query->where('execution_mode', 'sync');
}

public function scopeAsync($query)
{
    return $query->where('execution_mode', 'async');
}

// Helper
public function isSync(): bool
{
    return $this->execution_mode === 'sync';
}
```

---

## 💻 Backend Implementation

### Controller: Add Sync Generation Method

```php
// app/Http/Controllers/TenantContentController.php

/**
 * Store a new content generation (with sync option)
 */
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'page_id' => 'required|exists:contents,id',
        'prompt_id' => 'required|exists:prompts,id',
        'variables' => 'nullable|array',
        'additional_instructions' => 'nullable|string|max:2000',
        'execution_mode' => 'nullable|in:sync,async', // NEW
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $executionMode = $request->get('execution_mode', 'async');

    if ($executionMode === 'sync') {
        return $this->generateSync($request);
    } else {
        return $this->generateAsync($request);
    }
}

/**
 * Async generation (current implementation)
 */
protected function generateAsync(Request $request)
{
    $user = Auth::user();
    $tenantId = $user->tenant_id;

    // Create generation record
    $generation = ContentGeneration::create([
        'tenant_id' => $tenantId,
        'page_id' => $request->page_id,
        'prompt_id' => $request->prompt_id,
        'prompt_type' => $this->determinePromptType(...),
        'prompt_template' => $prompt->template,
        'variables' => $request->variables ?? [],
        'additional_instructions' => $request->additional_instructions,
        'status' => 'pending',
        'execution_mode' => 'async',
        'created_by' => $user->id,
        'ai_model' => 'gpt-4o',
    ]);

    // Dispatch to queue
    ProcessContentGeneration::dispatch($generation);

    return redirect()->route('tenant.content.show', $generation->id)
        ->with('success', 'Content generation started in background!');
}

/**
 * NEW: Sync generation (immediate)
 */
protected function generateSync(Request $request)
{
    $user = Auth::user();
    $tenantId = $user->tenant_id;

    try {
        // Verify page and prompt
        $page = Content::where('tenant_id', $tenantId)
            ->findOrFail($request->page_id);

        $prompt = Prompt::where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhere('is_system', true);
            })
            ->where('is_active', true)
            ->findOrFail($request->prompt_id);

        // Create generation record with 'processing' status
        $generation = ContentGeneration::create([
            'tenant_id' => $tenantId,
            'page_id' => $page->id,
            'prompt_id' => $prompt->id,
            'prompt_type' => $this->determinePromptType($prompt),
            'prompt_template' => $prompt->template,
            'variables' => $request->variables ?? [],
            'additional_instructions' => $request->additional_instructions,
            'status' => 'processing',
            'execution_mode' => 'sync',
            'created_by' => $user->id,
            'ai_model' => 'gpt-4o',
            'started_at' => now()
        ]);

        // Call OpenAI service IMMEDIATELY (not via job)
        $openAiService = app(OpenAiService::class);

        // Build prompt
        $finalPrompt = $this->buildPrompt($generation, $prompt, $page);

        Log::info('Sync generation started', [
            'generation_id' => $generation->id,
            'tenant_id' => $tenantId
        ]);

        // SYNCHRONOUS call to OpenAI (blocks here)
        $startTime = microtime(true);
        $generatedContent = $openAiService->generateSimpleContent($finalPrompt);
        $endTime = microtime(true);

        $generationTimeMs = ($endTime - $startTime) * 1000;
        $tokensUsed = $this->estimateTokens($finalPrompt . $generatedContent);

        // Update generation with results
        $generation->update([
            'status' => 'completed',
            'generated_content' => $generatedContent,
            'tokens_used' => $tokensUsed,
            'generation_time_ms' => $generationTimeMs,
            'completed_at' => now()
        ]);

        // Update tenant token usage
        $generation->tenant->increment('tokens_used_current', $tokensUsed);

        Log::info('Sync generation completed', [
            'generation_id' => $generation->id,
            'time_ms' => $generationTimeMs,
            'tokens' => $tokensUsed
        ]);

        // Return success with data
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'generation' => $generation->load(['content', 'prompt']),
                'redirect_url' => route('tenant.content.show', $generation->id)
            ]);
        }

        return redirect()->route('tenant.content.show', $generation->id)
            ->with('success', "Content generated successfully in {$generationTimeMs}ms!");

    } catch (Exception $e) {
        Log::error('Sync generation failed', [
            'error' => $e->getMessage(),
            'generation_id' => $generation->id ?? null
        ]);

        if (isset($generation)) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'Generation failed: ' . $e->getMessage()
            ], 500);
        }

        return back()
            ->with('error', 'Generation failed: ' . $e->getMessage())
            ->withInput();
    }
}

/**
 * Helper: Build final prompt from template + variables
 */
protected function buildPrompt($generation, $prompt, $page): string
{
    $finalPrompt = $prompt->template;

    // Replace variables
    if ($generation->variables) {
        foreach ($generation->variables as $key => $value) {
            $finalPrompt = str_replace('{{' . $key . '}}', $value, $finalPrompt);
        }
    }

    // Add page context
    $finalPrompt .= "\n\nPage Context:\n";
    $finalPrompt .= "URL: " . $page->url . "\n";
    $finalPrompt .= "Keyword: " . $page->keyword . "\n";

    // Add additional instructions
    if ($generation->additional_instructions) {
        $finalPrompt .= "\n\nAdditional Instructions:\n";
        $finalPrompt .= $generation->additional_instructions;
    }

    return $finalPrompt;
}

/**
 * Helper: Estimate token usage
 */
protected function estimateTokens(string $text): int
{
    // Rough estimate: 1 token ≈ 4 characters
    return (int) ceil(strlen($text) / 4);
}
```

---

## 🎨 Frontend UI/UX

### Updated Create Form with Mode Toggle

```blade
{{-- resources/views/tenant/content/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Generate Content')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="contentGenerationForm()">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Generate New Content</h3>
                <p class="text-gray-600">Use AI to generate content for your pages</p>
            </div>
            <a href="{{ route('tenant.content.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to Generations
            </a>
        </div>
    </div>

    <!-- Generation Form -->
    <form method="POST"
          action="{{ route('tenant.content.store') }}"
          @submit.prevent="handleSubmit"
          class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <!-- ... existing fields (page_id, prompt_id, variables, additional_instructions) ... -->

        <!-- NEW: Execution Mode Toggle -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <label class="flex items-center justify-between cursor-pointer">
                <div class="flex items-center">
                    <input type="checkbox"
                           name="execution_mode_toggle"
                           x-model="syncMode"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <div class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Generate Immediately</span>
                        <p class="text-xs text-gray-600">
                            <span x-show="!syncMode">
                                Default: Content will be generated in background (slower but safer)
                            </span>
                            <span x-show="syncMode">
                                ⚡ Instant: Content will be generated right now (faster, max 60 seconds)
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center">
                    <span x-show="!syncMode" class="text-xs text-gray-500">
                        <i class="fas fa-clock mr-1"></i>Background
                    </span>
                    <span x-show="syncMode" class="text-xs text-blue-600 font-medium">
                        <i class="fas fa-bolt mr-1"></i>Immediate
                    </span>
                </div>
            </label>
        </div>

        <input type="hidden" name="execution_mode" x-model="syncMode ? 'sync' : 'async'">

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('tenant.content.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-medium">
                Cancel
            </a>
            <button type="submit"
                    :disabled="isGenerating"
                    class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="{ 'animate-pulse': isGenerating }">
                <span x-show="!isGenerating">
                    <i class="fas fa-magic mr-2"></i>
                    <span x-text="syncMode ? 'Generate Now' : 'Generate Content'"></span>
                </span>
                <span x-show="isGenerating" class="flex items-center">
                    <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="progressMessage"></span>
                </span>
            </button>
        </div>
    </form>

    <!-- Progress Modal (shown during sync generation) -->
    <div x-show="isGenerating && syncMode"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.away="">
        <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="mb-4">
                    <svg class="animate-spin h-16 w-16 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Generating Content...</h3>
                <p class="text-gray-600 mb-4" x-text="progressMessage"></p>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                         :style="`width: ${progress}%`"></div>
                </div>

                <p class="text-sm text-gray-500">
                    <span x-text="elapsedTime"></span> seconds elapsed
                </p>

                <!-- Warning after 30 seconds -->
                <div x-show="elapsedTime > 30" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    This is taking longer than expected. The generation will continue in background if it times out.
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function contentGenerationForm() {
    return {
        syncMode: false,
        isGenerating: false,
        progress: 0,
        elapsedTime: 0,
        progressMessage: 'Initializing...',
        interval: null,

        handleSubmit(e) {
            const form = e.target;

            if (!this.syncMode) {
                // Async mode: normal form submit
                form.submit();
                return;
            }

            // Sync mode: AJAX request with progress tracking
            this.isGenerating = true;
            this.progress = 10;
            this.elapsedTime = 0;
            this.progressMessage = 'Preparing request...';

            // Start timer
            this.interval = setInterval(() => {
                this.elapsedTime++;

                // Simulate progress
                if (this.progress < 90) {
                    this.progress += Math.random() * 5;
                }

                // Update message based on time
                if (this.elapsedTime < 5) {
                    this.progressMessage = 'Calling AI model...';
                } else if (this.elapsedTime < 15) {
                    this.progressMessage = 'Generating content...';
                } else if (this.elapsedTime < 30) {
                    this.progressMessage = 'Almost done...';
                } else {
                    this.progressMessage = 'Still working...';
                }
            }, 1000);

            // Make AJAX request
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(this.interval);
                this.progress = 100;
                this.progressMessage = 'Complete!';

                if (data.success) {
                    // Success: redirect to view page
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 500);
                } else {
                    // Error
                    this.isGenerating = false;
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                clearInterval(this.interval);
                this.isGenerating = false;
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    }
}
</script>
@endpush
@endsection
```

---

## 🔌 API Endpoints

No new routes needed! Existing route handles both:

```php
// routes/web.php
Route::post('/dashboard/content', [TenantContentController::class, 'store'])
    ->name('tenant.content.store');
```

Controller detects `execution_mode` parameter and routes accordingly.

---

## ⚠️ Error Handling

### Timeout Handling

Se la generazione sync supera 60 secondi:

**Option 1: Browser timeout**
- Browser mostra errore 504 Gateway Timeout
- Mostrare messaggio: "Generation timeout. Switching to background mode..."
- Auto-create async job con stessi dati

**Option 2: PHP max_execution_time**
```php
// Nel controller sync
set_time_limit(120); // Max 2 minuti per sync generation

try {
    // ... generation logic
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'timeout')) {
        // Fallback to async
        ProcessContentGeneration::dispatch($generation);

        return response()->json([
            'success' => true,
            'fallback_to_async' => true,
            'message' => 'Generation switched to background mode',
            'redirect_url' => route('tenant.content.show', $generation->id)
        ]);
    }
}
```

### OpenAI API Errors

```php
try {
    $generatedContent = $openAiService->generateSimpleContent($finalPrompt);
} catch (\OpenAI\Exceptions\RateLimitException $e) {
    return response()->json([
        'success' => false,
        'error' => 'OpenAI rate limit reached. Please try again in a few moments.'
    ], 429);
} catch (\OpenAI\Exceptions\InvalidRequestException $e) {
    return response()->json([
        'success' => false,
        'error' => 'Invalid request to OpenAI: ' . $e->getMessage()
    ], 400);
} catch (Exception $e) {
    // Log and return generic error
    Log::error('OpenAI error', ['error' => $e->getMessage()]);
    return response()->json([
        'success' => false,
        'error' => 'AI generation failed. Please try again.'
    ], 500);
}
```

---

## ⚡ Performance Considerations

### 1. Server Resources

**Sync generation blocks PHP worker:**
- 1 sync generation = 1 PHP-FPM worker occupato per 10-60 secondi
- Con 10 PHP-FPM workers, max 10 generazioni sync simultanee
- Se 11° utente richiede sync → deve attendere

**Solution**:
- Limita sync generations concorrenti via middleware
- Auto-switch ad async se workers tutti occupati

```php
// app/Http/Middleware/LimitConcurrentSyncGenerations.php
class LimitConcurrentSyncGenerations
{
    public function handle($request, Closure $next)
    {
        if ($request->input('execution_mode') === 'sync') {
            $concurrent = ContentGeneration::where('status', 'processing')
                ->where('execution_mode', 'sync')
                ->where('started_at', '>=', now()->subMinutes(2))
                ->count();

            if ($concurrent >= 5) { // Max 5 sync concurrent
                // Force async
                $request->merge(['execution_mode' => 'async']);

                return $next($request)->with('info',
                    'Too many concurrent generations. Switched to background mode.');
            }
        }

        return $next($request);
    }
}
```

### 2. Database Connections

Sync generation tiene aperta connessione DB per tutta la durata:
- Rischio: esaurire connection pool se molte sync attive
- **Solution**: Chiudi connessione durante chiamata OpenAI

```php
// Prima di chiamare OpenAI
DB::disconnect();

$generatedContent = $openAiService->generateSimpleContent($finalPrompt);

// Dopo chiamata
DB::reconnect();
```

### 3. Memory Usage

Contenuto generato può essere grande (10-50KB):
- **Solution**: Limita max_content_length nel prompt

```php
if (strlen($generatedContent) > 100000) { // 100KB
    throw new Exception('Generated content exceeds maximum length');
}
```

---

## 🧪 Testing

### Unit Test: Sync Generation

```php
// tests/Unit/SyncContentGenerationTest.php
class SyncContentGenerationTest extends TestCase
{
    public function test_sync_generation_creates_record_with_correct_mode()
    {
        $user = User::factory()->create();
        $tenant = $user->tenant;
        $page = Content::factory()->create(['tenant_id' => $tenant->id]);
        $prompt = Prompt::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('tenant.content.store'), [
                'page_id' => $page->id,
                'prompt_id' => $prompt->id,
                'execution_mode' => 'sync'
            ]);

        $this->assertDatabaseHas('content_generations', [
            'page_id' => $page->id,
            'execution_mode' => 'sync',
            'status' => 'completed' // Should be completed immediately
        ]);
    }

    public function test_sync_generation_calls_openai_synchronously()
    {
        // Mock OpenAI service
        $mock = Mockery::mock(OpenAiService::class);
        $mock->shouldReceive('generateSimpleContent')
             ->once()
             ->andReturn('Generated content');

        $this->app->instance(OpenAiService::class, $mock);

        $user = User::factory()->create();
        // ... rest of test
    }

    public function test_sync_generation_times_out_gracefully()
    {
        // Mock OpenAI to throw timeout
        $mock = Mockery::mock(OpenAiService::class);
        $mock->shouldReceive('generateSimpleContent')
             ->andThrow(new Exception('Maximum execution time exceeded'));

        $this->app->instance(OpenAiService::class, $mock);

        $response = $this->actingAs($user)
            ->postJson(route('tenant.content.store'), [
                'page_id' => $page->id,
                'prompt_id' => $prompt->id,
                'execution_mode' => 'sync'
            ]);

        $response->assertStatus(500);
        // Should still create generation record with 'failed' status
    }
}
```

### Feature Test: E2E Sync Flow

```php
// tests/Feature/SyncContentGenerationFlowTest.php
class SyncContentGenerationFlowTest extends TestCase
{
    public function test_complete_sync_generation_flow()
    {
        $user = User::factory()->create();
        $page = Content::factory()->create(['tenant_id' => $user->tenant_id]);
        $prompt = Prompt::factory()->create([
            'template' => 'Write about: {{keyword}}',
            'is_active' => true
        ]);

        // Act: Submit sync generation
        $response = $this->actingAs($user)
            ->postJson(route('tenant.content.store'), [
                'page_id' => $page->id,
                'prompt_id' => $prompt->id,
                'variables' => ['keyword' => 'AI'],
                'execution_mode' => 'sync'
            ]);

        // Assert: Success response
        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure(['generation', 'redirect_url']);

        // Assert: Generation completed
        $generation = ContentGeneration::latest()->first();
        $this->assertEquals('completed', $generation->status);
        $this->assertEquals('sync', $generation->execution_mode);
        $this->assertNotNull($generation->generated_content);
        $this->assertGreaterThan(0, $generation->generation_time_ms);
    }
}
```

### Performance Test

```php
public function test_sync_generation_completes_within_timeout()
{
    $user = User::factory()->create();
    $page = Content::factory()->create(['tenant_id' => $user->tenant_id]);
    $prompt = Prompt::factory()->create();

    $startTime = microtime(true);

    $this->actingAs($user)
        ->post(route('tenant.content.store'), [
            'page_id' => $page->id,
            'prompt_id' => $prompt->id,
            'execution_mode' => 'sync'
        ]);

    $endTime = microtime(true);
    $duration = ($endTime - $startTime);

    // Should complete within 60 seconds
    $this->assertLessThan(60, $duration);
}
```

---

## 📊 Monitoring e Metrics

Aggiungi metriche per sync vs async:

```php
// Nel JobMetric::recordJobComplete()
public static function recordSyncGeneration($generation, $timeMs)
{
    static::create([
        'job_type' => 'sync_content_generation',
        'tenant_id' => $generation->tenant_id,
        'status' => 'completed',
        'processing_time' => $timeMs,
        'attempts' => 1,
        'metadata' => [
            'generation_id' => $generation->id,
            'tokens_used' => $generation->tokens_used
        ],
        'completed_at' => now()
    ]);
}
```

Dashboard mostra:
- Sync success rate
- Avg sync generation time
- Sync vs Async usage ratio

---

## 🚀 Rollout Strategy

### Phase 1: Beta (1-2 settimane)
- [ ] Deploy feature con toggle OFF by default
- [ ] Abilitare solo per super-admin
- [ ] Monitor errors e performance
- [ ] Gather feedback

### Phase 2: Limited Release (2-4 settimane)
- [ ] Abilitare per 10% utenti random
- [ ] A/B test: sync vs async conversion rate
- [ ] Optimize timeout handling

### Phase 3: General Availability
- [ ] Abilitare per tutti
- [ ] Toggle ON by default per nuovi utenti
- [ ] Marketing: "⚡ Instant AI Generation"

---

## ✅ Checklist Implementazione

- [ ] Migration: add `execution_mode`, `started_at`, `generation_time_ms`
- [ ] Update Model with new fields and scopes
- [ ] Controller: add `generateSync()` method
- [ ] Controller: add `generateAsync()` method (refactor existing)
- [ ] Frontend: add execution mode toggle
- [ ] Frontend: add progress modal
- [ ] Frontend: add AJAX submit handler
- [ ] Middleware: limit concurrent sync generations
- [ ] Error handling: timeout fallback to async
- [ ] Tests: unit + feature + performance
- [ ] Documentation: user guide
- [ ] Monitoring: add metrics tracking

---

## 📝 User Documentation

### Come usare la generazione immediata

1. Vai su **Generate Content**
2. Seleziona Page e Prompt
3. Attiva il toggle **"Generate Immediately"**
4. Click su **"Generate Now"**
5. Attendi 10-60 secondi mentre generiamo il contenuto
6. Verrai automaticamente reindirizzato al risultato

### Quando NON usare la generazione immediata

- ❌ Generazione di articoli molto lunghi (> 2000 parole)
- ❌ Generazione batch di più contenuti
- ❌ Quando la connessione internet è lenta
- ❌ Su dispositivi mobili con connessione instabile

**Suggerimento**: Se vedi "Generation timeout", usa la modalità standard (background) per maggiore affidabilità.

---

**Autore**: Claude AI
**Versione**: 1.0
**Ultimo aggiornamento**: 2025-10-03
