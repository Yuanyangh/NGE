<?php

namespace App\Livewire\Commissions;

use App\DTOs\PlanConfig;
use App\Models\CommissionLedgerEntry;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class CommissionHistory extends Component
{
    use WithPagination;

    public string $tab = 'overview';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    public array $affiliateRateMap = [];
    public array $viralRewardMap = [];

    public function mount(): void
    {
        $user = auth()->user();
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->first();

        if ($plan) {
            $config = PlanConfig::fromArray($plan->config);
            foreach ($config->affiliate_tiers as $i => $tier) {
                $this->affiliateRateMap[$i + 1] = number_format($tier->rate * 100, 0) . '%';
            }
            foreach ($config->viral_tiers as $tier) {
                $this->viralRewardMap[$tier->tier] = '$' . number_format($tier->daily_reward, 2) . '/day';
            }
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function updatingFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->orderByDesc('created_at');

        if ($this->tab === 'affiliate') {
            $query->where('type', 'affiliate_commission');
        } elseif ($this->tab === 'viral') {
            $query->where('type', 'viral_commission');
        }

        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        return view('livewire.commissions.commission-history', [
            'entries' => $query->paginate(15),
        ]);
    }
}
