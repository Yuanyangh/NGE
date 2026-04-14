<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Company;
use App\Services\Reporting\KpiDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConsolidatedDashboard extends Component
{
    public ?int $selectedCompanyId = null;
    public string $startDate;
    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->subDays(30)->toDateString();
        $this->endDate   = now()->toDateString();
    }

    public function updatedSelectedCompanyId(): void
    {
        // Triggers automatic re-render via reactive property
    }

    public function regenerate(): void
    {
        $this->validate([
            'startDate' => ['required', 'date', 'before_or_equal:endDate'],
            'endDate'   => ['required', 'date', 'after_or_equal:startDate'],
        ]);
    }

    #[Computed]
    public function companies(): Collection
    {
        return Company::orderBy('name')->get();
    }

    #[Computed]
    public function report(): array
    {
        $service = app(KpiDashboardService::class);
        $start   = Carbon::parse($this->startDate)->startOfDay();
        $end     = Carbon::parse($this->endDate)->endOfDay();

        if ($this->selectedCompanyId) {
            $company = $this->companies->firstWhere('id', $this->selectedCompanyId);

            return [
                'mode'    => 'single',
                'company' => $company,
                'data'    => $service->generate($this->selectedCompanyId, $start, $end),
            ];
        }

        // All-companies aggregate mode
        $perCompany = [];
        $totals = [
            'volume'      => '0',
            'commissions' => '0',
            'bonuses'     => '0',
            'affiliates'  => 0,
            'total_affiliates' => 0,
            'customers'   => 0,
            'enrollments' => 0,
            'run_count'   => 0,
            'viral_caps'  => 0,
            'payout'      => '0',
        ];

        $allTopEarners = [];

        foreach ($this->companies as $company) {
            $kpi = $service->generate($company->id, $start, $end);

            $perCompany[] = [
                'company' => $company,
                'kpi'     => $kpi,
            ];

            $totals['volume']           = bcadd($totals['volume'], $kpi->totalVolume, 4);
            $totals['commissions']      = bcadd($totals['commissions'], $kpi->totalCommissions, 4);
            $totals['bonuses']          = bcadd($totals['bonuses'], $kpi->totalBonuses, 4);
            $totals['affiliates']       += $kpi->activeAffiliates;
            $totals['total_affiliates'] += $kpi->totalAffiliates;
            $totals['customers']        += $kpi->activeCustomers;
            $totals['enrollments']      += $kpi->newEnrollments;
            $totals['run_count']        += $kpi->commissionRunCount;
            $totals['viral_caps']       += $kpi->viralCapTriggeredCount;

            foreach ($kpi->topEarners as $earner) {
                $allTopEarners[] = array_merge($earner, ['company_name' => $company->name]);
            }
        }

        // Combined payout = commissions + bonuses
        $totalPayout = bcadd($totals['commissions'], $totals['bonuses'], 4);
        $payoutRatio = bccomp($totals['volume'], '0', 4) > 0
            ? bcmul(bcdiv($totalPayout, $totals['volume'], 6), '100', 2)
            : '0.00';

        // Global top 5 earners: merge across companies, sort by earnings
        usort($allTopEarners, fn ($a, $b) => bccomp($b['total_earnings'], $a['total_earnings'], 4));
        $globalTopEarners = array_slice($allTopEarners, 0, 5);

        // Period-over-period aggregated changes — average across companies that have data
        $volumeChanges      = array_column(array_map(fn ($r) => $r['kpi'], $perCompany), 'volumeChange');
        $commissionChanges  = array_column(array_map(fn ($r) => $r['kpi'], $perCompany), 'commissionChange');
        $affiliateChanges   = array_column(array_map(fn ($r) => $r['kpi'], $perCompany), 'affiliateChange');
        $enrollmentChanges  = array_column(array_map(fn ($r) => $r['kpi'], $perCompany), 'enrollmentChange');

        $avgVolumeChange     = $this->averageChanges($volumeChanges);
        $avgCommChange       = $this->averageChanges($commissionChanges);
        $avgAffChange        = $this->averageChanges($affiliateChanges);
        $avgEnrollChange     = $this->averageChanges($enrollmentChanges);

        return [
            'mode'        => 'all',
            'totals'      => [
                'volume'         => bcadd($totals['volume'], '0', 2),
                'commissions'    => bcadd($totals['commissions'], '0', 2),
                'bonuses'        => bcadd($totals['bonuses'], '0', 2),
                'payout_ratio'   => $payoutRatio,
                'affiliates'     => $totals['affiliates'],
                'total_affiliates' => $totals['total_affiliates'],
                'customers'      => $totals['customers'],
                'enrollments'    => $totals['enrollments'],
                'run_count'      => $totals['run_count'],
                'viral_caps'     => $totals['viral_caps'],
                'volume_change'      => $avgVolumeChange,
                'commission_change'  => $avgCommChange,
                'affiliate_change'   => $avgAffChange,
                'enrollment_change'  => $avgEnrollChange,
            ],
            'perCompany'  => $perCompany,
            'topEarners'  => $globalTopEarners,
        ];
    }

    private function averageChanges(array $changes): string
    {
        if (empty($changes)) {
            return '0.00';
        }

        $sum = array_reduce($changes, fn ($carry, $item) => bcadd($carry, $item, 4), '0');

        return bcdiv($sum, (string) count($changes), 2);
    }

    public function render()
    {
        return view('livewire.admin.dashboard.consolidated-dashboard');
    }
}
