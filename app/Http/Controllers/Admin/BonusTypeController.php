<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BonusTypeEnum;
use App\Http\Controllers\Admin\Concerns\EnforcesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\BonusTier;
use App\Models\BonusType;
use App\Models\BonusTypeConfig;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BonusTypeController extends Controller
{
    use EnforcesCompanyAccess;

    public function index(Company $company, CompensationPlan $compensationPlan): View
    {
        $this->authorizeCompanyAccess($company);
        $this->authorisePlan($company, $compensationPlan);

        $bonusTypes = BonusType::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->where('compensation_plan_id', $compensationPlan->id)
            ->orderBy('priority')
            ->get();

        return view('admin.bonus-types.index', [
            'company'    => $company,
            'plan'       => $compensationPlan,
            'bonusTypes' => $bonusTypes,
        ]);
    }

    public function create(Company $company, CompensationPlan $compensationPlan): View
    {
        $this->authorizeCompanyAccess($company);
        $this->authorisePlan($company, $compensationPlan);

        return view('admin.bonus-types.create', [
            'company' => $company,
            'plan'    => $compensationPlan,
        ]);
    }

    public function store(Request $request, Company $company, CompensationPlan $compensationPlan): RedirectResponse
    {
        $this->authorizeCompanyAccess($company);
        $this->authorisePlan($company, $compensationPlan);

        $validated = $request->validate(array_merge($this->commonRules(), $this->typeSpecificRules($request)));
        $type = BonusTypeEnum::from($validated['type']);

        $bonusType = BonusType::create([
            'company_id'           => $company->id,
            'compensation_plan_id' => $compensationPlan->id,
            'type'                 => $type,
            'name'                 => $validated['name'],
            'description'          => $validated['description'] ?? null,
            'is_active'            => (bool) ($validated['is_active'] ?? false),
            'priority'             => (int) ($validated['priority'] ?? 0),
        ]);

        $this->saveTypeConfig($request, $bonusType, $type);

        return redirect()
            ->route('admin.companies.plans.bonus-types.index', [$company, $compensationPlan])
            ->with('success', 'Bonus type created successfully.');
    }

    public function edit(Company $company, CompensationPlan $compensationPlan, BonusType $bonusType): View
    {
        $this->authorizeCompanyAccess($company);
        $this->authorisePlan($company, $compensationPlan);
        $this->authoriseBonusType($company, $compensationPlan, $bonusType);

        $bonusType->load(['configs', 'tiers']);

        $configMap = $bonusType->configs->pluck('value', 'key')->all();

        return view('admin.bonus-types.edit', [
            'company'   => $company,
            'plan'      => $compensationPlan,
            'bonusType' => $bonusType,
            'configMap' => $configMap,
        ]);
    }

    public function update(Request $request, Company $company, CompensationPlan $compensationPlan, BonusType $bonusType): RedirectResponse
    {
        $this->authorizeCompanyAccess($company);
        $this->authorisePlan($company, $compensationPlan);
        $this->authoriseBonusType($company, $compensationPlan, $bonusType);

        $validated = $request->validate(array_merge($this->commonRules(), $this->typeSpecificRules($request)));
        $type = BonusTypeEnum::from($validated['type']);

        $bonusType->update([
            'type'        => $type,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => (bool) ($validated['is_active'] ?? false),
            'priority'    => (int) ($validated['priority'] ?? 0),
        ]);

        // Delete old config/tiers and re-save fresh
        $bonusType->configs()->delete();
        $bonusType->tiers()->delete();

        $this->saveTypeConfig($request, $bonusType, $type);

        return redirect()
            ->route('admin.companies.plans.bonus-types.index', [$company, $compensationPlan])
            ->with('success', 'Bonus type updated successfully.');
    }

    public function toggleActive(Company $company, CompensationPlan $compensationPlan, BonusType $bonusType): RedirectResponse
    {
        $this->authorizeCompanyAccess($company);
        $this->authorisePlan($company, $compensationPlan);
        $this->authoriseBonusType($company, $compensationPlan, $bonusType);

        $bonusType->update(['is_active' => ! $bonusType->is_active]);

        return back()->with('success', 'Bonus type status updated.');
    }

    public function destroy(Company $company, CompensationPlan $compensationPlan, BonusType $bonusType): RedirectResponse
    {
        $this->authorizeCompanyAccess($company);
        $this->authorisePlan($company, $compensationPlan);
        $this->authoriseBonusType($company, $compensationPlan, $bonusType);

        $bonusType->configs()->delete();
        $bonusType->tiers()->delete();
        $bonusType->delete();

        return redirect()
            ->route('admin.companies.plans.bonus-types.index', [$company, $compensationPlan])
            ->with('success', 'Bonus type deleted.');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** Abort 404 if the plan does not belong to the company. */
    private function authorisePlan(Company $company, CompensationPlan $compensationPlan): void
    {
        if ((int) $compensationPlan->company_id !== (int) $company->id) {
            abort(404);
        }
    }

    /** Abort 404 if the bonus type does not belong to this company + plan. */
    private function authoriseBonusType(Company $company, CompensationPlan $compensationPlan, BonusType $bonusType): void
    {
        if (
            (int) $bonusType->company_id !== (int) $company->id
            || (int) $bonusType->compensation_plan_id !== (int) $compensationPlan->id
        ) {
            abort(404);
        }
    }

    /** Type-specific validation rules based on the submitted bonus type. */
    private function typeSpecificRules(Request $request): array
    {
        $type = $request->input('type');
        $rules = [];

        if ($type === BonusTypeEnum::FastStart->value) {
            $rules['applies_to'] = ['nullable', 'string', 'in:affiliate,viral,both'];
        }

        if ($type === BonusTypeEnum::PoolSharing->value) {
            $rules['distribution_method'] = ['nullable', 'string', 'in:equal,volume_weighted'];
        }

        if (in_array($type, [BonusTypeEnum::RankAdvancement->value, BonusTypeEnum::Leadership->value], true)) {
            $rules['tiers.*.qualifier_type'] = ['nullable', 'string', 'in:min_qvv,min_referred_volume,min_active_customers'];
        }

        return $rules;
    }

    /** Validation rules shared by store and update. */
    private function commonRules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type'        => ['required', 'string', 'in:' . implode(',', array_column(BonusTypeEnum::cases(), 'value'))],
            'priority'    => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable'],
        ];
    }

    /** Persist type-specific configs and tiers after creating/updating the BonusType record. */
    private function saveTypeConfig(Request $request, BonusType $bonusType, BonusTypeEnum $type): void
    {
        match ($type) {
            BonusTypeEnum::Matching        => $this->saveMatching($request, $bonusType),
            BonusTypeEnum::FastStart       => $this->saveFastStart($request, $bonusType),
            BonusTypeEnum::RankAdvancement => $this->saveRankAdvancement($request, $bonusType),
            BonusTypeEnum::PoolSharing     => $this->savePoolSharing($request, $bonusType),
            BonusTypeEnum::Leadership      => $this->saveLeadership($request, $bonusType),
        };
    }

    private function saveMatching(Request $request, BonusType $bonusType): void
    {
        $tiers = $request->input('tiers', []);
        foreach ($tiers as $index => $tier) {
            if (empty($tier['rate'])) {
                continue;
            }
            BonusTier::create([
                'bonus_type_id'   => $bonusType->id,
                'level'           => $index + 1,
                'label'           => $tier['label'] ?? null,
                'rate'            => bcdiv((string) $tier['rate'], '100', 6),
                'qualifier_value' => null,
                'qualifier_type'  => null,
                'amount'          => null,
            ]);
        }
    }

    private function saveFastStart(Request $request, BonusType $bonusType): void
    {
        BonusTypeConfig::insert([
            ['bonus_type_id' => $bonusType->id, 'key' => 'duration_days',   'value' => (string) (int) $request->input('duration_days', 30)],
            ['bonus_type_id' => $bonusType->id, 'key' => 'multiplier_rate', 'value' => (string) $request->input('multiplier_rate', '2')],
            ['bonus_type_id' => $bonusType->id, 'key' => 'applies_to',      'value' => $request->input('applies_to', 'both')],
        ]);
    }

    private function saveRankAdvancement(Request $request, BonusType $bonusType): void
    {
        $tiers = $request->input('tiers', []);
        foreach ($tiers as $index => $tier) {
            if (empty($tier['bonus_amount'])) {
                continue;
            }
            BonusTier::create([
                'bonus_type_id'   => $bonusType->id,
                'level'           => $index + 1,
                'label'           => $tier['label'] ?? null,
                'qualifier_value' => isset($tier['qualifier_value']) ? (string) $tier['qualifier_value'] : null,
                'qualifier_type'  => $tier['qualifier_type'] ?? null,
                'rate'            => null,
                'amount'          => $tier['bonus_amount'],
            ]);
        }
    }

    private function savePoolSharing(Request $request, BonusType $bonusType): void
    {
        BonusTypeConfig::insert([
            ['bonus_type_id' => $bonusType->id, 'key' => 'pool_percent',        'value' => bcdiv((string) $request->input('pool_percent', '5'), '100', 6)],
            ['bonus_type_id' => $bonusType->id, 'key' => 'distribution_method', 'value' => $request->input('distribution_method', 'equal')],
            ['bonus_type_id' => $bonusType->id, 'key' => 'qualifying_min_rank', 'value' => (string) (int) $request->input('qualifying_min_rank', 1)],
        ]);
    }

    private function saveLeadership(Request $request, BonusType $bonusType): void
    {
        $tiers = $request->input('tiers', []);
        foreach ($tiers as $index => $tier) {
            if (empty($tier['monthly_amount'])) {
                continue;
            }
            BonusTier::create([
                'bonus_type_id'   => $bonusType->id,
                'level'           => $index + 1,
                'label'           => $tier['label'] ?? null,
                'qualifier_value' => isset($tier['qualifier_value']) ? (string) $tier['qualifier_value'] : null,
                'qualifier_type'  => $tier['qualifier_type'] ?? null,
                'rate'            => null,
                'amount'          => $tier['monthly_amount'],
            ]);
        }
    }
}
