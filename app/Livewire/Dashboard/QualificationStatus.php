<?php

namespace App\Livewire\Dashboard;

use App\DTOs\PlanConfig;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use App\Services\Commission\QualificationEvaluator;
use Carbon\Carbon;
use Livewire\Component;

class QualificationStatus extends Component
{
    public ?array $qualification = null;

    public function mount(QualificationEvaluator $evaluator): void
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
        $this->qualification = $evaluator->evaluate($user, Carbon::today(), $config)->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.qualification-status');
    }
}
