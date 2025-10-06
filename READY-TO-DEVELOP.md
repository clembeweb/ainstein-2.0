# ✅ SYSTEM READY - Next Development Task

**Last Updated**: 6 Ottobre 2025
**Status**: 📋 Layer 1.2 Complete - Ready for Layer 2.1 (OpenAI Service)

---

## 🎯 NEXT TASK: OpenAI Service Base

**Priority**: P0 CRITICAL
**Estimated Time**: 2 giorni (16h)
**Spec Document**: [`docs/01-project-overview/DEVELOPMENT-ROADMAP.md` - Lines 110-142](docs/01-project-overview/DEVELOPMENT-ROADMAP.md#L110-L142)

---

## ✅ COMPLETATO: Admin Settings Centralization

**Commit**: `84bccae`
**Status**: ✅ Complete
**Features**:
- ✅ Migration con 23 nuove colonne (OAuth, OpenAI, Stripe, Email, Advanced)
- ✅ PlatformSetting model con encryption & caching
- ✅ Admin UI con 6 tabs (OAuth, OpenAI, Stripe, Email, Branding, Advanced)
- ✅ Logo upload con auto-resize (256px, 64px, 32px)
- ✅ OpenAiService refactorato per usare `PlatformSetting::get()`
- ✅ Test completi (6 categorie, tutti passati)

---

## 🚀 QUICK START - OpenAI Service Base

Quando digiti **"prosegui"** in una nuova chat, l'AI eseguirà automaticamente:

1. ✅ Legge `START-HERE.md` per context
2. ✅ Legge `.project-status` per task corrente
3. ✅ Identifica: **Layer 2.1 - OpenAI Service Base (P0 CRITICAL)**
4. ✅ Apre spec: `DEVELOPMENT-ROADMAP.md` (lines 110-142)
5. ✅ Inizia implementazione

---

## 📋 IMPLEMENTATION CHECKLIST

### Day 1: Service Core (8h)

#### 1. Service Base Class (3h) - P0
```bash
cd ainstein-laravel
mkdir -p app/Services/AI
php artisan make:service AI/OpenAIService
```

**Tasks**:
- [ ] Create `app/Services/AI/OpenAIService.php`
- [ ] Implement methods:
  - `chat(array $messages, string $model = null, array $options = [])`
  - `completion(string $prompt, string $model = null, array $options = [])`
  - `embeddings(string|array $text)`
  - `parseJSON(array $messages)` // Force JSON response
- [ ] Integration con token tracking esistente
  - `trackTokenUsage($tenantId, $tokens, $model, $source, $metadata)`
- [ ] Use `PlatformSetting::get()` per API key/model/temperature

#### 2. Error Handling & Retry Logic (3h) - P0
**Tasks**:
- [ ] Rate limit handling (exponential backoff)
- [ ] Timeout management (30s default)
- [ ] OpenAI error codes mapping
- [ ] Retry logic (max 3 attempts)
- [ ] Fallback to MockOpenAiService se API key fake

#### 3. Configuration File (2h) - P1
**Tasks**:
- [ ] Create `config/ai.php`
- [ ] Default models per use case:
  - `campaigns` → gpt-4o-mini
  - `articles` → gpt-4o
  - `seo` → gpt-4o-mini
- [ ] Temperature settings per use case
- [ ] Max tokens per use case

### Day 2: Integration & Testing (8h)

#### 4. Token Tracking Integration (2h) - P0
**Tasks**:
- [ ] Extend `TokenTracking` model se necessario
- [ ] Implement `trackUsage()` method
- [ ] Add `source` field (campaign, article, seo, etc.)
- [ ] Add `metadata` JSON field per context

#### 5. Testing Suite (4h) - P1
**Tasks**:
- [ ] Unit test: `OpenAIServiceTest.php`
- [ ] Test chat completion
- [ ] Test JSON response parsing
- [ ] Test token tracking
- [ ] Test retry logic (mock rate limit)
- [ ] Test error handling
- [ ] Test fallback to MockService

#### 6. Documentation (2h) - P1
**Tasks**:
- [ ] PHPDoc completo per tutti i metodi
- [ ] Usage examples in comments
- [ ] Update `.project-status`
- [ ] Create `docs/02-tools-refactoring/OPENAI-SERVICE-GUIDE.md`

---

## 🎯 SUCCESS CRITERIA

✅ **Chat completion** funziona con modelli multipli
✅ **JSON parsing** robusto e testato
✅ **Token tracking** salvato correttamente in DB
✅ **Retry logic** testato con rate limit simulato
✅ **Error handling** gestisce tutti i casi edge
✅ **Mock service** si attiva automaticamente con fake keys
✅ **Configuration** centralizzata in `config/ai.php`
✅ **Test coverage** > 80% per OpenAIService

---

## 📊 TECHNICAL SPECIFICATIONS

### Service Methods Signature

```php
namespace App\Services\AI;

use App\Models\Tenant;
use OpenAI;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIService
{
    /**
     * Chat completion with message history
     *
     * @param array $messages [['role' => 'user', 'content' => '...']]
     * @param string|null $model Override default model
     * @param array $options ['temperature' => 0.7, 'max_tokens' => 2000]
     * @return array ['content' => '...', 'tokens' => 123, 'model' => 'gpt-4o']
     */
    public function chat(array $messages, string $model = null, array $options = []): array;

    /**
     * Simple completion (single prompt)
     *
     * @param string $prompt
     * @param string|null $model
     * @param array $options
     * @return array
     */
    public function completion(string $prompt, string $model = null, array $options = []): array;

    /**
     * Generate embeddings for semantic search
     *
     * @param string|array $text
     * @return array
     */
    public function embeddings(string|array $text): array;

    /**
     * Force JSON response from OpenAI
     *
     * @param array $messages
     * @return array Parsed JSON response
     */
    public function parseJSON(array $messages): array;

    /**
     * Track token usage for billing
     *
     * @param string $tenantId
     * @param int $tokens
     * @param string $model
     * @param string $source (campaign, article, seo, etc.)
     * @param array $metadata
     * @return void
     */
    protected function trackTokenUsage(string $tenantId, int $tokens, string $model, string $source, array $metadata = []): void;

    /**
     * Retry logic with exponential backoff
     *
     * @param callable $callback
     * @param int $maxAttempts
     * @return mixed
     */
    protected function retry(callable $callback, int $maxAttempts = 3): mixed;
}
```

### config/ai.php Structure

```php
return [
    // Default models per use case
    'models' => [
        'campaigns' => env('AI_MODEL_CAMPAIGNS', 'gpt-4o-mini'),
        'articles' => env('AI_MODEL_ARTICLES', 'gpt-4o'),
        'seo' => env('AI_MODEL_SEO', 'gpt-4o-mini'),
        'default' => env('AI_MODEL_DEFAULT', 'gpt-4o-mini'),
    ],

    // Temperature settings
    'temperature' => [
        'campaigns' => 0.8, // More creative for ads
        'articles' => 0.7,  // Balanced for content
        'seo' => 0.5,       // More deterministic
        'default' => 0.7,
    ],

    // Max tokens
    'max_tokens' => [
        'campaigns' => 1000,
        'articles' => 4000,
        'seo' => 2000,
        'default' => 2000,
    ],

    // Retry settings
    'retry' => [
        'max_attempts' => 3,
        'backoff_multiplier' => 2, // seconds
    ],

    // Timeout (seconds)
    'timeout' => 30,

    // Rate limiting
    'rate_limit' => [
        'requests_per_minute' => 60,
        'tokens_per_day' => 100000,
    ],
];
```

---

## 📁 FILES TO CREATE/MODIFY

### New Files
1. `app/Services/AI/OpenAIService.php` (core service)
2. `config/ai.php` (configuration)
3. `tests/Unit/Services/AI/OpenAIServiceTest.php`
4. `docs/02-tools-refactoring/OPENAI-SERVICE-GUIDE.md`

### Files to Modify
1. `app/Services/OpenAiService.php` → **Deprecare** in favore del nuovo
2. `database/migrations/XXXX_add_metadata_to_token_tracking.php` (se necessario)

---

## 🔧 DEPENDENCIES

### Existing Packages (already installed)
- ✅ `openai-php/client` (already in composer.json)
- ✅ Laravel HTTP Client
- ✅ Redis (for rate limiting)

### No New Dependencies Needed

---

## 📝 NOTES FOR DEVELOPER

### Important Reminders
1. **Backward Compatibility**: Il vecchio `OpenAiService.php` deve continuare a funzionare
2. **Migration Path**: Refactor graduale, non breaking changes
3. **Testing**: Test PRIMA dell'implementazione (TDD approach)
4. **Token Tracking**: Ogni chiamata DEVE tracciare token usage
5. **Rate Limiting**: Implementare throttling per evitare ban OpenAI
6. **Error Messages**: User-friendly, non esporre dettagli tecnici
7. **Logging**: Log INFO per success, WARNING per retry, ERROR per failures

### Testing Workflow
```bash
# 1. Create service
php artisan make:service AI/OpenAIService

# 2. Create test
php artisan make:test Services/AI/OpenAIServiceTest --unit

# 3. Run tests
php artisan test --filter=OpenAIServiceTest

# 4. Test in Tinker
php artisan tinker
>>> use App\Services\AI\OpenAIService;
>>> $service = app(OpenAIService::class);
>>> $result = $service->chat([['role' => 'user', 'content' => 'Hello']]);
>>> print_r($result);

# 5. Test token tracking
>>> \App\Models\TokenTracking::latest()->first();

# 6. Test error handling
>>> $service->chat([['role' => 'user', 'content' => str_repeat('a', 100000)]]);
```

---

## 🚨 CRITICAL PATH

This task is **P0 CRITICAL** because:

1. **Foundation for All Tools**: 4 su 6 tool usano OpenAI
2. **Token Billing**: Accurato tracking necessario per billing
3. **Performance**: Retry logic evita fallimenti transient
4. **User Experience**: Error handling migliora UX
5. **Scalability**: Rate limiting previene ban API

**Do NOT proceed to Campaign Generator** until questo è production-ready!

---

## 📚 REFERENCE DOCUMENTS

1. **Roadmap**: [`docs/01-project-overview/DEVELOPMENT-ROADMAP.md`](docs/01-project-overview/DEVELOPMENT-ROADMAP.md) (Lines 110-142)
2. **OpenAI API Docs**: [https://platform.openai.com/docs](https://platform.openai.com/docs)
3. **OpenAI PHP Client**: [https://github.com/openai-php/client](https://github.com/openai-php/client)

---

## 🎓 AFTER THIS TASK

Once OpenAI Service Base is complete:

**Next Task**: Layer 3.1 - Campaign Generator (Database & Models)
**Estimated Time**: 1 giorno (8h)
**Reference**: `DEVELOPMENT-ROADMAP.md` Lines 228-255

---

## ✅ COMPLETION VERIFICATION

Before marking this task as complete, verify:

- [ ] `OpenAIService::chat()` funziona con gpt-4o-mini
- [ ] `OpenAIService::chat()` funziona con gpt-4o
- [ ] `OpenAIService::parseJSON()` restituisce JSON valido
- [ ] Token usage salvato in `token_tracking` table
- [ ] Retry logic testato (simula rate limit)
- [ ] Error handling testato (API key invalida, timeout, ecc.)
- [ ] MockOpenAiService si attiva con fake key
- [ ] `config/ai.php` creato e funzionante
- [ ] Test coverage > 80%
- [ ] PHPDoc completo
- [ ] Nessuna regressione su `OpenAiService.php` esistente

---

**🚀 Ready to start - Digita "proseguiamo" per iniziare Layer 2.1!**

---

_Created: 6 Ottobre 2025_
_Task Priority: P0 CRITICAL_
_Estimated Time: 2 giorni (16h)_
_Blocks: Campaign Generator, Article Generator, SEO Tools_
