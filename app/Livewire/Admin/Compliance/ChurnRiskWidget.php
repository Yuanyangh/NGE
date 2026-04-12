<?php

namespace App\Livewire\Admin\Compliance;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Services\Compliance\ChurnDetector;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ChurnRiskWidget extends Component
{
    #[Locked]
    public int $companyId;
    public string $scanDate;

    // Threshold fields for inline editing
    public string $atRiskDays      = '30';
    public string $inactiveDays    = '60';
    public string $volumeDeclinePct = '50';
    public string $stagnantLeaderDays = '60';

    public bool $thresholdsSaved = false;

    public function mount(int $companyId, string $scanDate): void
    {
        $this->companyId  = $companyId;
        $this->scanDate   = $scanDate;

        $this->atRiskDays         = CompanySetting::getValue($companyId, 'churn_at_risk_days', '30');
        $this->inactiveDays       = CompanySetting::getValue($companyId, 'churn_inactive_days', '60');
        $this->volumeDeclinePct   = CompanySetting::getValue($companyId, 'churn_volume_decline_pct', '50');
        $this->stagnantLeaderDays = CompanySetting::getValue($companyId, 'churn_stagnant_leader_days', '60');
    }

    public function saveThresholds(): void
    {
        $this->validate([
            'atRiskDays'         => ['required', 'integer', 'min:1', 'max:365'],
            'inactiveDays'       => ['required', 'integer', 'min:1', 'max:365'],
            'volumeDeclinePct'   => ['required', 'numeric', 'min:1', 'max:100'],
            'stagnantLeaderDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $settings = [
            'churn_at_risk_days'         => $this->atRiskDays,
            'churn_inactive_days'        => $this->inactiveDays,
            'churn_volume_decline_pct'   => $this->volumeDeclinePct,
            'churn_stagnant_leader_days' => $this->stagnantLeaderDays,
        ];

        foreach ($settings as $key => $value) {
            CompanySetting::withoutGlobalScopes()
                ->updateOrCreate(
                    ['company_id' => $this->companyId, 'key' => $key],
                    ['value' => $value],
                );
        }

        $this->thresholdsSaved = true;

        // Clear computed cache so table re-runs with new thresholds
        unset($this->churnResults);
    }

    #[Computed]
    public function churnResults(): Collection
    {
        $detector = app(ChurnDetector::class);

        return $detector->scan(
            companyId: $this->companyId,
            date: Carbon::parse($this->scanDate),
        );
    }

    #[Computed]
    public function summary(): array
    {
        $results = $this->churnResults;

        return [
            'inactive_warning'  => $results->where('risk_level', 'inactive_warning')->count(),
            'at_risk'           => $results->where('risk_level', 'at_risk')->count(),
            'declining'         => $results->where('risk_level', 'declining')->count(),
            'stagnant_leader'   => $results->where('risk_level', 'stagnant_leader')->count(),
            'total'             => $results->count(),
        ];
    }

    #[Computed]
    public function company(): Company
    {
        return Company::findOrFail($this->companyId);
    }

    public function render()
    {
        return view('livewire.admin.compliance.churn-risk-widget');
    }
}
