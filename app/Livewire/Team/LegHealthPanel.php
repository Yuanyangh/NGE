<?php

namespace App\Livewire\Team;

use App\DTOs\PlanConfig;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use App\Services\Affiliate\TeamStatsService;
use Carbon\Carbon;
use Livewire\Component;

class LegHealthPanel extends Component
{
    public ?array $stats = null;

    public function mount(TeamStatsService $service): void
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
        $this->stats = $service->calculate($user, Carbon::today(), $config)->toArray();
    }

    public function render()
    {
        return view('livewire.team.leg-health-panel');
    }
}
