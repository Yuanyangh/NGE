<?php

namespace App\Livewire\Dashboard;

use App\DTOs\PlanConfig;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use App\Services\Commission\LegAggregator;
use App\Services\Commission\QualificationEvaluator;
use App\Services\Commission\QvvCalculator;
use Carbon\Carbon;
use Livewire\Component;

class QualificationStatus extends Component
{
    public ?array $qualification = null;

    public function mount(
        QualificationEvaluator $evaluator,
        LegAggregator $legAggregator,
        QvvCalculator $qvvCalculator,
    ): void {
        $user = auth()->user();
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return;
        }

        $config = PlanConfig::fromArray($plan->config);
        $result = $evaluator->evaluate($user, Carbon::today(), $config);
        $data = $result->toArray();

        // Compute effective viral tier using actual QVV
        $legVolumes = $legAggregator->getLegVolumes($user, Carbon::today(), $config);
        $volumeSnapshot = $qvvCalculator->calculate($legVolumes, $config);
        $currentQvv = $volumeSnapshot->qualifying_viral_volume;

        $viralTierIndex = $evaluator->matchViralTierWithQvv(
            $result->active_customer_count,
            $result->referred_volume_30d,
            $currentQvv,
            $config
        );

        // Replace the viral tier reason with the effective QVV-based tier
        $data['reasons'] = array_map(function (string $reason) use ($viralTierIndex, $config, $currentQvv) {
            if (str_starts_with($reason, 'Viral tier')) {
                if ($viralTierIndex !== null) {
                    $vt = $config->viral_tiers[$viralTierIndex];
                    return sprintf(
                        'Viral tier %d qualified (QVV: %s XP, earns $%s/day)',
                        $vt->tier,
                        number_format((float) $currentQvv, 0),
                        number_format($vt->daily_reward, 2)
                    );
                }
                return 'No viral tier qualified (insufficient QVV)';
            }
            return $reason;
        }, $data['reasons']);

        $this->qualification = $data;
    }

    public function render()
    {
        return view('livewire.dashboard.qualification-status');
    }
}
