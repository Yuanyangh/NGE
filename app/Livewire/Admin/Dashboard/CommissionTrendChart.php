<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\CommissionRun;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;

class CommissionTrendChart extends Component
{
    public function render()
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

        $labels = [];
        $affiliateData = [];
        $viralData = [];

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $day) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M j');
            $affiliateData[] = (float) ($runs[$key]->affiliate ?? 0);
            $viralData[] = (float) ($runs[$key]->viral ?? 0);
        }

        return view('livewire.admin.dashboard.commission-trend-chart', [
            'chartLabels' => $labels,
            'affiliateData' => $affiliateData,
            'viralData' => $viralData,
        ]);
    }
}
