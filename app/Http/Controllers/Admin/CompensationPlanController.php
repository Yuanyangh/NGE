<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompensationPlanController extends Controller
{
    public function index(): View
    {
        return view('admin.compensation-plans.index');
    }

    public function create(): View
    {
        $companies = Company::orderBy('name')->pluck('name', 'id');

        $defaults = $this->defaultConfigValues();

        return view('admin.compensation-plans.create', compact('companies', 'defaults'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'         => ['required', 'exists:companies,id'],
            'name'               => ['required', 'string', 'max:255'],
            'version'            => ['required', 'string', 'max:255'],
            'effective_from'     => ['required', 'date'],
            'effective_until'    => ['nullable', 'date'],
            'is_active'          => ['nullable'],

            // Plan settings
            'config.plan.currency'               => ['required', 'string', 'in:USD,EUR,GBP,CAD,AUD,MYR'],
            'config.plan.calculation_frequency'  => ['required', 'string', 'in:daily,weekly,monthly'],
            'config.plan.credit_frequency'       => ['required', 'string', 'in:weekly,monthly'],
            'config.plan.timezone'               => ['required', 'string'],

            // Qualification
            'config.qualification.rolling_days'                      => ['required', 'integer', 'min:1'],
            'config.qualification.active_customer_min_order_xp'      => ['required', 'numeric', 'min:0'],
            'config.qualification.active_customer_threshold_type'    => ['required', 'string', 'in:per_order,cumulative'],

            // Affiliate commission
            'config.affiliate_commission.payout_method'                => ['required', 'string', 'in:daily_new_volume,cumulative'],
            'config.affiliate_commission.self_purchase_earns_commission' => ['nullable'],
            'config.affiliate_commission.includes_smartship'           => ['nullable'],
            'config.affiliate_commission.tiers'                        => ['required', 'array', 'min:1'],
            'config.affiliate_commission.tiers.*.min_active_customers' => ['required', 'integer', 'min:0'],
            'config.affiliate_commission.tiers.*.min_referred_volume'  => ['required', 'numeric', 'min:0'],
            'config.affiliate_commission.tiers.*.rate'                 => ['required', 'numeric', 'min:0', 'max:100'],

            // Viral commission
            'config.viral_commission.tree'                           => ['required', 'string', 'in:enrollment,placement'],
            'config.viral_commission.tiers'                          => ['required', 'array', 'min:1'],
            'config.viral_commission.tiers.*.min_active_customers'   => ['required', 'integer', 'min:0'],
            'config.viral_commission.tiers.*.min_referred_volume'    => ['required', 'numeric', 'min:0'],
            'config.viral_commission.tiers.*.min_qvv'                => ['required', 'numeric', 'min:0'],
            'config.viral_commission.tiers.*.daily_reward'           => ['required', 'numeric', 'min:0'],

            // Caps
            'config.caps.total_payout_cap_percent'      => ['required', 'numeric', 'min:0', 'max:100'],
            'config.caps.total_payout_cap_enforcement'  => ['required', 'string', 'in:proportional_reduction,hard_cap'],
            'config.caps.viral_cap_percent'             => ['required', 'numeric', 'min:0', 'max:100'],
            'config.caps.viral_cap_enforcement'         => ['required', 'string', 'in:daily_reduction,weekly_reduction'],
            'config.caps.enforcement_order'             => ['required', 'string', 'in:viral_first,global_first'],

            // Wallet
            'config.wallet.credit_timing'           => ['required', 'string', 'in:weekly,monthly'],
            'config.wallet.release_delay_days'      => ['required', 'integer', 'min:0'],
            'config.wallet.minimum_withdrawal'      => ['required', 'numeric', 'min:0'],
            'config.wallet.clawback_window_days'    => ['required', 'integer', 'min:0'],
        ]);

        $plan = CompensationPlan::create([
            'company_id'     => $validated['company_id'],
            'name'           => $validated['name'],
            'version'        => $validated['version'],
            'effective_from' => $validated['effective_from'],
            'effective_until' => $validated['effective_until'] ?? null,
            'is_active'      => (bool) ($validated['is_active'] ?? false),
            'config'         => $this->assembleConfig($validated, $request->input('config')),
        ]);

        return redirect()->route('admin.compensation-plans.index')
            ->with('success', 'Compensation plan created successfully.');
    }

    public function edit(int $id): View
    {
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
        $companies = Company::orderBy('name')->pluck('name', 'id');

        $cfg = $plan->config ?? [];

        $extracted = $this->extractConfigValues($cfg, $plan->name, $plan->version, $plan->effective_from?->format('Y-m-d'));

        return view('admin.compensation-plans.edit', compact('plan', 'companies', 'extracted'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)->findOrFail($id);

        $validated = $request->validate([
            'company_id'         => ['required', 'exists:companies,id'],
            'name'               => ['required', 'string', 'max:255'],
            'version'            => ['required', 'string', 'max:255'],
            'effective_from'     => ['required', 'date'],
            'effective_until'    => ['nullable', 'date'],
            'is_active'          => ['nullable'],

            // Plan settings
            'config.plan.currency'               => ['required', 'string', 'in:USD,EUR,GBP,CAD,AUD,MYR'],
            'config.plan.calculation_frequency'  => ['required', 'string', 'in:daily,weekly,monthly'],
            'config.plan.credit_frequency'       => ['required', 'string', 'in:weekly,monthly'],
            'config.plan.timezone'               => ['required', 'string'],

            // Qualification
            'config.qualification.rolling_days'                      => ['required', 'integer', 'min:1'],
            'config.qualification.active_customer_min_order_xp'      => ['required', 'numeric', 'min:0'],
            'config.qualification.active_customer_threshold_type'    => ['required', 'string', 'in:per_order,cumulative'],

            // Affiliate commission
            'config.affiliate_commission.payout_method'                => ['required', 'string', 'in:daily_new_volume,cumulative'],
            'config.affiliate_commission.self_purchase_earns_commission' => ['nullable'],
            'config.affiliate_commission.includes_smartship'           => ['nullable'],
            'config.affiliate_commission.tiers'                        => ['required', 'array', 'min:1'],
            'config.affiliate_commission.tiers.*.min_active_customers' => ['required', 'integer', 'min:0'],
            'config.affiliate_commission.tiers.*.min_referred_volume'  => ['required', 'numeric', 'min:0'],
            'config.affiliate_commission.tiers.*.rate'                 => ['required', 'numeric', 'min:0', 'max:100'],

            // Viral commission
            'config.viral_commission.tree'                           => ['required', 'string', 'in:enrollment,placement'],
            'config.viral_commission.tiers'                          => ['required', 'array', 'min:1'],
            'config.viral_commission.tiers.*.min_active_customers'   => ['required', 'integer', 'min:0'],
            'config.viral_commission.tiers.*.min_referred_volume'    => ['required', 'numeric', 'min:0'],
            'config.viral_commission.tiers.*.min_qvv'                => ['required', 'numeric', 'min:0'],
            'config.viral_commission.tiers.*.daily_reward'           => ['required', 'numeric', 'min:0'],

            // Caps
            'config.caps.total_payout_cap_percent'      => ['required', 'numeric', 'min:0', 'max:100'],
            'config.caps.total_payout_cap_enforcement'  => ['required', 'string', 'in:proportional_reduction,hard_cap'],
            'config.caps.viral_cap_percent'             => ['required', 'numeric', 'min:0', 'max:100'],
            'config.caps.viral_cap_enforcement'         => ['required', 'string', 'in:daily_reduction,weekly_reduction'],
            'config.caps.enforcement_order'             => ['required', 'string', 'in:viral_first,global_first'],

            // Wallet
            'config.wallet.credit_timing'           => ['required', 'string', 'in:weekly,monthly'],
            'config.wallet.release_delay_days'      => ['required', 'integer', 'min:0'],
            'config.wallet.minimum_withdrawal'      => ['required', 'numeric', 'min:0'],
            'config.wallet.clawback_window_days'    => ['required', 'integer', 'min:0'],
        ]);

        $plan->update([
            'company_id'     => $validated['company_id'],
            'name'           => $validated['name'],
            'version'        => $validated['version'],
            'effective_from' => $validated['effective_from'],
            'effective_until' => $validated['effective_until'] ?? null,
            'is_active'      => (bool) ($validated['is_active'] ?? false),
            'config'         => $this->assembleConfig($validated, $request->input('config')),
        ]);

        return redirect()->route('admin.compensation-plans.index')
            ->with('success', 'Compensation plan updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
        $plan->delete();

        return redirect()->route('admin.compensation-plans.index')
            ->with('success', 'Compensation plan deleted successfully.');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** Build the full nested JSON config array from validated form data. */
    private function assembleConfig(array $validated, array $rawConfig): array
    {
        $cfg = $rawConfig;

        $affiliateTiers = collect($cfg['affiliate_commission']['tiers'] ?? [])->values()->map(function (array $tier, int $i) {
            return [
                'min_active_customers' => (int) $tier['min_active_customers'],
                'min_referred_volume'  => (float) $tier['min_referred_volume'],
                // Rate stored as submitted display percentage / 100 → decimal
                'rate'                 => round((float) $tier['rate'] / 100, 6),
            ];
        })->all();

        $viralTiers = collect($cfg['viral_commission']['tiers'] ?? [])->values()->map(function (array $tier, int $i) {
            return [
                'tier'                 => $i + 1,
                'min_active_customers' => (int) $tier['min_active_customers'],
                'min_referred_volume'  => (float) $tier['min_referred_volume'],
                'min_qvv'              => (float) $tier['min_qvv'],
                'daily_reward'         => (float) $tier['daily_reward'],
            ];
        })->all();

        $enforcementOrder = match ($cfg['caps']['enforcement_order'] ?? 'viral_first') {
            'viral_first'  => ['viral_cap_first', 'then_global_cap'],
            'global_first' => ['global_cap_first', 'then_viral_cap'],
            default        => ['viral_cap_first', 'then_global_cap'],
        };

        return [
            'plan' => [
                'name'                    => $validated['name'],
                'version'                 => $validated['version'],
                'effective_date'          => $validated['effective_from'],
                'currency'                => $cfg['plan']['currency'],
                'calculation_frequency'   => $cfg['plan']['calculation_frequency'],
                'credit_frequency'        => $cfg['plan']['credit_frequency'],
                'day_definition'          => [
                    'start'    => '00:00:00',
                    'end'      => '23:59:59',
                    'timezone' => $cfg['plan']['timezone'],
                ],
            ],
            'qualification' => [
                'rolling_days'                      => (int) $cfg['qualification']['rolling_days'],
                'active_customer_min_order_xp'      => (float) $cfg['qualification']['active_customer_min_order_xp'],
                'active_customer_threshold_type'    => $cfg['qualification']['active_customer_threshold_type'],
            ],
            'affiliate_commission' => [
                'type'                            => 'tiered_percentage',
                'payout_method'                   => $cfg['affiliate_commission']['payout_method'],
                'basis'                           => 'referred_volume_30d',
                'customer_basis'                  => 'referred_active_customers_30d',
                'self_purchase_earns_commission'  => (bool) ($cfg['affiliate_commission']['self_purchase_earns_commission'] ?? false),
                'includes_smartship'              => (bool) ($cfg['affiliate_commission']['includes_smartship'] ?? false),
                'tiers'                           => $affiliateTiers,
            ],
            'viral_commission' => [
                'type'           => 'tiered_fixed_daily',
                'basis'          => 'qualifying_viral_volume_30d',
                'tree'           => $cfg['viral_commission']['tree'],
                'qvv_algorithm'  => [
                    'description' => 'Large leg cap with 2/3 small leg benchmark',
                    'steps'       => [
                        'identify_large_leg',
                        'sum_small_legs',
                        'cap_large_leg_at_two_thirds_small',
                        'qvv_equals_large_plus_small',
                    ],
                ],
                'tiers' => $viralTiers,
            ],
            'caps' => [
                'total_payout_cap_percent'      => round((float) $cfg['caps']['total_payout_cap_percent'] / 100, 6),
                'total_payout_cap_enforcement'  => $cfg['caps']['total_payout_cap_enforcement'],
                'total_payout_cap_window'       => 'rolling_30d',
                'viral_commission_cap'          => [
                    'percent_of_company_volume' => round((float) $cfg['caps']['viral_cap_percent'] / 100, 6),
                    'window'                    => 'rolling_30d',
                    'enforcement'               => $cfg['caps']['viral_cap_enforcement'],
                    'reduction_method'          => 'proportional_overage',
                ],
                'enforcement_order' => $enforcementOrder,
            ],
            'wallet' => [
                'credit_timing'          => $cfg['wallet']['credit_timing'],
                'release_delay_days'     => (int) $cfg['wallet']['release_delay_days'],
                'minimum_withdrawal'     => (float) $cfg['wallet']['minimum_withdrawal'],
                'clawback_window_days'   => (int) $cfg['wallet']['clawback_window_days'],
            ],
        ];
    }

    /** Extract flat display values from a stored config array for pre-populating the edit form. */
    private function extractConfigValues(array $cfg, ?string $planName, ?string $version, ?string $effectiveFrom): array
    {
        $affiliateTiers = array_values(array_map(function (array $tier) {
            return [
                'min_active_customers' => $tier['min_active_customers'] ?? 0,
                'min_referred_volume'  => $tier['min_referred_volume'] ?? 0,
                // Convert decimal rate (0.10) to display percentage (10)
                'rate'                 => isset($tier['rate']) ? round((float) $tier['rate'] * 100, 4) : 0,
            ];
        }, $cfg['affiliate_commission']['tiers'] ?? []));

        $viralTiers = array_values(array_map(function (array $tier) {
            return [
                'min_active_customers' => $tier['min_active_customers'] ?? 0,
                'min_referred_volume'  => $tier['min_referred_volume'] ?? 0,
                'min_qvv'              => $tier['min_qvv'] ?? 0,
                'daily_reward'         => $tier['daily_reward'] ?? 0,
            ];
        }, $cfg['viral_commission']['tiers'] ?? []));

        // Convert stored enforcement_order array back to a single key
        $enforcementOrderRaw = $cfg['caps']['enforcement_order'] ?? ['viral_cap_first'];
        $enforcementOrder = (is_array($enforcementOrderRaw) && in_array('global_cap_first', $enforcementOrderRaw))
            ? 'global_first'
            : 'viral_first';

        return [
            'plan' => [
                'currency'               => $cfg['plan']['currency'] ?? 'USD',
                'calculation_frequency'  => $cfg['plan']['calculation_frequency'] ?? 'daily',
                'credit_frequency'       => $cfg['plan']['credit_frequency'] ?? 'weekly',
                'timezone'               => $cfg['plan']['day_definition']['timezone'] ?? $cfg['plan']['timezone'] ?? 'UTC',
            ],
            'qualification' => [
                'rolling_days'                      => $cfg['qualification']['rolling_days'] ?? 30,
                'active_customer_min_order_xp'      => $cfg['qualification']['active_customer_min_order_xp'] ?? 20,
                'active_customer_threshold_type'    => $cfg['qualification']['active_customer_threshold_type'] ?? 'per_order',
            ],
            'affiliate_commission' => [
                'payout_method'                   => $cfg['affiliate_commission']['payout_method'] ?? 'daily_new_volume',
                'self_purchase_earns_commission'  => (bool) ($cfg['affiliate_commission']['self_purchase_earns_commission'] ?? false),
                'includes_smartship'              => (bool) ($cfg['affiliate_commission']['includes_smartship'] ?? true),
                'tiers'                           => $affiliateTiers,
            ],
            'viral_commission' => [
                'tree'   => $cfg['viral_commission']['tree'] ?? 'enrollment',
                'tiers'  => $viralTiers,
            ],
            'caps' => [
                // Convert decimal (0.35) to display percentage (35)
                'total_payout_cap_percent'      => isset($cfg['caps']['total_payout_cap_percent'])
                    ? round((float) $cfg['caps']['total_payout_cap_percent'] * 100, 4)
                    : 35,
                'total_payout_cap_enforcement'  => $cfg['caps']['total_payout_cap_enforcement'] ?? 'proportional_reduction',
                'viral_cap_percent'             => isset($cfg['caps']['viral_commission_cap']['percent_of_company_volume'])
                    ? round((float) $cfg['caps']['viral_commission_cap']['percent_of_company_volume'] * 100, 4)
                    : 15,
                'viral_cap_enforcement'         => $cfg['caps']['viral_commission_cap']['enforcement'] ?? 'daily_reduction',
                'enforcement_order'             => $enforcementOrder,
            ],
            'wallet' => [
                'credit_timing'         => $cfg['wallet']['credit_timing'] ?? 'weekly',
                'release_delay_days'    => $cfg['wallet']['release_delay_days'] ?? 0,
                'minimum_withdrawal'    => $cfg['wallet']['minimum_withdrawal'] ?? 0,
                'clawback_window_days'  => $cfg['wallet']['clawback_window_days'] ?? 30,
            ],
        ];
    }

    /** Sensible defaults for a brand-new plan. */
    private function defaultConfigValues(): array
    {
        return [
            'plan' => [
                'currency'               => 'USD',
                'calculation_frequency'  => 'daily',
                'credit_frequency'       => 'weekly',
                'timezone'               => 'UTC',
            ],
            'qualification' => [
                'rolling_days'                      => 30,
                'active_customer_min_order_xp'      => 20,
                'active_customer_threshold_type'    => 'per_order',
            ],
            'affiliate_commission' => [
                'payout_method'                   => 'daily_new_volume',
                'self_purchase_earns_commission'  => false,
                'includes_smartship'              => true,
                'tiers'                           => [
                    ['min_active_customers' => 1, 'min_referred_volume' => 0, 'rate' => 10],
                ],
            ],
            'viral_commission' => [
                'tree'  => 'enrollment',
                'tiers' => [
                    ['min_active_customers' => 2, 'min_referred_volume' => 50, 'min_qvv' => 100, 'daily_reward' => 0.53],
                ],
            ],
            'caps' => [
                'total_payout_cap_percent'      => 35,
                'total_payout_cap_enforcement'  => 'proportional_reduction',
                'viral_cap_percent'             => 15,
                'viral_cap_enforcement'         => 'daily_reduction',
                'enforcement_order'             => 'viral_first',
            ],
            'wallet' => [
                'credit_timing'         => 'weekly',
                'release_delay_days'    => 0,
                'minimum_withdrawal'    => 0,
                'clawback_window_days'  => 30,
            ],
        ];
    }
}
