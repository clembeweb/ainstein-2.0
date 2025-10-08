# Job Monitoring Dashboard - Specifiche di Sviluppo

**Data creazione**: 2025-10-03
**Sistema**: Ainstein Platform - Laravel Queue Monitoring
**Destinatario**: Super Admin Dashboard

---

## 📋 Indice

1. [Panoramica](#panoramica)
2. [Obiettivi](#obiettivi)
3. [Database Schema](#database-schema)
4. [Architettura](#architettura)
5. [UI/UX Components](#uiux-components)
6. [API Endpoints](#api-endpoints)
7. [Features Dettagliate](#features-dettagliate)
8. [Implementazione](#implementazione)
9. [Testing](#testing)
10. [Future Enhancements](#future-enhancements)

---

## 📖 Panoramica

Sistema di monitoraggio in tempo reale per tutti i job della piattaforma Ainstein, con focus particolare sui job di generazione contenuti AI. La dashboard fornisce visibilità completa su:

- **Worker attivi** e loro stato
- **Job in coda** (pending, processing, completed, failed)
- **Metriche storiche** e performance
- **Gestione manuale** dei job (retry, cancella, priorità)
- **Alert e notifiche** per job falliti

### Contesto Attuale

**Job esistenti nella piattaforma**:
1. `ProcessContentGeneration` - Generazione contenuti AI via OpenAI
2. (Futuri) `ProcessBulkContentGeneration` - Generazione batch
3. (Futuri) `ProcessWordPressSync` - Sincronizzazione WordPress
4. (Futuri) `ProcessPrestaShopSync` - Sincronizzazione PrestaShop
5. (Futuri) `ProcessCsvImport` - Import CSV contenuti

**Queue configuration attuale**:
- Driver: `database` (tabella `jobs`)
- Connection: SQLite
- Retry policy: 3 tentativi
- Timeout: 300 secondi (5 minuti)

---

## 🎯 Obiettivi

### Obiettivi Primari
1. **Visibilità completa** dello stato dei worker e job
2. **Diagnostica rapida** dei problemi (job falliti, worker bloccati)
3. **Controllo manuale** per retry e gestione emergenze
4. **Metriche performance** per ottimizzazione
5. **Alert proattivi** per problemi critici

### Obiettivi Secondari
1. Auto-scaling dei worker basato sul carico
2. Previsione tempi di completamento
3. Gestione priorità job
4. Report schedulati via email

---

## 🗄️ Database Schema

### Tabella: `job_batches` (già esistente in Laravel)
```sql
CREATE TABLE job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INTEGER NOT NULL,
    pending_jobs INTEGER NOT NULL,
    failed_jobs INTEGER NOT NULL,
    failed_job_ids TEXT,
    options TEXT,
    cancelled_at INTEGER,
    created_at INTEGER NOT NULL,
    finished_at INTEGER
);
```

### Tabella: `failed_jobs` (già esistente in Laravel)
```sql
CREATE TABLE failed_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabella: `jobs` (già esistente in Laravel)
```sql
CREATE TABLE jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE INDEX jobs_queue_index ON jobs (queue);
```

### NUOVA Tabella: `job_metrics` (per metriche storiche)
```sql
CREATE TABLE job_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_type VARCHAR(255) NOT NULL,
    tenant_id VARCHAR(255),
    status VARCHAR(50) NOT NULL, -- completed, failed, timeout
    processing_time INTEGER NOT NULL, -- millisecondi
    memory_used INTEGER, -- bytes
    attempts INTEGER NOT NULL,
    error_message TEXT,
    metadata TEXT, -- JSON con info aggiuntive
    completed_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX job_metrics_type_status_idx ON job_metrics (job_type, status);
CREATE INDEX job_metrics_tenant_idx ON job_metrics (tenant_id);
CREATE INDEX job_metrics_completed_idx ON job_metrics (completed_at);
```

### NUOVA Tabella: `worker_heartbeats` (per monitoraggio worker)
```sql
CREATE TABLE worker_heartbeats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    worker_name VARCHAR(255) UNIQUE NOT NULL,
    process_id INTEGER NOT NULL,
    queue VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL, -- active, idle, stopped
    current_job_id INTEGER,
    current_job_type VARCHAR(255),
    started_at TIMESTAMP NOT NULL,
    last_heartbeat TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX worker_heartbeats_status_idx ON worker_heartbeats (status);
```

---

## 🏗️ Architettura

### Layer 1: Data Collection (Event Listeners)

**Event Listeners da creare**:

```php
// app/Listeners/JobEventSubscriber.php
class JobEventSubscriber
{
    public function subscribe($events)
    {
        $events->listen(
            'Illuminate\Queue\Events\JobProcessing',
            [JobEventSubscriber::class, 'handleJobProcessing']
        );

        $events->listen(
            'Illuminate\Queue\Events\JobProcessed',
            [JobEventSubscriber::class, 'handleJobProcessed']
        );

        $events->listen(
            'Illuminate\Queue\Events\JobFailed',
            [JobEventSubscriber::class, 'handleJobFailed']
        );

        $events->listen(
            'Illuminate\Queue\Events\WorkerStopping',
            [JobEventSubscriber::class, 'handleWorkerStopping']
        );
    }

    public function handleJobProcessing($event)
    {
        // Log job start, update metrics
        JobMetrics::recordJobStart($event->job, $event->connectionName);
    }

    public function handleJobProcessed($event)
    {
        // Log job completion, save metrics
        JobMetrics::recordJobComplete($event->job, $event->connectionName);
    }

    public function handleJobFailed($event)
    {
        // Log failure, trigger alerts
        JobMetrics::recordJobFailed($event->job, $event->exception);

        // Alert su Slack/email per job critici
        if ($this->isCriticalJob($event->job)) {
            Alert::send('Job critico fallito: ' . get_class($event->job));
        }
    }
}
```

### Layer 2: Models

```php
// app/Models/JobMetric.php
class JobMetric extends Model
{
    protected $fillable = [
        'job_type', 'tenant_id', 'status', 'processing_time',
        'memory_used', 'attempts', 'error_message', 'metadata', 'completed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'completed_at' => 'datetime'
    ];

    // Scopes
    public function scopeCompleted($query) { ... }
    public function scopeFailed($query) { ... }
    public function scopeByType($query, $type) { ... }
    public function scopeByTenant($query, $tenantId) { ... }
    public function scopeLast24Hours($query) { ... }

    // Aggregations
    public static function getSuccessRate($jobType = null) { ... }
    public static function getAverageProcessingTime($jobType) { ... }
    public static function getFailuresByType() { ... }
}

// app/Models/WorkerHeartbeat.php
class WorkerHeartbeat extends Model
{
    protected $fillable = [
        'worker_name', 'process_id', 'queue', 'status',
        'current_job_id', 'current_job_type', 'started_at', 'last_heartbeat'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_heartbeat' => 'datetime'
    ];

    // Check se worker è inattivo da > 2 minuti
    public function isStale() {
        return $this->last_heartbeat->diffInMinutes(now()) > 2;
    }

    // Active workers
    public static function getActiveWorkers() {
        return static::where('status', 'active')
            ->where('last_heartbeat', '>=', now()->subMinutes(2))
            ->get();
    }
}
```

### Layer 3: Services

```php
// app/Services/JobMonitoringService.php
class JobMonitoringService
{
    public function getDashboardStats()
    {
        return [
            'workers' => [
                'active' => WorkerHeartbeat::getActiveWorkers()->count(),
                'total' => WorkerHeartbeat::count(),
                'idle' => WorkerHeartbeat::where('status', 'idle')->count(),
                'stale' => WorkerHeartbeat::whereHas('isStale')->count()
            ],
            'jobs' => [
                'pending' => DB::table('jobs')->count(),
                'processing' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
                'completed_today' => JobMetric::where('status', 'completed')
                    ->whereDate('completed_at', today())->count(),
                'failed_today' => JobMetric::where('status', 'failed')
                    ->whereDate('completed_at', today())->count()
            ],
            'metrics' => [
                'avg_processing_time' => JobMetric::getAverageProcessingTime(),
                'success_rate' => JobMetric::getSuccessRate(),
                'total_processed_24h' => JobMetric::last24Hours()->count()
            ]
        ];
    }

    public function getJobsByStatus()
    {
        return [
            'pending' => DB::table('jobs')->select('*')->get(),
            'failed' => DB::table('failed_jobs')->latest()->limit(50)->get(),
            'processing' => DB::table('jobs')->whereNotNull('reserved_at')->get()
        ];
    }

    public function retryFailedJob($uuid)
    {
        Artisan::call('queue:retry', ['id' => $uuid]);
        return true;
    }

    public function retryAllFailed()
    {
        Artisan::call('queue:retry', ['id' => 'all']);
        return DB::table('failed_jobs')->count();
    }

    public function deleteFailedJob($uuid)
    {
        Artisan::call('queue:forget', ['id' => $uuid]);
        return true;
    }

    public function flushFailedJobs()
    {
        Artisan::call('queue:flush');
        return true;
    }
}
```

### Layer 4: Controllers

```php
// app/Http/Controllers/Admin/JobMonitoringController.php
class JobMonitoringController extends Controller
{
    protected $monitoringService;

    public function __construct(JobMonitoringService $service)
    {
        $this->monitoringService = $service;
        $this->middleware(['auth', 'role:super-admin']);
    }

    public function index()
    {
        $stats = $this->monitoringService->getDashboardStats();
        $jobs = $this->monitoringService->getJobsByStatus();

        return view('admin.job-monitoring.index', compact('stats', 'jobs'));
    }

    public function getRealtimeStats()
    {
        // API endpoint per polling AJAX ogni 5 secondi
        return response()->json([
            'stats' => $this->monitoringService->getDashboardStats(),
            'timestamp' => now()->toISOString()
        ]);
    }

    public function retryJob(Request $request, $uuid)
    {
        $this->monitoringService->retryFailedJob($uuid);
        return redirect()->back()->with('success', 'Job reinserito nella coda');
    }

    public function retryAll()
    {
        $count = $this->monitoringService->retryAllFailed();
        return redirect()->back()->with('success', "{$count} job reinseriti nella coda");
    }

    public function deleteJob($uuid)
    {
        $this->monitoringService->deleteFailedJob($uuid);
        return redirect()->back()->with('success', 'Job eliminato');
    }

    public function flushFailed()
    {
        $this->monitoringService->flushFailedJobs();
        return redirect()->back()->with('success', 'Tutti i job falliti sono stati eliminati');
    }

    public function getMetrics(Request $request)
    {
        $jobType = $request->get('job_type');
        $period = $request->get('period', '24h'); // 24h, 7d, 30d

        $metrics = JobMetric::query()
            ->when($jobType, fn($q) => $q->where('job_type', $jobType))
            ->when($period === '24h', fn($q) => $q->where('completed_at', '>=', now()->subDay()))
            ->when($period === '7d', fn($q) => $q->where('completed_at', '>=', now()->subDays(7)))
            ->when($period === '30d', fn($q) => $q->where('completed_at', '>=', now()->subDays(30)))
            ->selectRaw('
                DATE(completed_at) as date,
                COUNT(*) as total,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed,
                AVG(processing_time) as avg_time
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($metrics);
    }
}
```

---

## 🎨 UI/UX Components

### 1. Dashboard Overview (Pagina Principale)

```blade
{{-- resources/views/admin/job-monitoring/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Job Monitoring Dashboard')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Job Monitoring Dashboard</h1>
        <div class="flex space-x-3">
            <button @click="refreshStats()" class="btn-secondary">
                <i class="fas fa-sync-alt mr-2"></i>Aggiorna
            </button>
            <button @click="retryAllFailed()" class="btn-warning">
                <i class="fas fa-redo mr-2"></i>Retry All Failed
            </button>
        </div>
    </div>

    {{-- Stats Cards Row 1: Worker Status --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- Worker Attivi --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Worker Attivi</p>
                    <p class="text-3xl font-bold text-blue-600" id="active-workers">
                        {{ $stats['workers']['active'] }}/{{ $stats['workers']['total'] }}
                    </p>
                </div>
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-server text-2xl text-blue-600"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full"
                         style="width: {{ ($stats['workers']['active'] / max($stats['workers']['total'], 1)) * 100 }}%"></div>
                </div>
            </div>
        </div>

        {{-- Lavori in Attesa --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Lavori in Attesa</p>
                    <p class="text-3xl font-bold text-yellow-600" id="pending-jobs">
                        {{ $stats['jobs']['pending'] }}
                    </p>
                </div>
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl text-yellow-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                {{ $stats['jobs']['processing'] }} in elaborazione
            </p>
        </div>

        {{-- Lavori Completati Oggi --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Completati Oggi</p>
                    <p class="text-3xl font-bold text-green-600" id="completed-jobs">
                        {{ number_format($stats['jobs']['completed_today']) }}
                    </p>
                </div>
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Success rate: {{ number_format($stats['metrics']['success_rate'], 1) }}%
            </p>
        </div>

        {{-- Lavori Falliti Oggi --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Falliti Oggi</p>
                    <p class="text-3xl font-bold text-red-600" id="failed-jobs">
                        {{ $stats['jobs']['failed_today'] }}
                    </p>
                </div>
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
                </div>
            </div>
            @if($stats['jobs']['failed_today'] > 0)
                <a href="#failed-jobs-section" class="text-xs text-red-600 hover:text-red-800 mt-2 inline-block">
                    Visualizza dettagli →
                </a>
            @endif
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Job Timeline Chart --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Batch Live (Ultime 24h)</h3>
            <canvas id="job-timeline-chart" height="200"></canvas>
        </div>

        {{-- Job Types Distribution --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Distribuzione per Tipo</h3>
            <canvas id="job-types-chart" height="200"></canvas>
        </div>
    </div>

    {{-- Jobs Tables --}}
    <div class="space-y-6">
        {{-- Pending Jobs --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Lavori in Coda ({{ count($jobs['pending']) }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Queue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tentativi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creato</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($jobs['pending'] as $job)
                            @php
                                $payload = json_decode($job->payload);
                                $jobClass = $payload->displayName ?? 'Unknown';
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $job->id }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ class_basename($jobClass) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $job->queue }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $job->attempts }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::createFromTimestamp($job->created_at)->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button class="text-blue-600 hover:text-blue-900">Dettagli</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Nessun lavoro in coda
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Failed Jobs --}}
        <div id="failed-jobs-section" class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-red-600">Lavori Falliti ({{ count($jobs['failed']) }})</h3>
                @if(count($jobs['failed']) > 0)
                    <div class="flex space-x-2">
                        <form method="POST" action="{{ route('admin.jobs.retry-all') }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-warning btn-sm">
                                <i class="fas fa-redo mr-1"></i>Retry All
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.jobs.flush-failed') }}"
                              onsubmit="return confirm('Eliminare tutti i job falliti?')" class="inline">
                            @csrf
                            <button type="submit" class="btn-danger btn-sm">
                                <i class="fas fa-trash mr-1"></i>Elimina Tutti
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-red-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UUID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Queue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Errore</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fallito il</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($jobs['failed'] as $job)
                            @php
                                $payload = json_decode($job->payload);
                                $jobClass = $payload->displayName ?? 'Unknown';
                                $exception = Str::limit($job->exception, 100);
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                    {{ Str::limit($job->uuid, 8) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ class_basename($jobClass) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $job->queue }}</td>
                                <td class="px-6 py-4 text-sm text-red-600" title="{{ $job->exception }}">
                                    {{ $exception }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->failed_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <form method="POST" action="{{ route('admin.jobs.retry', $job->uuid) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.jobs.delete', $job->uuid) }}"
                                          onsubmit="return confirm('Eliminare questo job?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <button @click="showJobDetails('{{ $job->uuid }}')" class="text-gray-600 hover:text-gray-900">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                                    <p>Nessun lavoro fallito</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Alpine.js + Chart.js for realtime updates --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh stats every 5 seconds
    setInterval(refreshStats, 5000);

    function refreshStats() {
        fetch('/admin/jobs/realtime-stats')
            .then(res => res.json())
            .then(data => {
                document.getElementById('active-workers').textContent =
                    `${data.stats.workers.active}/${data.stats.workers.total}`;
                document.getElementById('pending-jobs').textContent = data.stats.jobs.pending;
                document.getElementById('completed-jobs').textContent =
                    data.stats.jobs.completed_today.toLocaleString();
                document.getElementById('failed-jobs').textContent = data.stats.jobs.failed_today;
            });
    }

    // Initialize charts
    initCharts();
});

function initCharts() {
    // Job Timeline Chart
    const timelineCtx = document.getElementById('job-timeline-chart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [], // Populated via AJAX
            datasets: [{
                label: 'Completati',
                data: [],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
            }, {
                label: 'Falliti',
                data: [],
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });

    // Job Types Distribution Chart
    const typesCtx = document.getElementById('job-types-chart').getContext('2d');
    new Chart(typesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Content Generation', 'WordPress Sync', 'PrestaShop Sync', 'CSV Import'],
            datasets: [{
                data: [0, 0, 0, 0], // Populated via AJAX
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(139, 92, 246)',
                    'rgb(236, 72, 153)',
                    'rgb(34, 197, 94)',
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });
}
</script>
@endpush
@endsection
```

---

## 🔌 API Endpoints

### Routes Definition

```php
// routes/web.php (Admin section)
Route::prefix('admin')->middleware(['auth', 'role:super-admin'])->group(function () {
    Route::prefix('jobs')->name('admin.jobs.')->group(function () {
        // Dashboard
        Route::get('/', [JobMonitoringController::class, 'index'])->name('index');

        // Realtime API
        Route::get('/realtime-stats', [JobMonitoringController::class, 'getRealtimeStats'])->name('realtime-stats');
        Route::get('/metrics', [JobMonitoringController::class, 'getMetrics'])->name('metrics');

        // Actions
        Route::post('/retry/{uuid}', [JobMonitoringController::class, 'retryJob'])->name('retry');
        Route::post('/retry-all', [JobMonitoringController::class, 'retryAll'])->name('retry-all');
        Route::delete('/delete/{uuid}', [JobMonitoringController::class, 'deleteJob'])->name('delete');
        Route::post('/flush-failed', [JobMonitoringController::class, 'flushFailed'])->name('flush-failed');

        // Workers
        Route::get('/workers', [JobMonitoringController::class, 'getWorkers'])->name('workers');
        Route::post('/workers/{id}/restart', [JobMonitoringController::class, 'restartWorker'])->name('workers.restart');
    });
});
```

---

## ⚙️ Features Dettagliate

### Feature 1: Auto-Refresh Dashboard (Polling AJAX)

**Comportamento**:
- Dashboard fa polling ogni 5 secondi a `/admin/jobs/realtime-stats`
- Aggiorna contatori senza ricaricare pagina
- Mostra indicatore "Last updated: X seconds ago"

**Implementazione**:
```javascript
let lastUpdate = Date.now();
setInterval(() => {
    fetch('/admin/jobs/realtime-stats')
        .then(res => res.json())
        .then(data => {
            updateDashboard(data);
            lastUpdate = Date.now();
            document.getElementById('last-update').textContent =
                'Updated just now';
        });
}, 5000);
```

### Feature 2: Worker Heartbeat System

**Comportamento**:
- Ogni worker scrive heartbeat ogni 30 secondi in `worker_heartbeats`
- Dashboard mostra worker come "stale" se heartbeat > 2 minuti
- Alert automatico se 0 worker attivi e job in coda > 10

**Implementazione**:
```php
// Console Command: app/Console/Commands/WorkerHeartbeat.php
class WorkerHeartbeat extends Command
{
    protected $signature = 'worker:heartbeat';

    public function handle()
    {
        $workerName = gethostname() . '_' . getmypid();

        WorkerHeartbeat::updateOrCreate(
            ['worker_name' => $workerName],
            [
                'process_id' => getmypid(),
                'queue' => 'default',
                'status' => 'active',
                'last_heartbeat' => now()
            ]
        );
    }
}
```

Da chiamare nel worker command:
```bash
php artisan queue:work --daemon &
while true; do php artisan worker:heartbeat; sleep 30; done &
```

### Feature 3: Retry con Exponential Backoff

**Comportamento**:
- Retry automatico dopo 1 min, 5 min, 30 min
- Dopo 3 tentativi → failed
- Opzione manuale "Retry Now" ignora backoff

**Implementazione**:
```php
// Nel Job
class ProcessContentGeneration implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [60, 300, 1800]; // 1min, 5min, 30min

    public function retryUntil()
    {
        return now()->addHours(24);
    }
}
```

### Feature 4: Priority Queue Management

**Comportamento**:
- Job urgenti vanno in queue "high"
- Job normali in queue "default"
- Job batch/export in queue "low"

**Implementazione**:
```php
ProcessContentGeneration::dispatch($generation)->onQueue('high');
```

Worker con priorità:
```bash
php artisan queue:work --queue=high,default,low
```

### Feature 5: Alerts e Notifiche

**Trigger alert quando**:
1. Job fallisce > 5 volte in 1 ora
2. Worker count = 0 e pending jobs > 10
3. Average processing time > soglia (es. 5 minuti)
4. Memory usage > 80%

**Implementazione**:
```php
// app/Services/AlertService.php
class AlertService
{
    public function checkAlerts()
    {
        // Check 1: High failure rate
        $recentFailures = JobMetric::where('status', 'failed')
            ->where('completed_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures >= 5) {
            $this->sendAlert('High failure rate', "warn");
        }

        // Check 2: No workers with pending jobs
        $activeWorkers = WorkerHeartbeat::getActiveWorkers()->count();
        $pendingJobs = DB::table('jobs')->count();

        if ($activeWorkers === 0 && $pendingJobs > 10) {
            $this->sendAlert('No active workers with jobs pending', 'critical');
        }
    }

    protected function sendAlert($message, $level)
    {
        // Send to Slack
        // Send email to admin
        // Log to file
        Log::channel($level)->error('[JOB ALERT] ' . $message);
    }
}
```

Scheduler per check ogni 5 minuti:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(AlertService::class)->checkAlerts();
    })->everyFiveMinutes();
}
```

---

## 🛠️ Implementazione

### Step 1: Migrations

```bash
php artisan make:migration create_job_metrics_table
php artisan make:migration create_worker_heartbeats_table
php artisan migrate
```

### Step 2: Models + Event Subscribers

```bash
php artisan make:model JobMetric
php artisan make:model WorkerHeartbeat
php artisan make:listener JobEventSubscriber
```

Registrare il subscriber in `EventServiceProvider`:
```php
protected $subscribe = [
    \App\Listeners\JobEventSubscriber::class,
];
```

### Step 3: Controllers + Views

```bash
php artisan make:controller Admin/JobMonitoringController
mkdir -p resources/views/admin/job-monitoring
touch resources/views/admin/job-monitoring/index.blade.php
```

### Step 4: Routes

Aggiungere routes in `routes/web.php` come da sezione API Endpoints

### Step 5: Frontend Assets

```bash
npm install chart.js
```

Includere in `resources/js/app.js`:
```javascript
import Chart from 'chart.js/auto';
window.Chart = Chart;
```

### Step 6: Worker Management

Creare script bash per gestire worker:

```bash
# scripts/start-workers.sh
#!/bin/bash
NUM_WORKERS=3

for i in $(seq 1 $NUM_WORKERS); do
    php artisan queue:work database --daemon --sleep=3 --tries=3 &
    echo "Started worker $i with PID $!"
done

# Start heartbeat monitor
while true; do
    php artisan worker:heartbeat
    sleep 30
done &
```

### Step 7: Supervisor Configuration

Creare `/etc/supervisor/conf.d/ainstein-workers.conf`:
```ini
[program:ainstein-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/ainstein/artisan queue:work database --sleep=3 --tries=3 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/ainstein-worker.log
stopwaitsecs=3600

[program:ainstein-heartbeat]
command=bash /path/to/ainstein/scripts/heartbeat.sh
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/ainstein-heartbeat.log
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ainstein-worker:*
sudo supervisorctl start ainstein-heartbeat:*
```

---

## 🧪 Testing

### Unit Tests

```php
// tests/Unit/JobMonitoringServiceTest.php
class JobMonitoringServiceTest extends TestCase
{
    public function test_dashboard_stats_returns_correct_structure()
    {
        $service = new JobMonitoringService();
        $stats = $service->getDashboardStats();

        $this->assertArrayHasKey('workers', $stats);
        $this->assertArrayHasKey('jobs', $stats);
        $this->assertArrayHasKey('metrics', $stats);
    }

    public function test_retry_failed_job_works()
    {
        // Create a failed job
        $uuid = Str::uuid();
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data']),
            'exception' => 'Test exception',
            'failed_at' => now()
        ]);

        $service = new JobMonitoringService();
        $result = $service->retryFailedJob($uuid);

        $this->assertTrue($result);
        $this->assertEquals(0, DB::table('failed_jobs')->where('uuid', $uuid)->count());
    }
}
```

### Feature Tests

```php
// tests/Feature/JobMonitoringTest.php
class JobMonitoringTest extends TestCase
{
    public function test_admin_can_access_job_monitoring_dashboard()
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin)->get('/admin/jobs');

        $response->assertStatus(200);
        $response->assertSee('Job Monitoring Dashboard');
    }

    public function test_regular_user_cannot_access_dashboard()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin/jobs');

        $response->assertStatus(403);
    }

    public function test_realtime_stats_endpoint_returns_json()
    {
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin)->get('/admin/jobs/realtime-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats' => [
                'workers',
                'jobs',
                'metrics'
            ],
            'timestamp'
        ]);
    }
}
```

---

## 🚀 Future Enhancements

### Phase 2 Features

1. **Auto-Scaling Workers**
   - Se pending jobs > 100 → spawn nuovo worker
   - Se pending jobs = 0 per 5 minuti → kill worker idle

2. **Predictive Analytics**
   - ML model per prevedere tempo completamento job
   - Alert se tempo stimato > soglia

3. **Advanced Filtering**
   - Filtra job per tenant
   - Filtra per job type
   - Filtra per date range

4. **Export Reports**
   - Export CSV delle metriche
   - Report PDF giornalieri/settimanali via email

5. **WebSocket Real-time Updates**
   - Sostituire polling AJAX con WebSocket
   - Push notifications real-time

6. **Queue Priority UI**
   - Interfaccia drag-and-drop per riordinare job priority
   - Pausa/riprendi specifici job

7. **Job Dependency Tracking**
   - Visualizza job che dipendono da altri
   - DAG (Directed Acyclic Graph) delle dipendenze

8. **Cost Tracking**
   - Track costi API OpenAI per job
   - Report costi per tenant

---

## 📚 References

- [Laravel Queue Documentation](https://laravel.com/docs/11.x/queues)
- [Laravel Horizon](https://laravel.com/docs/11.x/horizon) - Inspiration for UI
- [Supervisor Documentation](http://supervisord.org/)
- [Chart.js Documentation](https://www.chartjs.org/)

---

## 📝 Note Implementative

### Considerazioni Architetturali

1. **Performance**: Con migliaia di job/giorno, la tabella `job_metrics` crescerà rapidamente
   - **Soluzione**: Implementare partitioning per data o archiviazione vecchi record

2. **Scalabilità**: Dashboard potrebbe diventare lenta con molte query
   - **Soluzione**: Implementare cache Redis per statistiche aggregate

3. **Sicurezza**: Solo super-admin deve accedere
   - **Soluzione**: Middleware `role:super-admin` su tutte le route

4. **Monitoring Production**: Usare servizi esterni per uptime
   - **Soluzione**: Integrare con Sentry, New Relic o Datadog

### Best Practices

1. **Always Use Queues for Long Tasks** (> 2 secondi)
2. **Set Proper Timeouts** per evitare worker bloccati
3. **Use Job Batching** per operazioni correlate
4. **Monitor Memory Usage** del worker (restart se > 128MB)
5. **Log Everything** con context appropriato

---

**Autore**: Claude AI
**Versione**: 1.0
**Ultimo aggiornamento**: 2025-10-03
