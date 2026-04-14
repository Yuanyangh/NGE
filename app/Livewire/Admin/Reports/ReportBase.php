<?php

namespace App\Livewire\Admin\Reports;

use Carbon\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Component;

abstract class ReportBase extends Component
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

    protected function start(): Carbon
    {
        return Carbon::parse($this->startDate)->startOfDay();
    }

    protected function end(): Carbon
    {
        return Carbon::parse($this->endDate)->endOfDay();
    }
}
