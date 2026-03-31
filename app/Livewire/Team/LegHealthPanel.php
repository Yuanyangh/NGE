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
    public string $period = 'month';

    public function mount(): void
    {
        $this->loadStats();
    }

    public function setDay(): void
    {
        $this->period = 'day';
        $this->loadStats();
    }

    public function setWeek(): void
    {
        $this->period = 'week';
        $this->loadStats();
    }

    public function setMonth(): void
    {
        $this->period = 'month';
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $this->stats = null;

        $user = auth()->user();
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return;
        }

        $configArray = $plan->config;
        $configArray['qualification']['rolling_days'] = match ($this->period) {
            'day' => 1,
            'week' => 7,
            default => 30,
        };

        $config = PlanConfig::fromArray($configArray);
        $service = app(TeamStatsService::class);
        $this->stats = $service->calculate($user, Carbon::today(), $config)->toArray();
    }

    public function render()
    {
        return view('livewire.team.leg-health-panel');
    }
}
