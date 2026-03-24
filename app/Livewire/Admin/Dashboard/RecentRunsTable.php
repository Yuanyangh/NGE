<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\CommissionRun;
use App\Scopes\CompanyScope;
use Livewire\Component;

class RecentRunsTable extends Component
{
    public function render()
    {
        $runs = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->with('company')
            ->latest('run_date')
            ->limit(10)
            ->get();

        return view('livewire.admin.dashboard.recent-runs-table', [
            'runs' => $runs,
        ]);
    }
}
