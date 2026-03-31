<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class SimulationConfig extends Data
{
    public function __construct(
        public readonly int $projection_days,
        public readonly int $starting_affiliates,
        public readonly int $starting_customers,
        public readonly int $seed,

        // Growth
        public readonly float $new_affiliates_per_day,
        public readonly float $new_customers_per_affiliate_per_month,
        public readonly float $affiliate_to_customer_ratio,
        public readonly string $growth_curve,

        // Transactions
        public readonly float $average_order_xp,
        public readonly float $orders_per_customer_per_month,
        public readonly float $smartship_adoption_rate,
        public readonly float $smartship_average_xp,
        public readonly float $refund_rate,

        // Retention
        public readonly float $customer_monthly_churn_rate,
        public readonly float $affiliate_monthly_churn_rate,

        // Tree shape
        public readonly int $average_legs_per_affiliate,
        public readonly float $leg_balance_ratio,
        public readonly string $depth_bias,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            projection_days: $data['projection_days'] ?? 30,
            starting_affiliates: $data['starting_affiliates'] ?? 10,
            starting_customers: $data['starting_customers'] ?? 40,
            seed: $data['seed'] ?? 42,

            new_affiliates_per_day: $data['growth']['new_affiliates_per_day'] ?? 1,
            new_customers_per_affiliate_per_month: $data['growth']['new_customers_per_affiliate_per_month'] ?? 2,
            affiliate_to_customer_ratio: $data['growth']['affiliate_to_customer_ratio'] ?? 0.15,
            growth_curve: $data['growth']['growth_curve'] ?? 'linear',

            average_order_xp: $data['transactions']['average_order_xp'] ?? 45,
            orders_per_customer_per_month: $data['transactions']['orders_per_customer_per_month'] ?? 1.5,
            smartship_adoption_rate: $data['transactions']['smartship_adoption_rate'] ?? 0.30,
            smartship_average_xp: $data['transactions']['smartship_average_xp'] ?? 35,
            refund_rate: $data['transactions']['refund_rate'] ?? 0.05,

            customer_monthly_churn_rate: $data['retention']['customer_monthly_churn_rate'] ?? 0.08,
            affiliate_monthly_churn_rate: $data['retention']['affiliate_monthly_churn_rate'] ?? 0.05,

            average_legs_per_affiliate: $data['tree_shape']['average_legs_per_affiliate'] ?? 3,
            leg_balance_ratio: $data['tree_shape']['leg_balance_ratio'] ?? 0.6,
            depth_bias: $data['tree_shape']['depth_bias'] ?? 'moderate',
        );
    }

    public function toNestedArray(): array
    {
        return [
            'projection_days' => $this->projection_days,
            'starting_affiliates' => $this->starting_affiliates,
            'starting_customers' => $this->starting_customers,
            'seed' => $this->seed,
            'growth' => [
                'new_affiliates_per_day' => $this->new_affiliates_per_day,
                'new_customers_per_affiliate_per_month' => $this->new_customers_per_affiliate_per_month,
                'affiliate_to_customer_ratio' => $this->affiliate_to_customer_ratio,
                'growth_curve' => $this->growth_curve,
            ],
            'transactions' => [
                'average_order_xp' => $this->average_order_xp,
                'orders_per_customer_per_month' => $this->orders_per_customer_per_month,
                'smartship_adoption_rate' => $this->smartship_adoption_rate,
                'smartship_average_xp' => $this->smartship_average_xp,
                'refund_rate' => $this->refund_rate,
            ],
            'retention' => [
                'customer_monthly_churn_rate' => $this->customer_monthly_churn_rate,
                'affiliate_monthly_churn_rate' => $this->affiliate_monthly_churn_rate,
            ],
            'tree_shape' => [
                'average_legs_per_affiliate' => $this->average_legs_per_affiliate,
                'leg_balance_ratio' => $this->leg_balance_ratio,
                'depth_bias' => $this->depth_bias,
            ],
        ];
    }
}
