<?php

namespace App\Livewire\Admin\Dashboard;

use App\DTOs\BreakageData;
use App\DTOs\KpiDashboardData;
use App\Services\Reporting\BreakageAnalysisService;
use App\Services\Reporting\KpiDashboardService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class KpiDashboard extends Component
{
    #[Locked]
    public int $companyId;

    public string $startDate;
    public string $endDate;

    public function mount(int $companyId, string $startDate, string $endDate): void
    {
        $this->companyId = $companyId;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function regenerate(): void
    {
        $this->validate([
            'startDate' => ['required', 'date', 'before_or_equal:endDate'],
            'endDate'   => ['required', 'date', 'after_or_equal:startDate'],
        ]);
    }

    #[Computed]
    public function kpi(): KpiDashboardData
    {
        return app(KpiDashboardService::class)->generate(
            $this->companyId,
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        );
    }

    #[Computed]
    public function breakage(): BreakageData
    {
        return app(BreakageAnalysisService::class)->analyze(
            $this->companyId,
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        );
    }

    public function render()
    {
        return view('livewire.admin.dashboard.kpi-dashboard');
    }
}
