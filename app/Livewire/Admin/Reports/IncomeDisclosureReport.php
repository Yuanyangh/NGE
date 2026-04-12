<?php

namespace App\Livewire\Admin\Reports;

use App\DTOs\IncomeDisclosureData;
use App\Models\Company;
use App\Services\Reporting\IncomeDisclosureService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class IncomeDisclosureReport extends Component
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
    public function report(): IncomeDisclosureData
    {
        $service = app(IncomeDisclosureService::class);

        return $service->generate(
            $this->companyId,
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        );
    }

    #[Computed]
    public function company(): Company
    {
        return Company::findOrFail($this->companyId);
    }

    public function render()
    {
        return view('livewire.admin.reports.income-disclosure-report');
    }
}
