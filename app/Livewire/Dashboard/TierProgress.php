<?php

namespace App\Livewire\Dashboard;

use App\DTOs\PlanConfig;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use App\Services\Affiliate\TierProgressService;
use Carbon\Carbon;
use Livewire\Component;

class TierProgress extends Component
{
    public ?array $progress = null;

    public function mount(TierProgressService $service): void
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
        $this->progress = $service->calculate($user, Carbon::today(), $config)->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.tier-progress');
    }
}
