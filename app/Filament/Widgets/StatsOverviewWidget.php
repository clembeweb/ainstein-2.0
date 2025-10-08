<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Page;
use App\Models\ContentGeneration;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Tenants', Tenant::count())
                ->description('Active organizations')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Total Pages', Page::count())
                ->description('Content pages managed')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Generations', ContentGeneration::count())
                ->description('AI content generated')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),

            Stat::make('Tokens Used', ContentGeneration::sum('tokens_used'))
                ->description('Total OpenAI tokens consumed')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('gray'),

            Stat::make('Active Tenants', Tenant::where('status', 'active')->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
