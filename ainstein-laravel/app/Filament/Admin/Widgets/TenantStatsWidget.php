<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Tenant;
use App\Models\User;
use App\Models\ContentGeneration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TenantStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Total tenants
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();

        // Total users
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();

        // Token usage across all tenants
        $totalTokensUsed = Tenant::sum('tokens_used_current');
        $totalTokensLimit = Tenant::sum('tokens_monthly_limit');
        $tokenUsagePercent = $totalTokensLimit > 0
            ? round(($totalTokensUsed / $totalTokensLimit) * 100, 1)
            : 0;

        // Content generations
        $totalGenerations = ContentGeneration::count();
        $todayGenerations = ContentGeneration::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Tenants', $totalTenants)
                ->description("{$activeTenants} active tenants")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success')
                ->chart([7, 12, 15, 18, 20, 22, $totalTenants]),

            Stat::make('Total Users', $totalUsers)
                ->description("{$activeUsers} active users")
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([10, 25, 40, 55, 70, 85, $totalUsers]),

            Stat::make('Token Usage', number_format($totalTokensUsed))
                ->description("{$tokenUsagePercent}% of {$this->formatNumber($totalTokensLimit)} limit")
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($tokenUsagePercent > 80 ? 'danger' : ($tokenUsagePercent > 50 ? 'warning' : 'success'))
                ->chart($this->getTokenUsageChart()),

            Stat::make('Content Generations', number_format($totalGenerations))
                ->description("{$todayGenerations} generated today")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart($this->getGenerationsChart()),
        ];
    }

    protected function formatNumber($number): string
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    protected function getTokenUsageChart(): array
    {
        // Get last 7 days token usage
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $usage = ContentGeneration::whereDate('created_at', $date)->sum('tokens_used') ?? 0;
            $data[] = $usage;
        }
        return $data;
    }

    protected function getGenerationsChart(): array
    {
        // Get last 7 days generations count
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $count = ContentGeneration::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }
}
