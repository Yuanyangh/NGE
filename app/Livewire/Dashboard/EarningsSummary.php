<?php

namespace App\Livewire\Dashboard;

use App\DTOs\PlanConfig;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use App\Services\Affiliate\AffiliateDashboardService;
use Carbon\Carbon;
use Livewire\Component;

class EarningsSummary extends Component
{
    public string $totalEarned30d = '0';
    public string $pendingAmount = '0';
    public string $walletBalance = '0';
    public ?int $currentAffiliateTier = null;
    public ?float $currentAffiliateRate = null;
    public ?int $currentViralTier = null;
    public ?float $currentViralDailyReward = null;

    public function mount(AffiliateDashboardService $service): void
    {
        $user = auth()->user();
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return;
        }

        $config = PlanConfig::fromArray($plan->config);
        $data = $service->getDashboardData($user, Carbon::today(), $config);

        $this->totalEarned30d = $data->total_earned_30d;
        $this->pendingAmount = $data->pending_amount;
        $this->walletBalance = $data->wallet_balance;
        $this->currentAffiliateTier = $data->current_affiliate_tier;
        $this->currentAffiliateRate = $data->current_affiliate_rate;
        $this->currentViralTier = $data->current_viral_tier;
        $this->currentViralDailyReward = $data->current_viral_daily_reward;
    }

    public function render()
    {
        return view('livewire.dashboard.earnings-summary');
    }
}
