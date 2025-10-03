# GSC Position Tracker - Refactoring Dettagliato

## 📋 Panoramica

**Nome**: Google Search Console Position Tracker
**Categoria**: SEO
**Funzione**: Tracking posizioni SERP e performance da Google Search Console
**API**: Google Search Console API (OAuth 2.0)
**Token tracking**: ❌ No (non usa AI)

---

## 🗄️ Database Schema

```php
Schema::create('seo_gsc_tracking', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('site_url'); // Property GSC
    $table->string('page_url');
    $table->string('query');

    $table->decimal('position', 5, 2);
    $table->integer('clicks')->default(0);
    $table->integer('impressions')->default(0);
    $table->decimal('ctr', 5, 2)->default(0);

    $table->date('check_date');

    $table->timestamps();

    $table->index(['tenant_id', 'check_date']);
    $table->index(['page_url', 'query', 'check_date']);
});

Schema::create('seo_gsc_properties', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->string('site_url');
    $table->boolean('is_default')->default(false);

    $table->timestamps();

    $table->unique(['tenant_id', 'site_url']);
});

Schema::create('google_search_console_connections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->json('credentials'); // client_credentials.json content
    $table->text('access_token')->nullable();
    $table->text('refresh_token')->nullable();
    $table->timestamp('expires_at')->nullable();

    $table->timestamps();

    $table->unique('tenant_id');
});
```

---

## 📦 Models

```php
class SeoGscTracking extends Model
{
    protected $fillable = [
        'tenant_id', 'site_url', 'page_url', 'query',
        'position', 'clicks', 'impressions', 'ctr', 'check_date'
    ];

    protected $casts = [
        'position' => 'decimal:2',
        'ctr' => 'decimal:2',
        'check_date' => 'date',
    ];

    public function getPositionChange(): ?float
    {
        $previous = self::where('tenant_id', $this->tenant_id)
            ->where('page_url', $this->page_url)
            ->where('query', $this->query)
            ->where('check_date', '<', $this->check_date)
            ->orderBy('check_date', 'desc')
            ->first();

        return $previous ? $previous->position - $this->position : null;
    }
}

class GoogleSearchConsoleConnection extends Model
{
    protected $fillable = [
        'tenant_id', 'credentials', 'access_token',
        'refresh_token', 'expires_at'
    ];

    protected $casts = [
        'credentials' => 'array',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
```

---

## 🔧 Service

```php
// app/Services/Tools/GSC/GoogleSearchConsoleService.php

use Google\Client as GoogleClient;
use Google\Service\SearchConsole as GoogleSearchConsole;

class GoogleSearchConsoleService
{
    protected GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setApplicationName('Ainstein GSC Tracker');
    }

    public function initOAuth(int $tenantId): string
    {
        $connection = GoogleSearchConsoleConnection::where('tenant_id', $tenantId)->first();

        if (!$connection) {
            throw new \Exception('GSC credentials not configured');
        }

        $this->client->setAuthConfig($connection->credentials);
        $this->client->addScope(GoogleSearchConsole::WEBMASTERS_READONLY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setRedirectUri(route('tenant.tools.seo.gsc.oauth.callback'));

        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code, int $tenantId): void
    {
        $connection = GoogleSearchConsoleConnection::where('tenant_id', $tenantId)->first();

        $this->client->setAuthConfig($connection->credentials);
        $this->client->setRedirectUri(route('tenant.tools.seo.gsc.oauth.callback'));

        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        $connection->update([
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'] ?? $connection->refresh_token,
            'expires_at' => now()->addSeconds($token['expires_in']),
        ]);
    }

    public function checkPositions(string $siteUrl, array $pageUrls, int $tenantId): array
    {
        $connection = GoogleSearchConsoleConnection::where('tenant_id', $tenantId)->first();

        if (!$connection) {
            throw new \Exception('GSC not connected');
        }

        // Refresh token se scaduto
        if ($connection->isExpired()) {
            $this->refreshToken($connection);
        }

        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
            'expires_in' => $connection->expires_at->diffInSeconds(now()),
        ]);

        $service = new GoogleSearchConsole($this->client);

        $results = [];

        foreach ($pageUrls as $pageUrl) {
            $query = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
            $query->setStartDate(now()->subDays(7)->format('Y-m-d'));
            $query->setEndDate(now()->format('Y-m-d'));
            $query->setDimensions(['query', 'page']);
            $query->setRowLimit(100);

            // Filtra per pagina specifica
            $dimensionFilter = new \Google\Service\SearchConsole\ApiDimensionFilter();
            $dimensionFilter->setDimension('page');
            $dimensionFilter->setOperator('equals');
            $dimensionFilter->setExpression($pageUrl);

            $dimensionFilterGroup = new \Google\Service\SearchConsole\ApiDimensionFilterGroup();
            $dimensionFilterGroup->setFilters([$dimensionFilter]);

            $query->setDimensionFilterGroups([$dimensionFilterGroup]);

            $response = $service->searchanalytics->query($siteUrl, $query);

            foreach ($response->getRows() as $row) {
                $tracking = SeoGscTracking::create([
                    'tenant_id' => $tenantId,
                    'site_url' => $siteUrl,
                    'page_url' => $row->getKeys()[1],
                    'query' => $row->getKeys()[0],
                    'position' => $row->getPosition(),
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr() * 100,
                    'check_date' => now()->toDateString(),
                ]);

                $results[] = $tracking;
            }
        }

        return $results;
    }

    protected function refreshToken(GoogleSearchConsoleConnection $connection): void
    {
        $this->client->setAuthConfig($connection->credentials);
        $this->client->refreshToken($connection->refresh_token);

        $newToken = $this->client->getAccessToken();

        $connection->update([
            'access_token' => $newToken['access_token'],
            'expires_at' => now()->addSeconds($newToken['expires_in']),
        ]);
    }

    public function getProperties(int $tenantId): array
    {
        $connection = GoogleSearchConsoleConnection::where('tenant_id', $tenantId)->first();

        if ($connection->isExpired()) {
            $this->refreshToken($connection);
        }

        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
        ]);

        $service = new GoogleSearchConsole($this->client);
        $sitesList = $service->sites->listSites();

        return collect($sitesList->getSiteEntry())
            ->map(fn($site) => $site->getSiteUrl())
            ->toArray();
    }
}
```

---

## 🎮 Controller

```php
class GSCTrackingController extends Controller
{
    public function __construct(
        protected GoogleSearchConsoleService $gscService
    ) {}

    public function index()
    {
        $tracking = SeoGscTracking::forTenant(Auth::user()->tenant_id)
            ->with('content')
            ->latest('check_date')
            ->paginate(50);

        $connection = GoogleSearchConsoleConnection::where('tenant_id', Auth::user()->tenant_id)->first();

        return view('tenant.tools.seo.gsc-tracking.index', compact('tracking', 'connection'));
    }

    public function initiateOAuth()
    {
        try {
            $authUrl = $this->gscService->initOAuth(Auth::user()->tenant_id);
            return redirect($authUrl);
        } catch (\Exception $e) {
            return back()->with('error', 'Configura prima le credenziali GSC nelle impostazioni admin');
        }
    }

    public function handleOAuthCallback(Request $request)
    {
        $this->gscService->handleCallback($request->code, Auth::user()->tenant_id);

        return redirect()
            ->route('tenant.tools.seo.gsc-tracking.index')
            ->with('success', 'GSC connesso con successo!');
    }

    public function checkPositions(Request $request)
    {
        $validated = $request->validate([
            'site_url' => 'required|url',
            'page_urls' => 'required|array',
            'page_urls.*' => 'url',
        ]);

        try {
            $results = $this->gscService->checkPositions(
                $validated['site_url'],
                $validated['page_urls'],
                Auth::user()->tenant_id
            );

            return back()->with('success', count($results) . ' posizioni tracciate!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $data = SeoGscTracking::forTenant(Auth::user()->tenant_id)
            ->whereBetween('check_date', [
                $request->get('start_date', now()->subDays(30)),
                $request->get('end_date', now()),
            ])
            ->get();

        return Excel::download(
            new GSCTrackingExport($data),
            'gsc_tracking_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function compare(Request $request)
    {
        $validated = $request->validate([
            'page_url' => 'required|url',
            'date1' => 'required|date',
            'date2' => 'required|date',
        ]);

        $tracking1 = SeoGscTracking::forTenant(Auth::user()->tenant_id)
            ->where('page_url', $validated['page_url'])
            ->where('check_date', $validated['date1'])
            ->get()
            ->keyBy('query');

        $tracking2 = SeoGscTracking::forTenant(Auth::user()->tenant_id)
            ->where('page_url', $validated['page_url'])
            ->where('check_date', $validated['date2'])
            ->get()
            ->keyBy('query');

        $comparison = [];

        foreach ($tracking2 as $query => $t2) {
            $t1 = $tracking1->get($query);

            $comparison[] = [
                'query' => $query,
                'position_before' => $t1?->position,
                'position_after' => $t2->position,
                'position_change' => $t1 ? ($t1->position - $t2->position) : null,
                'clicks_change' => $t1 ? ($t2->clicks - $t1->clicks) : $t2->clicks,
            ];
        }

        return response()->json($comparison);
    }
}
```

---

## 🎨 UI (Sintesi)

```blade
<div class="max-w-7xl mx-auto">
    <h1>📊 GSC Position Tracker</h1>

    @if(!$connection || !$connection->access_token)
        <div class="alert alert-warning">
            <p>Connetti Google Search Console per iniziare</p>
            <a href="{{ route('tenant.tools.seo.gsc.oauth') }}" class="btn btn-primary">
                🔗 Connetti GSC
            </a>
        </div>
    @else
        {{-- Form Check Positions --}}
        <form method="POST" action="{{ route('tenant.tools.seo.gsc.check') }}">
            @csrf
            <div>
                <label>Site URL</label>
                <select name="site_url" required>
                    @foreach($properties as $property)
                        <option value="{{ $property }}">{{ $property }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Page URLs (una per riga)</label>
                <textarea name="page_urls" rows="5"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">🔍 Check Posizioni</button>
        </form>

        {{-- Results Table --}}
        <table class="mt-6">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Pagina</th>
                    <th>Query</th>
                    <th>Posizione</th>
                    <th>Variazione</th>
                    <th>Click</th>
                    <th>Impression</th>
                    <th>CTR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tracking as $t)
                <tr>
                    <td>{{ $t->check_date->format('d/m/Y') }}</td>
                    <td>{{ Str::limit($t->page_url, 40) }}</td>
                    <td>{{ $t->query }}</td>
                    <td>
                        <span class="font-bold">{{ number_format($t->position, 1) }}</span>
                    </td>
                    <td>
                        @php $change = $t->getPositionChange(); @endphp
                        @if($change)
                            @if($change > 0)
                                <span class="text-green-600">↑ {{ number_format($change, 1) }}</span>
                            @else
                                <span class="text-red-600">↓ {{ number_format(abs($change), 1) }}</span>
                            @endif
                        @endif
                    </td>
                    <td>{{ $t->clicks }}</td>
                    <td>{{ number_format($t->impressions) }}</td>
                    <td>{{ number_format($t->ctr, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            <a href="{{ route('tenant.tools.seo.gsc.export') }}" class="btn btn-secondary">
                📥 Export Excel
            </a>
        </div>
    @endif
</div>
```

---

## ✅ Checklist

- [ ] Migration tables
- [ ] Models + OAuth connection
- [ ] GoogleSearchConsoleService con OAuth flow
- [ ] Google API PHP Client package: `composer require google/apiclient`
- [ ] Controller OAuth + check positions
- [ ] Export Excel (Laravel Excel)
- [ ] Compare dates functionality
- [ ] Admin upload client_credentials.json

**Stima Token**: 0 (non usa AI)
