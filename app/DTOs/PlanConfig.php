<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class PlanConfig extends Data
{
    public function __construct(
        public readonly string $plan_name,
        public readonly string $version,
        public readonly string $currency,
        public readonly string $calculation_frequency,
        public readonly string $credit_frequency,
        public readonly string $timezone,

        // Qualification
        public readonly int $rolling_days,
        public readonly float $active_customer_min_order_xp,
        public readonly string $active_customer_threshold_type,

        // Affiliate commission
        public readonly string $affiliate_payout_method,
        public readonly bool $self_purchase_earns_commission,
        public readonly bool $includes_smartship,
        /** @var AffiliateCommissionTier[] */
        public readonly array $affiliate_tiers,

        // Viral commission
        public readonly string $viral_tree,
        /** @var ViralCommissionTier[] */
        public readonly array $viral_tiers,

        // Caps
        public readonly float $total_payout_cap_percent,
        public readonly string $total_payout_cap_enforcement,
        public readonly float $viral_cap_percent,
        public readonly string $viral_cap_enforcement,
        public readonly array $enforcement_order,

        // Wallet
        public readonly string $wallet_credit_timing,
        public readonly int $wallet_release_delay_days,
        public readonly float $wallet_minimum_withdrawal,
        public readonly int $wallet_clawback_window_days,
    ) {}

    public static function fromArray(array $config): static
    {
        $affiliateTiers = array_map(
            fn (array $t) => new AffiliateCommissionTier(
                min_active_customers: $t['min_active_customers'],
                min_referred_volume: $t['min_referred_volume'],
                rate: $t['rate'],
            ),
            $config['affiliate_commission']['tiers'] ?? []
        );

        $viralTiers = array_map(
            fn (array $t) => new ViralCommissionTier(
                tier: $t['tier'],
                min_active_customers: $t['min_active_customers'],
                min_referred_volume: $t['min_referred_volume'],
                min_qvv: $t['min_qvv'],
                daily_reward: $t['daily_reward'],
            ),
            $config['viral_commission']['tiers'] ?? []
        );

        return new static(
            plan_name: $config['plan']['name'],
            version: $config['plan']['version'],
            currency: $config['plan']['currency'] ?? 'USD',
            calculation_frequency: $config['plan']['calculation_frequency'] ?? 'daily',
            credit_frequency: $config['plan']['credit_frequency'] ?? 'weekly',
            timezone: $config['plan']['day_definition']['timezone'] ?? 'UTC',

            rolling_days: $config['qualification']['rolling_days'] ?? 30,
            active_customer_min_order_xp: $config['qualification']['active_customer_min_order_xp'] ?? 20,
            active_customer_threshold_type: $config['qualification']['active_customer_threshold_type'] ?? 'per_order',

            affiliate_payout_method: $config['affiliate_commission']['payout_method'] ?? 'daily_new_volume',
            self_purchase_earns_commission: $config['affiliate_commission']['self_purchase_earns_commission'] ?? false,
            includes_smartship: $config['affiliate_commission']['includes_smartship'] ?? true,
            affiliate_tiers: $affiliateTiers,

            viral_tree: $config['viral_commission']['tree'] ?? 'enrollment',
            viral_tiers: $viralTiers,

            total_payout_cap_percent: $config['caps']['total_payout_cap_percent'] ?? 0.35,
            total_payout_cap_enforcement: $config['caps']['total_payout_cap_enforcement'] ?? 'proportional_reduction',
            viral_cap_percent: $config['caps']['viral_commission_cap']['percent_of_company_volume'] ?? 0.15,
            viral_cap_enforcement: $config['caps']['viral_commission_cap']['enforcement'] ?? 'daily_reduction',
            enforcement_order: $config['caps']['enforcement_order'] ?? ['viral_cap_first', 'then_global_cap'],

            wallet_credit_timing: $config['wallet']['credit_timing'] ?? 'weekly',
            wallet_release_delay_days: $config['wallet']['release_delay_days'] ?? 0,
            wallet_minimum_withdrawal: $config['wallet']['minimum_withdrawal'] ?? 0,
            wallet_clawback_window_days: $config['wallet']['clawback_window_days'] ?? 30,
        );
    }
}
