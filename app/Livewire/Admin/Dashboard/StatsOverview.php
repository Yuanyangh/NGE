<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Company;
use App\Models\CommissionRun;
use App\Models\User;
use App\Scopes\CompanyScope;
use Livewire\Component;

class StatsOverview extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard.stats-overview', [
            'totalCompanies' => Company::where('is_active', true)->count(),
            'activeAffiliates' => User::withoutGlobalScope(CompanyScope::class)
                ->where('role', 'affiliate')
                ->where('status', 'active')
                ->count(),
            'completedRuns' => CommissionRun::withoutGlobalScope(CompanyScope::class)
                ->where('status', 'completed')
                ->where('run_date', '>=', now()->subDays(30))
                ->count(),
            'totalPaid' => (float) CommissionRun::withoutGlobalScope(CompanyScope::class)
                ->where('status', 'completed')
                ->where('run_date', '>=', now()->subDays(30))
                ->selectRaw('COALESCE(SUM(total_affiliate_commission + total_viral_commission), 0) as total')
                ->value('total'),
        ]);
    }
}
