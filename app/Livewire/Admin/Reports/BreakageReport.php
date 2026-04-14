<?php

namespace App\Livewire\Admin\Reports;

use App\DTOs\BreakageData;
use App\Services\Reporting\BreakageAnalysisService;
use Livewire\Attributes\Computed;

class BreakageReport extends ReportBase
{
    #[Computed]
    public function data(): BreakageData
    {
        return app(BreakageAnalysisService::class)->analyze(
            $this->companyId,
            $this->start(),
            $this->end(),
        );
    }

    public function render()
    {
        return view('livewire.admin.reports.breakage-report');
    }
}
