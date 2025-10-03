# 📊 Admin Panel - OpenAI Cost Analytics

**Feature Request**: Dashboard per analisi e controllo costi API OpenAI
**Priority**: P1 High (Super Admin Feature)
**Layer**: Layer 1.3 - Admin Infrastructure Enhancement

---

## 🎯 Obiettivo

Creare una sezione nel Super Admin panel per monitorare e analizzare i costi delle chiamate API OpenAI, permettendo di:
- Visualizzare costi totali e trend
- Filtrare per date e tenant
- Analizzare usage per modello AI
- Esportare report dettagliati

---

## 📋 Specifiche Feature

### 1. Database Schema

#### Nuova Tabella: `openai_usage_logs`

```php
Schema::create('openai_usage_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    $table->string('model'); // gpt-4, gpt-4o, gpt-3.5-turbo, etc.
    $table->integer('input_tokens')->default(0);
    $table->integer('output_tokens')->default(0);
    $table->integer('total_tokens')->default(0);
    $table->decimal('input_cost', 10, 6)->default(0); // Cost in USD
    $table->decimal('output_cost', 10, 6)->default(0);
    $table->decimal('total_cost', 10, 6)->default(0);
    $table->string('request_type'); // 'generation', 'revision', 'update', etc.
    $table->string('source')->nullable(); // Controller/Service che ha fatto la call
    $table->json('metadata')->nullable(); // Extra context
    $table->timestamps();

    $table->index(['tenant_id', 'created_at']);
    $table->index(['model', 'created_at']);
    $table->index('request_type');
});
```

#### Estensione Tabella: `tenants`

```php
Schema::table('tenants', function (Blueprint $table) {
    $table->decimal('total_ai_cost', 12, 2)->default(0)->after('tokens_used_current');
    $table->decimal('monthly_ai_cost', 12, 2)->default(0)->after('total_ai_cost');
    $table->date('cost_reset_at')->nullable()->after('monthly_ai_cost');
});
```

---

### 2. Model

```php
// app/Models/OpenAiUsageLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenAiUsageLog extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'model',
        'input_tokens', 'output_tokens', 'total_tokens',
        'input_cost', 'output_cost', 'total_cost',
        'request_type', 'source', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'input_cost' => 'decimal:6',
        'output_cost' => 'decimal:6',
        'total_cost' => 'decimal:6',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope per filtrare per range date
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope per filtrare per modello
     */
    public function scopeByModel($query, $model)
    {
        return $query->where('model', $model);
    }

    /**
     * Scope per tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
```

---

### 3. Service - OpenAI Cost Tracker

```php
// app/Services/OpenAiCostTracker.php
namespace App\Services;

use App\Models\OpenAiUsageLog;
use App\Models\Tenant;

class OpenAiCostTracker
{
    // Pricing per model (input/output per 1M tokens in USD)
    const PRICING = [
        'gpt-4' => ['input' => 30.00, 'output' => 60.00],
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
    ];

    /**
     * Traccia usage e calcola costo
     */
    public function trackUsage(
        ?int $tenantId,
        ?int $userId,
        string $model,
        int $inputTokens,
        int $outputTokens,
        string $requestType,
        ?string $source = null,
        ?array $metadata = null
    ): OpenAiUsageLog {
        $pricing = self::PRICING[$model] ?? self::PRICING['gpt-4o-mini'];

        $inputCost = ($inputTokens / 1000000) * $pricing['input'];
        $outputCost = ($outputTokens / 1000000) * $pricing['output'];
        $totalCost = $inputCost + $outputCost;
        $totalTokens = $inputTokens + $outputTokens;

        $log = OpenAiUsageLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'input_cost' => $inputCost,
            'output_cost' => $outputCost,
            'total_cost' => $totalCost,
            'request_type' => $requestType,
            'source' => $source,
            'metadata' => $metadata,
        ]);

        // Update tenant costs
        if ($tenantId) {
            $this->updateTenantCosts($tenantId, $totalCost);
        }

        return $log;
    }

    /**
     * Aggiorna costi tenant
     */
    protected function updateTenantCosts(int $tenantId, float $cost): void
    {
        $tenant = Tenant::find($tenantId);

        $tenant->increment('total_ai_cost', $cost);
        $tenant->increment('monthly_ai_cost', $cost);
    }

    /**
     * Reset costi mensili per tenant
     */
    public function resetMonthlyCosts(int $tenantId): void
    {
        Tenant::where('id', $tenantId)->update([
            'monthly_ai_cost' => 0,
            'cost_reset_at' => now(),
        ]);
    }

    /**
     * Get statistics per date range
     */
    public function getStats(?string $startDate = null, ?string $endDate = null, ?int $tenantId = null): array
    {
        $query = OpenAiUsageLog::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        return [
            'total_cost' => $query->sum('total_cost'),
            'total_tokens' => $query->sum('total_tokens'),
            'total_requests' => $query->count(),
            'by_model' => $query->selectRaw('model, SUM(total_cost) as cost, SUM(total_tokens) as tokens, COUNT(*) as requests')
                ->groupBy('model')
                ->get(),
            'by_request_type' => $query->selectRaw('request_type, SUM(total_cost) as cost, COUNT(*) as requests')
                ->groupBy('request_type')
                ->get(),
        ];
    }

    /**
     * Get daily costs (for charts)
     */
    public function getDailyCosts(?string $startDate = null, ?string $endDate = null, ?int $tenantId = null): array
    {
        $query = OpenAiUsageLog::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        return $query->selectRaw('DATE(created_at) as date, SUM(total_cost) as cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('cost', 'date')
            ->toArray();
    }
}
```

---

### 4. Controller - Admin Cost Analytics

```php
// app/Http/Controllers/Admin/CostAnalyticsController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OpenAiCostTracker;
use App\Models\OpenAiUsageLog;
use App\Models\Tenant;
use Illuminate\Http\Request;

class CostAnalyticsController extends Controller
{
    protected $costTracker;

    public function __construct(OpenAiCostTracker $costTracker)
    {
        $this->costTracker = $costTracker;
    }

    /**
     * Dashboard principale cost analytics
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $tenantId = $request->get('tenant_id');

        $stats = $this->costTracker->getStats($startDate, $endDate, $tenantId);
        $dailyCosts = $this->costTracker->getDailyCosts($startDate, $endDate, $tenantId);

        $tenants = Tenant::select('id', 'name')->get();

        return view('admin.cost-analytics.index', compact(
            'stats',
            'dailyCosts',
            'tenants',
            'startDate',
            'endDate',
            'tenantId'
        ));
    }

    /**
     * Dettaglio logs (DataTable)
     */
    public function logs(Request $request)
    {
        $query = OpenAiUsageLog::with(['tenant', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        if ($request->has('tenant_id')) {
            $query->forTenant($request->tenant_id);
        }

        if ($request->has('model')) {
            $query->byModel($request->model);
        }

        return response()->json([
            'data' => $query->paginate(50)
        ]);
    }

    /**
     * Export CSV
     */
    public function export(Request $request)
    {
        $query = OpenAiUsageLog::with(['tenant', 'user']);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        if ($request->has('tenant_id')) {
            $query->forTenant($request->tenant_id);
        }

        $logs = $query->get();

        $csv = "Date,Tenant,User,Model,Input Tokens,Output Tokens,Total Tokens,Cost (USD)\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%d,%d,%d,%.6f\n",
                $log->created_at->format('Y-m-d H:i:s'),
                $log->tenant ? $log->tenant->name : 'N/A',
                $log->user ? $log->user->name : 'N/A',
                $log->model,
                $log->input_tokens,
                $log->output_tokens,
                $log->total_tokens,
                $log->total_cost
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="openai-costs-' . now()->format('Y-m-d') . '.csv"');
    }
}
```

---

### 5. View - Cost Analytics Dashboard

```blade
{{-- resources/views/admin/cost-analytics/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'OpenAI Cost Analytics')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">📊 OpenAI Cost Analytics</h1>
        <p class="mt-2 text-gray-600">Monitora e analizza i costi delle API OpenAI</p>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('admin.cost-analytics') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">

            {{-- Date Range --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Inizio</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Fine</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full rounded-lg border-gray-300">
            </div>

            {{-- Tenant Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tenant</label>
                <select name="tenant_id" class="w-full rounded-lg border-gray-300">
                    <option value="">Tutti i tenant</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ $tenantId == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                    🔍 Filtra
                </button>
            </div>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

        {{-- Total Cost --}}
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-sm font-medium">Costo Totale</p>
                    <p class="text-3xl font-bold mt-2">${{ number_format($stats['total_cost'], 2) }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Tokens --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm font-medium">Token Totali</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_tokens']) }}</p>
            <p class="text-sm text-gray-500 mt-1">~{{ number_format($stats['total_tokens'] / 1000, 1) }}K tokens</p>
        </div>

        {{-- Total Requests --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <p class="text-gray-600 text-sm font-medium">Richieste Totali</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_requests']) }}</p>
            <p class="text-sm text-gray-500 mt-1">API calls</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Daily Costs Chart --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 Costi Giornalieri</h3>
            <canvas id="dailyCostsChart" height="200"></canvas>
        </div>

        {{-- By Model Chart (Pie) --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🎯 Costi per Modello</h3>
            <canvas id="modelCostsChart" height="200"></canvas>
        </div>
    </div>

    {{-- By Model Table --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">💻 Statistiche per Modello</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modello</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Token</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Richieste</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Costo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">$/1M tokens</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stats['by_model'] as $modelStat)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                                {{ $modelStat->model }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($modelStat->tokens) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($modelStat->requests) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            ${{ number_format($modelStat->cost, 4) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${{ number_format(($modelStat->cost / $modelStat->tokens) * 1000000, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Export Button --}}
    <div class="flex justify-end">
        <a href="{{ route('admin.cost-analytics.export', request()->all()) }}"
           class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Esporta CSV
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Daily Costs Chart
const dailyCostsData = @json($dailyCosts);
const dailyCostsChart = new Chart(document.getElementById('dailyCostsChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(dailyCostsData),
        datasets: [{
            label: 'Costo ($)',
            data: Object.values(dailyCostsData),
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(2);
                    }
                }
            }
        }
    }
});

// Model Costs Chart (Pie)
const modelData = @json($stats['by_model']);
const modelCostsChart = new Chart(document.getElementById('modelCostsChart'), {
    type: 'doughnut',
    data: {
        labels: modelData.map(m => m.model),
        datasets: [{
            data: modelData.map(m => m.cost),
            backgroundColor: [
                'rgba(99, 102, 241, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(251, 146, 60, 0.8)',
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': $' + context.parsed.toFixed(4);
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection
```

---

### 6. Routes

```php
// routes/admin.php (o web.php con prefix admin)

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {

    // Cost Analytics
    Route::get('/cost-analytics', [CostAnalyticsController::class, 'index'])->name('cost-analytics');
    Route::get('/cost-analytics/logs', [CostAnalyticsController::class, 'logs'])->name('cost-analytics.logs');
    Route::get('/cost-analytics/export', [CostAnalyticsController::class, 'export'])->name('cost-analytics.export');
});
```

---

### 7. Integration con OpenAI Service

Modifica `OpenAiService` per tracciare automaticamente ogni chiamata:

```php
// app/Services/OpenAiService.php

use App\Services\OpenAiCostTracker;

class OpenAiService
{
    protected $costTracker;

    public function __construct(OpenAiCostTracker $costTracker)
    {
        $this->costTracker = $costTracker;
    }

    public function generateContent(string $prompt, ?int $tenantId = null, ?int $userId = null)
    {
        // Existing OpenAI call logic...
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        // Track usage automatically
        $this->costTracker->trackUsage(
            tenantId: $tenantId,
            userId: $userId,
            model: $response['model'],
            inputTokens: $response['usage']['prompt_tokens'],
            outputTokens: $response['usage']['completion_tokens'],
            requestType: 'generation',
            source: static::class,
            metadata: ['prompt_length' => strlen($prompt)]
        );

        return $response['choices'][0]['message']['content'];
    }
}
```

---

## 📊 Features Dashboard

### Stats Cards
1. **Costo Totale** (periodo selezionato)
2. **Token Totali** (con conversione K/M)
3. **Richieste Totali** (API calls count)

### Charts
1. **Costi Giornalieri** (Bar chart) - Trend temporale
2. **Costi per Modello** (Doughnut chart) - Distribuzione percentuale

### Tables
1. **Statistiche per Modello** - Breakdown dettagliato
2. **Statistiche per Request Type** - Usage patterns

### Filters
- Range date (start/end)
- Tenant selector
- Model filter (dropdown)

### Export
- CSV export completo
- Excel export (opzionale con Laravel Excel)

---

## 🔄 Scheduled Jobs

### Reset Costi Mensili

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Reset monthly costs il 1° del mese
    $schedule->call(function () {
        $tenants = Tenant::all();
        $costTracker = app(OpenAiCostTracker::class);

        foreach ($tenants as $tenant) {
            $costTracker->resetMonthlyCosts($tenant->id);
        }
    })->monthlyOn(1, '00:00');
}
```

---

## ✅ Implementation Checklist

### Database
- [ ] Create `openai_usage_logs` migration
- [ ] Extend `tenants` table with cost fields
- [ ] Run migrations
- [ ] Create OpenAiUsageLog model

### Backend
- [ ] Create `OpenAiCostTracker` service
- [ ] Create `CostAnalyticsController`
- [ ] Integrate tracking in `OpenAiService`
- [ ] Add routes to admin panel
- [ ] Create scheduled job for monthly reset

### Frontend
- [ ] Create cost analytics view
- [ ] Implement Chart.js for visualizations
- [ ] Add date range picker
- [ ] Add tenant/model filters
- [ ] Create export CSV functionality
- [ ] Add navigation link in admin sidebar

### Testing
- [ ] Test cost calculation accuracy
- [ ] Test filters (date range, tenant, model)
- [ ] Test export CSV
- [ ] Test monthly reset job
- [ ] Verify chart rendering

---

## 🎯 Success Criteria

✅ Super admin can view total OpenAI costs
✅ Costs are broken down by tenant, model, request type
✅ Daily cost trends are visualized in charts
✅ CSV export works with all filters applied
✅ Automatic tracking on every OpenAI API call
✅ Monthly costs reset automatically
✅ Cost data accurate within 0.01 USD

---

## 📈 Future Enhancements

1. **Budget Alerts** - Email quando si supera soglia
2. **Cost Forecasting** - Predizione costi futuri con ML
3. **Tenant Billing** - Addebitare costi ai tenant
4. **Cost Optimization Tips** - AI suggestions per ridurre costi
5. **Real-time Dashboard** - WebSocket per updates live
6. **API Rate Limiting** - Limiti automatici basati su budget

---

_Feature spec created: 3 Ottobre 2025_
_Priority: P1 High - Admin Infrastructure Enhancement_
