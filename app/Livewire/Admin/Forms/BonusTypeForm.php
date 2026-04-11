<?php

namespace App\Livewire\Admin\Forms;

use App\Enums\BonusTypeEnum;
use App\Models\BonusType;
use App\Models\Company;
use App\Models\CompensationPlan;
use Livewire\Component;

/**
 * Renders a dynamic bonus type form.
 *
 * This component is read-only in terms of data submission — it renders
 * the correct fields based on the selected type so the parent Blade form
 * can POST them to the controller. It uses Alpine.js for client-side
 * interactivity (add/remove tier rows) with Livewire for the type-switching
 * reactive section headers/descriptions.
 */
class BonusTypeForm extends Component
{
    public int $companyId;
    public int $planId;
    public ?int $bonusTypeId = null;

    // Common fields
    public string $name        = '';
    public string $description = '';
    public string $type        = '';
    public int    $priority    = 0;
    public bool   $isActive    = true;

    // Fast start fields
    public int    $durationDays   = 30;
    public float  $multiplierRate = 2.0;
    public string $appliesTo      = 'both';

    // Pool sharing fields
    public float  $poolPercent    = 5.0;
    public string $distributionMethod = 'equal';
    public int    $qualifyingMinRank  = 1;

    // Matching tiers (for matching type)
    public array $matchingTiers = [
        ['label' => '', 'rate' => ''],
    ];

    // Rank/leadership tiers
    public array $rankTiers = [
        ['label' => '', 'qualifier_value' => '', 'qualifier_type' => 'min_customers', 'bonus_amount' => ''],
    ];

    public array $leadershipTiers = [
        ['label' => '', 'qualifier_value' => '', 'qualifier_type' => 'min_customers', 'monthly_amount' => ''],
    ];

    public function mount(Company $company, CompensationPlan $plan, ?BonusType $bonusType = null): void
    {
        $this->companyId = $company->id;
        $this->planId    = $plan->id;

        if ($bonusType) {
            $this->bonusTypeId = $bonusType->id;
            $this->name        = $bonusType->name;
            $this->description = $bonusType->description ?? '';
            $this->type        = $bonusType->type->value;
            $this->priority    = $bonusType->priority;
            $this->isActive    = $bonusType->is_active;

            $this->populateTypeFields($bonusType);
        }
    }

    public function updatedType(): void
    {
        // Reset tier arrays when type changes to avoid stale data
        $this->matchingTiers    = [['label' => '', 'rate' => '']];
        $this->rankTiers        = [['label' => '', 'qualifier_value' => '', 'qualifier_type' => 'min_customers', 'bonus_amount' => '']];
        $this->leadershipTiers  = [['label' => '', 'qualifier_value' => '', 'qualifier_type' => 'min_customers', 'monthly_amount' => '']];
    }

    public function addMatchingTier(): void
    {
        $this->matchingTiers[] = ['label' => '', 'rate' => ''];
    }

    public function removeMatchingTier(int $index): void
    {
        if (count($this->matchingTiers) > 1) {
            unset($this->matchingTiers[$index]);
            $this->matchingTiers = array_values($this->matchingTiers);
        }
    }

    public function addRankTier(): void
    {
        $this->rankTiers[] = ['label' => '', 'qualifier_value' => '', 'qualifier_type' => 'min_customers', 'bonus_amount' => ''];
    }

    public function removeRankTier(int $index): void
    {
        if (count($this->rankTiers) > 1) {
            unset($this->rankTiers[$index]);
            $this->rankTiers = array_values($this->rankTiers);
        }
    }

    public function addLeadershipTier(): void
    {
        $this->leadershipTiers[] = ['label' => '', 'qualifier_value' => '', 'qualifier_type' => 'min_customers', 'monthly_amount' => ''];
    }

    public function removeLeadershipTier(int $index): void
    {
        if (count($this->leadershipTiers) > 1) {
            unset($this->leadershipTiers[$index]);
            $this->leadershipTiers = array_values($this->leadershipTiers);
        }
    }

    public function render()
    {
        $bonusTypeOptions = collect(BonusTypeEnum::cases())
            ->mapWithKeys(fn (BonusTypeEnum $case) => [$case->value => $case->label()])
            ->all();

        return view('livewire.admin.forms.bonus-type-form', [
            'bonusTypeOptions' => $bonusTypeOptions,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function populateTypeFields(BonusType $bonusType): void
    {
        $bonusType->load(['configs', 'tiers']);
        $configMap = $bonusType->configs->pluck('value', 'key')->all();

        match ($bonusType->type) {
            BonusTypeEnum::Matching => $this->populateMatching($bonusType),
            BonusTypeEnum::FastStart => $this->populateFastStart($configMap),
            BonusTypeEnum::RankAdvancement => $this->populateRankAdvancement($bonusType),
            BonusTypeEnum::PoolSharing => $this->populatePoolSharing($configMap),
            BonusTypeEnum::Leadership => $this->populateLeadership($bonusType),
        };
    }

    private function populateMatching(BonusType $bonusType): void
    {
        if ($bonusType->tiers->isNotEmpty()) {
            $this->matchingTiers = $bonusType->tiers->map(fn ($tier) => [
                'label' => $tier->label ?? '',
                'rate'  => $tier->rate !== null ? round((float) $tier->rate * 100, 4) : '',
            ])->all();
        }
    }

    private function populateFastStart(array $configMap): void
    {
        $this->durationDays  = (int) ($configMap['duration_days'] ?? 30);
        $this->multiplierRate = (float) ($configMap['multiplier_rate'] ?? 2.0);
        $this->appliesTo     = $configMap['applies_to'] ?? 'both';
    }

    private function populateRankAdvancement(BonusType $bonusType): void
    {
        if ($bonusType->tiers->isNotEmpty()) {
            $this->rankTiers = $bonusType->tiers->map(fn ($tier) => [
                'label'           => $tier->label ?? '',
                'qualifier_value' => $tier->qualifier_value ?? '',
                'qualifier_type'  => $tier->qualifier_type ?? 'min_customers',
                'bonus_amount'    => $tier->amount ?? '',
            ])->all();
        }
    }

    private function populatePoolSharing(array $configMap): void
    {
        $this->poolPercent        = isset($configMap['pool_percent'])
            ? round((float) $configMap['pool_percent'] * 100, 4)
            : 5.0;
        $this->distributionMethod = $configMap['distribution_method'] ?? 'equal';
        $this->qualifyingMinRank  = (int) ($configMap['qualifying_min_rank'] ?? 1);
    }

    private function populateLeadership(BonusType $bonusType): void
    {
        if ($bonusType->tiers->isNotEmpty()) {
            $this->leadershipTiers = $bonusType->tiers->map(fn ($tier) => [
                'label'           => $tier->label ?? '',
                'qualifier_value' => $tier->qualifier_value ?? '',
                'qualifier_type'  => $tier->qualifier_type ?? 'min_customers',
                'monthly_amount'  => $tier->amount ?? '',
            ])->all();
        }
    }
}
