<?php

namespace App\Filament\Widgets;

use App\Models\CommissionRun;
use App\Models\Company;
use App\Models\User;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $totalCompanies = Company::where('is_active', true)->count();

        $activeAffiliates = User::withoutGlobalScope(CompanyScope::class)
            ->where('role', 'affiliate')
            ->where('status', 'active')
            ->count();

        $completedRuns = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->where('status', 'completed')
            ->where('run_date', '>=', $thirtyDaysAgo)
            ->count();

        $totalPaid = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->where('status', 'completed')
            ->where('run_date', '>=', $thirtyDaysAgo)
            ->selectRaw('COALESCE(SUM(total_affiliate_commission + total_viral_commission), 0) as total')
            ->value('total');

        return [
            Stat::make('Total Companies', $totalCompanies)
                ->description('Active companies on platform')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Active Affiliates', number_format($activeAffiliates))
                ->description('Currently active affiliates')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Commission Runs (30d)', $completedRuns)
                ->description('Completed in last 30 days')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info'),

            Stat::make('Total Commissions Paid (30d)', '$' . number_format((float) $totalPaid, 2))
                ->description('Affiliate + Viral commissions')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
