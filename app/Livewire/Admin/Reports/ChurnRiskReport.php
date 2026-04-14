<?php

namespace App\Livewire\Admin\Reports;

use App\Services\Compliance\ChurnDetector;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ChurnRiskReport extends ReportBase
{
    #[Computed]
    public function results(): Collection
    {
        // Churn detection uses "as of today", not a date range
        return app(ChurnDetector::class)->scan($this->companyId, Carbon::today());
    }

    #[Computed]
    public function summary(): array
    {
        $all = $this->results;
        return [
            'inactive_warning' => $all->where('risk_level', 'inactive_warning')->count(),
            'at_risk'          => $all->where('risk_level', 'at_risk')->count(),
            'declining'        => $all->where('risk_level', 'declining')->count(),
            'stagnant_leader'  => $all->where('risk_level', 'stagnant_leader')->count(),
            'total'            => $all->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports.churn-risk-report');
    }
}
