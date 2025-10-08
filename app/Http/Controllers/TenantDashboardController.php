<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\ContentGeneration;
use App\Models\Prompt;
use App\Models\ApiKey;
use App\Models\AdvCampaign;
use App\Models\AdvGeneratedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return redirect()->route('login')->with('error', 'No tenant assigned to your account');
        }

        try {
            // Basic statistics
            $stats = [
                'total_pages' => Page::where('tenant_id', $tenant->id)->count(),
                'total_generations' => ContentGeneration::where('tenant_id', $tenant->id)->count(),
                'active_prompts' => Prompt::where('tenant_id', $tenant->id)->where('is_active', true)->count(),
                'total_prompts' => Prompt::where('tenant_id', $tenant->id)->count(),
                'completed_generations' => ContentGeneration::where('tenant_id', $tenant->id)->where('status', 'completed')->count(),
                'failed_generations' => ContentGeneration::where('tenant_id', $tenant->id)->where('status', 'failed')->count(),
                'pending_generations' => ContentGeneration::where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
                'processing_generations' => ContentGeneration::where('tenant_id', $tenant->id)->where('status', 'processing')->count(),
            ];

            // API Key statistics
            $apiKeyStats = [
                'total_api_keys' => ApiKey::where('tenant_id', $tenant->id)->count(),
                'active_api_keys' => ApiKey::where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })->count(),
                'expired_api_keys' => ApiKey::where('tenant_id', $tenant->id)
                    ->where('expires_at', '<=', now())
                    ->count(),
            ];

            // Page statistics by status and category
            $pageStats = [
                'active_pages' => Page::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
                'inactive_pages' => Page::where('tenant_id', $tenant->id)->where('status', 'inactive')->count(),
                'pending_pages' => Page::where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
                'archived_pages' => Page::where('tenant_id', $tenant->id)->where('status', 'archived')->count(),
            ];

            // Get pages by category for charts
            $pagesByCategory = Page::where('tenant_id', $tenant->id)
                ->select('category', \DB::raw('count(*) as count'))
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->groupBy('category')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            // Monthly generation trends (last 6 months)
            $generationTrends = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $generationTrends[] = [
                    'month' => $date->format('M Y'),
                    'count' => ContentGeneration::where('tenant_id', $tenant->id)
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count(),
                    'tokens' => ContentGeneration::where('tenant_id', $tenant->id)
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->sum('tokens_used') ?: 0,
                ];
            }

            // Success rate calculation
            $successRate = $stats['total_generations'] > 0
                ? round(($stats['completed_generations'] / $stats['total_generations']) * 100, 1)
                : 0;

            $stats['success_rate'] = $successRate;

            // Campaign Generator statistics
            $campaignStats = [
                'total_campaigns' => AdvCampaign::where('tenant_id', $tenant->id)->count(),
                'rsa_campaigns' => AdvCampaign::where('tenant_id', $tenant->id)->where('type', 'rsa')->count(),
                'pmax_campaigns' => AdvCampaign::where('tenant_id', $tenant->id)->where('type', 'pmax')->count(),
                'total_assets' => AdvGeneratedAsset::whereHas('campaign', function($q) use ($tenant) {
                    $q->where('tenant_id', $tenant->id);
                })->count(),
                'campaign_tokens' => AdvCampaign::where('tenant_id', $tenant->id)->sum('tokens_used') ?: 0,
            ];

            // Token statistics (simplified)
            $tokenStats = [
                'total_tokens_used' => $tenant->tokens_used_current,
                'monthly_limit' => $tenant->tokens_monthly_limit,
                'remaining_tokens' => max(0, $tenant->tokens_monthly_limit - $tenant->tokens_used_current),
            ];

            // Usage percentage
            $tokenUsagePercent = $tenant->tokens_monthly_limit > 0
                ? round(($tenant->tokens_used_current / $tenant->tokens_monthly_limit) * 100, 1)
                : 0;

            $tokenStats['usage_percent'] = $tokenUsagePercent;
            $tokenStats['remaining_tokens'] = max(0, $tenant->tokens_monthly_limit - $tenant->tokens_used_current);

            // Recent pages with generation counts
            $recentPages = Page::where('tenant_id', $tenant->id)
                ->with(['generations' => function ($query) {
                    $query->select('page_id', 'status', \DB::raw('count(*) as count'))
                        ->groupBy('page_id', 'status');
                }])
                ->withCount('generations')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Recent generations with page info
            $recentGenerations = ContentGeneration::where('tenant_id', $tenant->id)
                ->with(['page:id,url_path,keyword'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Top performing pages (by generation count)
            $topPages = Page::where('tenant_id', $tenant->id)
                ->withCount(['generations' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->get()
                ->filter(function ($page) {
                    return $page->generations_count > 0;
                })
                ->sortByDesc('generations_count')
                ->take(5)
                ->values();

            // Recent activity (last 7 days)
            $recentActivity = [
                'pages_created' => Page::where('tenant_id', $tenant->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'generations_completed' => ContentGeneration::where('tenant_id', $tenant->id)
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'tokens_used' => ContentGeneration::where('tenant_id', $tenant->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->sum('tokens_used') ?: 0,
            ];

            // Plan information
            $planInfo = [
                'name' => ucfirst($tenant->plan_type ?? 'free'),
                'tokens_limit' => $tenant->tokens_monthly_limit,
                'tokens_used' => $tenant->tokens_used_current,
                'pages_limit' => $this->getPagesLimitForPlan($tenant->plan_type ?? 'free'),
                'api_keys_limit' => $this->getApiKeysLimitForPlan($tenant->plan_type ?? 'free'),
            ];

            if (request()->expectsJson()) {
                return response()->json([
                    'stats' => $stats,
                    'api_key_stats' => $apiKeyStats,
                    'page_stats' => $pageStats,
                    'pages_by_category' => $pagesByCategory,
                    'generation_trends' => $generationTrends,
                    'token_stats' => $tokenStats,
                    'recent_pages' => $recentPages,
                    'recent_generations' => $recentGenerations,
                    'top_pages' => $topPages,
                    'recent_activity' => $recentActivity,
                    'plan_info' => $planInfo,
                    'tenant' => $tenant
                ]);
            }

            return view('tenant.dashboard', compact(
                'stats', 'apiKeyStats', 'pageStats', 'campaignStats', 'pagesByCategory', 'generationTrends',
                'tokenStats', 'recentPages', 'recentGenerations', 'topPages', 'recentActivity',
                'planInfo', 'tenant'
            ));

        } catch (\Exception $e) {
            Log::error('Dashboard data loading failed', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json(['error' => 'Failed to load dashboard data'], 500);
            }

            // Provide default values for error state
            $planInfo = [
                'name' => ucfirst($tenant->plan_type ?? 'free'),
                'tokens_limit' => $tenant->tokens_monthly_limit ?? 0,
                'tokens_used' => $tenant->tokens_used_current ?? 0,
                'pages_limit' => 0,
                'api_keys_limit' => 0,
            ];

            $tokenStats = [
                'total_tokens_used' => $tenant->tokens_used_current ?? 0,
                'monthly_limit' => $tenant->tokens_monthly_limit ?? 0,
                'remaining_tokens' => 0,
                'usage_percent' => 0,
            ];

            $stats = [];
            $apiKeyStats = [];
            $pageStats = [];
            $pagesByCategory = collect();
            $generationTrends = [];
            $recentPages = collect();
            $recentGenerations = collect();
            $topPages = collect();
            $recentActivity = [];

            return view('tenant.dashboard', compact(
                'stats', 'apiKeyStats', 'pageStats', 'campaignStats', 'pagesByCategory', 'generationTrends',
                'tokenStats', 'recentPages', 'recentGenerations', 'topPages', 'recentActivity',
                'planInfo', 'tenant'
            ));
        }
    }

    public function pages(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $query = Page::where('tenant_id', $tenant->id)->with('contentGenerations');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('url_path', 'like', "%{$search}%")
                  ->orWhere('keyword', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        $pages = $query->orderBy('created_at', 'desc')->paginate(20);

        $categories = Page::where('tenant_id', $tenant->id)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort();

        return view('tenant.pages', compact('pages', 'categories'));
    }

    public function prompts()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $prompts = Prompt::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tenant.prompts', compact('prompts'));
    }

    public function contentGenerations(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $query = ContentGeneration::where('tenant_id', $tenant->id)->with('page');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('prompt_type')) {
            $query->where('prompt_type', $request->get('prompt_type'));
        }

        $generations = $query->orderBy('created_at', 'desc')->paginate(20);

        $statuses = ['pending', 'processing', 'completed', 'failed'];
        $promptTypes = ContentGeneration::where('tenant_id', $tenant->id)
            ->distinct()
            ->pluck('prompt_type')
            ->filter()
            ->sort();

        return view('tenant.content-generations', compact('generations', 'statuses', 'promptTypes'));
    }

    public function showGeneration(ContentGeneration $generation)
    {
        $user = Auth::user();

        if ($generation->tenant_id !== $user->tenant->id) {
            abort(403, 'Unauthorized access to this content generation');
        }

        return view('tenant.generation-detail', compact('generation'));
    }

    public function settings()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        return view('tenant.settings', compact('tenant', 'user'));
    }

    public function apiKeys()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $apiKeys = ApiKey::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tenant.api-keys', compact('apiKeys', 'tenant'));
    }

    public function createApiKey(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = Auth::user();
        $tenant = $user->tenant;

        // Generate secure API key
        $apiKey = 'ak_' . Str::random(32);

        $newApiKey = ApiKey::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'key' => hash('sha256', $apiKey), // Store hashed version
            'expires_at' => $request->expires_at,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API Key created successfully',
            'api_key' => $apiKey, // Return plain text only once
            'key_id' => $newApiKey->id,
        ]);
    }

    public function revokeApiKey(Request $request, $keyId)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $apiKey = ApiKey::where('id', $keyId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$apiKey) {
            return response()->json(['error' => 'API Key not found'], 404);
        }

        $apiKey->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => $user->id
        ]);

        Log::info('API key revoked from dashboard', [
            'api_key_id' => $apiKey->id,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API Key revoked successfully'
        ]);
    }

    /**
     * Get pages limit for a plan
     */
    private function getPagesLimitForPlan(string $planType): int
    {
        return match ($planType) {
            'free' => 10,
            'basic' => 50,
            'pro' => 200,
            'enterprise' => 1000,
            default => 10,
        };
    }

    /**
     * Get API keys limit for a plan
     */
    private function getApiKeysLimitForPlan(string $planType): int
    {
        return match ($planType) {
            'free' => 2,
            'basic' => 5,
            'pro' => 10,
            'enterprise' => 25,
            default => 2,
        };
    }

    /**
     * Get dashboard analytics data
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $period = $request->get('period', 'week'); // week, month, quarter, year

        try {
            $dateRange = $this->getDateRangeForPeriod($period);

            $analytics = [
                'period' => $period,
                'date_range' => $dateRange,
                'generations' => $this->getGenerationAnalytics($tenant->id, $dateRange),
                'pages' => $this->getPageAnalytics($tenant->id, $dateRange),
                'tokens' => $this->getTokenAnalytics($tenant->id, $dateRange),
                'performance' => $this->getPerformanceAnalytics($tenant->id, $dateRange),
            ];

            return response()->json(['data' => $analytics]);

        } catch (\Exception $e) {
            Log::error('Analytics data loading failed', [
                'tenant_id' => $tenant->id,
                'period' => $period,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to load analytics data'], 500);
        }
    }

    /**
     * Get date range for analytics period
     */
    private function getDateRangeForPeriod(string $period): array
    {
        $now = now();

        return match ($period) {
            'week' => ['start' => $now->copy()->subWeek(), 'end' => $now],
            'month' => ['start' => $now->copy()->subMonth(), 'end' => $now],
            'quarter' => ['start' => $now->copy()->subMonths(3), 'end' => $now],
            'year' => ['start' => $now->copy()->subYear(), 'end' => $now],
            default => ['start' => $now->copy()->subWeek(), 'end' => $now],
        };
    }

    /**
     * Get generation analytics for the period
     */
    private function getGenerationAnalytics(string $tenantId, array $dateRange): array
    {
        $query = ContentGeneration::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        return [
            'total' => $query->count(),
            'completed' => $query->where('status', 'completed')->count(),
            'failed' => $query->where('status', 'failed')->count(),
            'pending' => $query->where('status', 'pending')->count(),
            'processing' => $query->where('status', 'processing')->count(),
            'daily_breakdown' => $this->getDailyBreakdown($query, $dateRange),
        ];
    }

    /**
     * Get page analytics for the period
     */
    private function getPageAnalytics(string $tenantId, array $dateRange): array
    {
        $query = Page::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        return [
            'created' => $query->count(),
            'by_status' => $query->select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            'by_category' => $query->select('category', \DB::raw('count(*) as count'))
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderByDesc('count')
                ->get(),
        ];
    }

    /**
     * Get token analytics for the period
     */
    private function getTokenAnalytics(string $tenantId, array $dateRange): array
    {
        $query = ContentGeneration::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

        return [
            'total_used' => $query->sum('tokens_used') ?: 0,
            'average_per_generation' => $query->avg('tokens_used') ?: 0,
            'daily_usage' => $this->getDailyTokenUsage($query, $dateRange),
        ];
    }

    /**
     * Get performance analytics for the period
     */
    private function getPerformanceAnalytics(string $tenantId, array $dateRange): array
    {
        $totalGenerations = ContentGeneration::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $completedGenerations = ContentGeneration::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $successRate = $totalGenerations > 0 ? ($completedGenerations / $totalGenerations) * 100 : 0;

        return [
            'success_rate' => round($successRate, 2),
            'total_generations' => $totalGenerations,
            'completed_generations' => $completedGenerations,
            'average_completion_time' => $this->getAverageCompletionTime($tenantId, $dateRange),
        ];
    }

    /**
     * Get daily breakdown for analytics
     */
    private function getDailyBreakdown($query, array $dateRange): array
    {
        return $query->select(
                \DB::raw('DATE(created_at) as date'),
                \DB::raw('count(*) as count')
            )
            ->groupBy(\DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get daily token usage
     */
    private function getDailyTokenUsage($query, array $dateRange): array
    {
        return $query->select(
                \DB::raw('DATE(created_at) as date'),
                \DB::raw('SUM(tokens_used) as tokens')
            )
            ->groupBy(\DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get average completion time for successful generations
     */
    private function getAverageCompletionTime(string $tenantId, array $dateRange): ?float
    {
        return ContentGeneration::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('completed_at')
            ->avg(\DB::raw('TIMESTAMPDIFF(SECOND, created_at, completed_at)'));
    }
}