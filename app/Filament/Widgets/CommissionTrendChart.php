<?php

namespace App\Filament\Widgets;

use App\Models\CommissionRun;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class CommissionTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Commission Trend (30 Days)';

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $runs = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->where('status', 'completed')
            ->whereBetween('run_date', [$startDate, $endDate])
            ->selectRaw('DATE(run_date) as date, SUM(total_affiliate_commission) as affiliate, SUM(total_viral_commission) as viral')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $period = CarbonPeriod::create($startDate, $endDate);
        $labels = [];
        $affiliateData = [];
        $viralData = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $affiliateData[] = (float) ($runs[$key]->affiliate ?? 0);
            $viralData[] = (float) ($runs[$key]->viral ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Affiliate Commissions',
                    'data' => $affiliateData,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Viral Commissions',
                    'data' => $viralData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
