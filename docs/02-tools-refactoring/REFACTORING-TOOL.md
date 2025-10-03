# Linee Guida per Refactoring Tool WordPress → Ainstein Laravel

## 🎯 Obiettivi del Refactoring

Questo documento fornisce le linee guida generiche per trasformare qualsiasi prototipo WordPress in un tool compatibile con la piattaforma Ainstein Laravel, garantendo:

1. **Multi-tenancy**: Isolamento completo dati tra tenant
2. **Token-based billing**: Tracciamento consumo per fatturazione
3. **Centralizzazione API**: Admin settings per tutte le credenziali
4. **Scalabilità**: Background jobs, queue, caching
5. **Sicurezza**: CSRF, policies, encrypted credentials
6. **UX moderna**: Tailwind UI, Alpine.js, responsive design

---

## 📋 Checklist Pre-Refactoring

Prima di iniziare il refactoring di un tool WordPress, completare questa analisi:

### 1. Analisi Funzionale
- [ ] Quali sono le funzionalità principali del tool?
- [ ] Quali API esterne vengono utilizzate?
- [ ] Quali credenziali/API keys sono necessarie?
- [ ] Il tool fa uso di OAuth? (Google, Facebook, etc.)
- [ ] Ci sono job asincroni o cron job?
- [ ] Quali dati vengono salvati nel database?
- [ ] Il tool genera contenuti con AI?
- [ ] Ci sono export/import di dati?

### 2. Analisi Tecnica
- [ ] Quali tabelle custom usa il plugin WP?
- [ ] Quali dipendenze esterne (Composer, npm)?
- [ ] Ci sono shortcode o widget?
- [ ] Usa JavaScript/jQuery custom?
- [ ] Ci sono file upload?
- [ ] Esistono webhook o API endpoint?

### 3. Analisi Token Consumption
- [ ] Il tool usa OpenAI? Quali modelli?
- [ ] Ci sono altre API a consumo token? (Anthropic, etc.)
- [ ] Quanti token mediamente consuma per operazione?
- [ ] È necessario tracking token per utente?

---

## 🏗️ Architettura Standard Ainstein Tool

Ogni tool deve seguire questa struttura:

```
ainstein-laravel/
├── app/
│   ├── Http/Controllers/Tenant/Tools/{ToolName}/
│   │   └── {ToolName}Controller.php
│   ├── Models/
│   │   └── {ToolName}.php
│   ├── Services/Tools/{ToolName}/
│   │   ├── {ToolName}Service.php
│   │   └── {ToolName}APIClient.php (se necessario)
│   └── Jobs/Tools/{ToolName}/
│       └── Process{ToolName}Job.php
├── database/migrations/
│   └── xxxx_create_{tool_name}_tables.php
├── resources/views/tenant/tools/{tool-name}/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
└── routes/tenant.php (aggiungere routes)
```

---

## 🔧 Step-by-Step Refactoring Process

### Step 1: Database Schema Design

#### Principi Fondamentali
1. **Sempre aggiungere `tenant_id`** come foreign key
2. Usare **JSON columns** per dati flessibili
3. Aggiungere **timestamps** (`created_at`, `updated_at`)
4. Usare **soft deletes** quando appropriato
5. Creare **indexes** su colonne filtrate frequentemente

#### Template Migration

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_{table_name}', function (Blueprint $table) {
            $table->id();

            // 🔑 OBBLIGATORIO: Multi-tenancy
            $table->foreignId('tenant_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Campi specifici del tool
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->enum('status', ['draft', 'processing', 'completed', 'failed'])
                  ->default('draft');

            // 📊 Token tracking (se il tool usa AI)
            $table->integer('tokens_used')->default(0);
            $table->string('model_used')->nullable();

            // Log e metadata
            $table->json('metadata')->nullable();
            $table->text('error_log')->nullable();

            $table->timestamps();
            $table->softDeletes(); // opzionale

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_{table_name}');
    }
};
```

---

### Step 2: Model Setup

#### Template Model

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolName extends Model
{
    use SoftDeletes;

    protected $table = 'tool_{table_name}';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'settings',
        'status',
        'tokens_used',
        'model_used',
        'metadata',
        'error_log',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 🔑 Relationship con Tenant
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes utili
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Helper methods
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_log' => $error,
        ]);
    }

    public function addTokenUsage(int $tokens, string $model): void
    {
        $this->increment('tokens_used', $tokens);
        $this->update(['model_used' => $model]);
    }
}
```

---

### Step 3: Service Layer

#### Template Service

```php
<?php
namespace App\Services\Tools\ToolName;

use App\Models\ToolName;
use App\Services\OpenAIService;
use App\Services\TokenTrackingService;
use Illuminate\Support\Facades\Log;

class ToolNameService
{
    public function __construct(
        protected OpenAIService $openai,
        protected TokenTrackingService $tokenTracker
    ) {}

    /**
     * Elabora il tool con AI
     */
    public function process(ToolName $tool, array $input): array
    {
        try {
            $tool->markAsProcessing();

            // 1. Prepara prompt
            $prompt = $this->buildPrompt($input);

            // 2. Chiamata OpenAI
            $response = $this->openai->chat([
                'model' => config('services.openai.default_model'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            // 3. Traccia token usage
            $tokens = $response['usage']['total_tokens'];
            $model = $response['model'];

            $tool->addTokenUsage($tokens, $model);
            $this->tokenTracker->track($tool->tenant_id, $tokens, $model, 'tool_name');

            // 4. Estrai risultato
            $result = $response['choices'][0]['message']['content'];

            $tool->markAsCompleted();

            return [
                'success' => true,
                'result' => $result,
                'tokens_used' => $tokens,
            ];

        } catch (\Exception $e) {
            Log::error("ToolName processing failed", [
                'tool_id' => $tool->id,
                'error' => $e->getMessage(),
            ]);

            $tool->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function buildPrompt(array $input): string
    {
        // Implementa logica specifica
        return "Your prompt here";
    }

    protected function getSystemPrompt(): string
    {
        return "You are an AI assistant specialized in...";
    }
}
```

---

### Step 4: Controller Setup

#### Template Controller

```php
<?php
namespace App\Http\Controllers\Tenant\Tools\ToolName;

use App\Http\Controllers\Controller;
use App\Models\ToolName;
use App\Services\Tools\ToolName\ToolNameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolNameController extends Controller
{
    public function __construct(
        protected ToolNameService $service
    ) {}

    public function index()
    {
        $tools = ToolName::forTenant(Auth::user()->tenant_id)
            ->latest()
            ->paginate(20);

        return view('tenant.tools.tool-name.index', compact('tools'));
    }

    public function create()
    {
        return view('tenant.tools.tool-name.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        $tool = ToolName::create([
            'tenant_id' => Auth::user()->tenant_id,
            ...$validated,
        ]);

        return redirect()
            ->route('tenant.tools.tool-name.show', $tool)
            ->with('success', 'Tool creato con successo!');
    }

    public function show(ToolName $tool)
    {
        $this->authorize('view', $tool);

        return view('tenant.tools.tool-name.show', compact('tool'));
    }

    public function edit(ToolName $tool)
    {
        $this->authorize('update', $tool);

        return view('tenant.tools.tool-name.edit', compact('tool'));
    }

    public function update(Request $request, ToolName $tool)
    {
        $this->authorize('update', $tool);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        $tool->update($validated);

        return redirect()
            ->route('tenant.tools.tool-name.show', $tool)
            ->with('success', 'Tool aggiornato!');
    }

    public function destroy(ToolName $tool)
    {
        $this->authorize('delete', $tool);

        $tool->delete();

        return redirect()
            ->route('tenant.tools.tool-name.index')
            ->with('success', 'Tool eliminato!');
    }

    /**
     * Esegue il processing del tool
     */
    public function process(Request $request, ToolName $tool)
    {
        $this->authorize('update', $tool);

        $result = $this->service->process($tool, $request->all());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'result' => $result['result'],
                'tokens_used' => $result['tokens_used'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 422);
    }
}
```

---

### Step 5: Policy Authorization

#### Template Policy

```php
<?php
namespace App\Policies;

use App\Models\User;
use App\Models\ToolName;

class ToolNamePolicy
{
    /**
     * Determina se l'utente può visualizzare il tool
     */
    public function view(User $user, ToolName $tool): bool
    {
        return $user->tenant_id === $tool->tenant_id;
    }

    /**
     * Determina se l'utente può creare tool
     */
    public function create(User $user): bool
    {
        return true; // Tutti gli utenti tenant possono creare
    }

    /**
     * Determina se l'utente può aggiornare il tool
     */
    public function update(User $user, ToolName $tool): bool
    {
        return $user->tenant_id === $tool->tenant_id;
    }

    /**
     * Determina se l'utente può eliminare il tool
     */
    public function delete(User $user, ToolName $tool): bool
    {
        return $user->tenant_id === $tool->tenant_id;
    }
}
```

Registra in `AuthServiceProvider.php`:

```php
protected $policies = [
    ToolName::class => ToolNamePolicy::class,
];
```

---

### Step 6: Routes

Aggiungere in `routes/tenant.php`:

```php
Route::prefix('tools/tool-name')->name('tools.tool-name.')->group(function () {
    Route::get('/', [ToolNameController::class, 'index'])->name('index');
    Route::get('/create', [ToolNameController::class, 'create'])->name('create');
    Route::post('/', [ToolNameController::class, 'store'])->name('store');
    Route::get('/{tool}', [ToolNameController::class, 'show'])->name('show');
    Route::get('/{tool}/edit', [ToolNameController::class, 'edit'])->name('edit');
    Route::put('/{tool}', [ToolNameController::class, 'update'])->name('update');
    Route::delete('/{tool}', [ToolNameController::class, 'destroy'])->name('destroy');

    // Azioni speciali
    Route::post('/{tool}/process', [ToolNameController::class, 'process'])->name('process');
});
```

---

### Step 7: UI/UX con Tailwind + Alpine.js

#### Template Index View

```blade
@extends('layouts.tenant')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h1 class="text-3xl font-bold text-gray-900">
                🎯 Tool Name
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Gestisci i tuoi tool e monitora il consumo di token
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('tenant.tools.tool-name.create') }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Tool
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Totale Tool</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ $tools->total() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Completati</dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                {{ $tools->where('status', 'completed')->count() }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Token Usati</dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                {{ number_format($tools->sum('tokens_used')) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tools as $tool)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $tool->name }}</div>
                        <div class="text-sm text-gray-500">{{ Str::limit($tool->description, 50) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($tool->status === 'completed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completato</span>
                        @elseif($tool->status === 'processing')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">In elaborazione</span>
                        @elseif($tool->status === 'failed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Fallito</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Bozza</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ number_format($tool->tokens_used) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $tool->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('tenant.tools.tool-name.show', $tool) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Vedi</a>
                        <a href="{{ route('tenant.tools.tool-name.edit', $tool) }}" class="text-gray-600 hover:text-gray-900 mr-3">Modifica</a>
                        <form action="{{ route('tenant.tools.tool-name.destroy', $tool) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Sei sicuro?')">Elimina</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                        Nessun tool creato. <a href="{{ route('tenant.tools.tool-name.create') }}" class="text-indigo-600 hover:text-indigo-900">Creane uno ora</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $tools->links() }}
        </div>
    </div>

</div>
@endsection
```

---

### Step 8: Background Jobs (Opzionale)

Per operazioni lunghe, usare Jobs:

```php
<?php
namespace App\Jobs\Tools\ToolName;

use App\Models\ToolName;
use App\Services\Tools\ToolName\ToolNameService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessToolNameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minuti

    public function __construct(
        public ToolName $tool,
        public array $input
    ) {}

    public function handle(ToolNameService $service): void
    {
        $service->process($this->tool, $this->input);
    }

    public function failed(\Throwable $exception): void
    {
        $this->tool->markAsFailed($exception->getMessage());
    }
}
```

Dispatch nel controller:

```php
ProcessToolNameJob::dispatch($tool, $request->all());
```

---

## 🔐 Admin Settings Integration

### Step 1: Estendere Migration Admin Settings

Se il tool richiede API keys esterne:

```php
// In migration admin_tool_settings o estendi admin_settings
Schema::table('admin_settings', function (Blueprint $table) {
    $table->string('toolname_api_key')->nullable()->after('openai_api_key');
    $table->json('toolname_config')->nullable();
});
```

### Step 2: Admin Settings View

Aggiungere sezione in `resources/views/admin/settings/tools.blade.php`:

```blade
{{-- Tool Name Settings --}}
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">🎯 Tool Name API</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
            <input type="password"
                   name="toolname_api_key"
                   value="{{ old('toolname_api_key', $settings->toolname_api_key) }}"
                   class="form-input w-full" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Configurazione</label>
            <textarea name="toolname_config"
                      rows="3"
                      class="form-textarea w-full">{{ old('toolname_config', json_encode($settings->toolname_config ?? [])) }}</textarea>
        </div>
    </div>
</div>
```

### Step 3: Service Config Retrieval

Nel Service, recuperare settings:

```php
protected function getApiKey(): string
{
    $settings = \App\Models\AdminSetting::first();
    return $settings->toolname_api_key ?? '';
}
```

---

## 📊 Token Tracking Integration

### Tracciamento Automatico

Ogni tool che usa AI **DEVE** tracciare i token:

```php
use App\Services\TokenTrackingService;

class ToolNameService
{
    public function __construct(
        protected TokenTrackingService $tokenTracker
    ) {}

    protected function trackTokens(int $tokens, string $model, int $tenantId): void
    {
        $this->tokenTracker->track(
            tenantId: $tenantId,
            tokens: $tokens,
            model: $model,
            source: 'tool_name', // Identificatore tool
            metadata: [
                'tool_id' => $this->tool->id,
                'action' => 'process',
            ]
        );
    }
}
```

### Visualizzazione Consumo

Mostrare token usage nella dashboard tenant:

```blade
<div class="mt-4 p-4 bg-blue-50 rounded-lg">
    <p class="text-sm text-blue-700">
        ⚡ Token utilizzati: <strong>{{ number_format($tool->tokens_used) }}</strong>
    </p>
    <p class="text-xs text-blue-600 mt-1">
        Modello: {{ $tool->model_used }}
    </p>
</div>
```

---

## 🎨 UI/UX Best Practices

### 1. Loading States

Usare Alpine.js per stati loading:

```blade
<div x-data="{ processing: false }">
    <button @click="processing = true; $refs.form.submit()"
            :disabled="processing"
            class="btn btn-primary">
        <span x-show="!processing">Elabora</span>
        <span x-show="processing" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Elaborazione...
        </span>
    </button>
</div>
```

### 2. Real-time Updates (Opzionale)

Con Laravel Echo + Pusher per aggiornamenti real-time:

```javascript
// resources/js/app.js
Echo.private(`tenant.${tenantId}.tools.${toolId}`)
    .listen('ToolProcessed', (e) => {
        // Aggiorna UI
        updateToolStatus(e.tool);
    });
```

### 3. Toast Notifications

Usare Alpine.js per notifiche:

```blade
@if(session('success'))
<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 3000)"
     class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
    {{ session('success') }}
</div>
@endif
```

### 4. Responsive Tables

Usare pattern responsive per tabelle grandi:

```blade
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        {{-- table content --}}
    </table>
</div>
```

---

## ✅ Checklist Finale Refactoring

Prima di considerare completo il refactoring:

### Backend
- [ ] Migration creata con `tenant_id` foreign key
- [ ] Model con relationships e casts
- [ ] Service layer con business logic
- [ ] Policy per authorization
- [ ] Routes registrate in `tenant.php`
- [ ] Controller con CRUD completo
- [ ] Token tracking implementato (se usa AI)
- [ ] Error handling e logging
- [ ] Admin settings (se necessario)
- [ ] Background jobs (se necessario)

### Frontend
- [ ] View index con stats e table
- [ ] View create/edit con form validation
- [ ] View show con dettagli completi
- [ ] Loading states con Alpine.js
- [ ] Toast notifications
- [ ] Responsive design
- [ ] Accessibility (ARIA labels)
- [ ] Dark mode compatible (opzionale)

### Testing
- [ ] Unit tests per Service
- [ ] Feature tests per Controller
- [ ] Policy tests
- [ ] Integration tests API esterne

### Documentation
- [ ] README specifico tool
- [ ] Inline comments codice
- [ ] API documentation (se espone endpoint)
- [ ] User guide in docs/

---

## 📚 Risorse e Tool Consigliati

### Laravel Packages Utili
- **Laravel Excel**: Import/Export Excel/CSV
- **Laravel Sanctum**: API authentication
- **Laravel Telescope**: Debug e monitoring
- **Spatie Laravel Permission**: Role-based access (se necessario)
- **Laravel Horizon**: Queue monitoring
- **Laravel Pulse**: Performance monitoring

### Frontend
- **Alpine.js**: Reattività UI
- **Tailwind CSS**: Styling
- **Heroicons**: Icon set
- **Chart.js**: Grafici (se necessario)

### API Clients
- **Guzzle HTTP**: HTTP client
- **OpenAI PHP**: Client OpenAI
- **Google API PHP Client**: Google services

### Testing
- **PHPUnit**: Unit testing
- **Pest**: Alternative a PHPUnit
- **Laravel Dusk**: Browser testing

---

## 🚀 Quick Start Template

Per velocizzare il refactoring, usa questo comando:

```bash
# Genera scaffolding base
php artisan make:model ToolName -mcr
php artisan make:policy ToolNamePolicy
php artisan make:service Tools/ToolName/ToolNameService
php artisan make:job Tools/ToolName/ProcessToolNameJob
```

Poi segui gli step di questo documento per completare l'implementazione.

---

## 📝 Note Finali

- **Consistenza**: Segui sempre le convenzioni Laravel
- **Sicurezza**: Non esporre mai API keys nel frontend
- **Performance**: Usa eager loading per evitare N+1 queries
- **Scalabilità**: Pensa sempre a background jobs per operazioni lunghe
- **UX**: Feedback immediato all'utente con loading states
- **Testing**: Testa sempre multi-tenancy isolation

Per tool specifici, consulta i file MD dedicati nella root del progetto.
