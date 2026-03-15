<?php

namespace Tests\Feature\Commission;

use App\DTOs\PlanConfig;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletAccount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class CommissionTestCase extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected CompensationPlan $plan;
    protected PlanConfig $config;
    protected Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->today = Carbon::create(2026, 3, 15);
        Carbon::setTestNow($this->today);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Create a test company with the full SoComm plan config.
     */
    protected function createCompanyWithPlan(array $configOverrides = []): void
    {
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-co',
        ]);

        $config = $this->soCommConfig();
        $config = array_replace_recursive($config, $configOverrides);

        $this->plan = CompensationPlan::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Plan',
            'version' => '1.0',
            'config' => $config,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->config = PlanConfig::fromArray($config);
    }

    /**
     * Create an affiliate user with a genealogy node.
     */
    protected function createAffiliate(string $name = 'Affiliate', ?GenealogyNode $sponsor = null): User
    {
        $user = User::factory()->affiliate()->create([
            'company_id' => $this->company->id,
            'name' => $name,
        ]);

        GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'sponsor_id' => $sponsor?->id,
            'tree_depth' => $sponsor ? $sponsor->tree_depth + 1 : 0,
        ]);

        WalletAccount::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        return $user;
    }

    /**
     * Create a customer user with a genealogy node under a sponsor.
     */
    protected function createCustomer(string $name = 'Customer', ?GenealogyNode $sponsor = null): User
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => $name,
            'role' => 'customer',
        ]);

        GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'sponsor_id' => $sponsor?->id,
            'tree_depth' => $sponsor ? $sponsor->tree_depth + 1 : 0,
        ]);

        return $user;
    }

    /**
     * Get the genealogy node for a user.
     */
    protected function nodeFor(User $user): GenealogyNode
    {
        return GenealogyNode::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    /**
     * Create a confirmed, qualifying transaction.
     */
    protected function createTransaction(
        User $buyer,
        float $xp,
        ?User $referredBy = null,
        ?int $daysAgo = 0,
        string $type = 'purchase',
        string $status = 'confirmed',
        bool $qualifies = true,
    ): Transaction {
        return Transaction::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $buyer->id,
            'referred_by_user_id' => $referredBy?->id,
            'type' => $type,
            'amount' => $xp,
            'xp' => $xp,
            'status' => $status,
            'qualifies_for_commission' => $qualifies,
            'transaction_date' => $this->today->copy()->subDays($daysAgo),
        ]);
    }

    /**
     * Assert a commission amount matches with bcmath precision.
     */
    protected function assertCommissionEquals(string $expected, string $actual, string $message = ''): void
    {
        $this->assertSame(
            0,
            bccomp($expected, $actual, 4),
            $message ?: "Expected commission {$expected}, got {$actual}"
        );
    }

    /**
     * Full SoComm plan config for tests.
     */
    protected function soCommConfig(): array
    {
        return [
            'plan' => [
                'name' => 'SoComm Affiliate Rewards Program',
                'version' => '1.0',
                'effective_date' => '2026-01-01',
                'currency' => 'USD',
                'calculation_frequency' => 'daily',
                'credit_frequency' => 'weekly',
                'day_definition' => [
                    'start' => '00:00:00',
                    'end' => '23:59:59',
                    'timezone' => 'UTC',
                ],
            ],
            'qualification' => [
                'rolling_days' => 30,
                'active_customer_min_order_xp' => 20,
                'active_customer_threshold_type' => 'per_order',
                'affiliate_inactivity_downgrade_months' => 12,
                'affiliate_inactivity_requires_no_orders' => true,
                'affiliate_inactivity_requires_no_rewards' => true,
            ],
            'affiliate_commission' => [
                'type' => 'tiered_percentage',
                'payout_method' => 'daily_new_volume',
                'basis' => 'referred_volume_30d',
                'customer_basis' => 'referred_active_customers_30d',
                'self_purchase_earns_commission' => false,
                'includes_smartship' => true,
                'tiers' => [
                    ['min_active_customers' => 1, 'min_referred_volume' => 0,    'rate' => 0.10],
                    ['min_active_customers' => 2, 'min_referred_volume' => 200,  'rate' => 0.11],
                    ['min_active_customers' => 2, 'min_referred_volume' => 400,  'rate' => 0.12],
                    ['min_active_customers' => 3, 'min_referred_volume' => 600,  'rate' => 0.13],
                    ['min_active_customers' => 4, 'min_referred_volume' => 800,  'rate' => 0.14],
                    ['min_active_customers' => 5, 'min_referred_volume' => 1000, 'rate' => 0.15],
                    ['min_active_customers' => 6, 'min_referred_volume' => 1200, 'rate' => 0.16],
                    ['min_active_customers' => 7, 'min_referred_volume' => 1400, 'rate' => 0.17],
                    ['min_active_customers' => 8, 'min_referred_volume' => 1600, 'rate' => 0.18],
                    ['min_active_customers' => 9, 'min_referred_volume' => 1800, 'rate' => 0.19],
                    ['min_active_customers' => 10,'min_referred_volume' => 2000, 'rate' => 0.20],
                ],
            ],
            'viral_commission' => [
                'type' => 'tiered_fixed_daily',
                'basis' => 'qualifying_viral_volume_30d',
                'tree' => 'enrollment',
                'qvv_algorithm' => [
                    'description' => 'Large leg cap with 2/3 small leg benchmark',
                ],
                'tiers' => [
                    ['tier' => 1,  'min_active_customers' => 2, 'min_referred_volume' => 50,   'min_qvv' => 100,   'daily_reward' => 0.53],
                    ['tier' => 2,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 250,   'daily_reward' => 1.33],
                    ['tier' => 3,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 500,   'daily_reward' => 2.67],
                    ['tier' => 4,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 750,   'daily_reward' => 4.00],
                    ['tier' => 5,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 1000,  'daily_reward' => 5.00],
                    ['tier' => 6,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 1500,  'daily_reward' => 7.50],
                    ['tier' => 7,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2000,  'daily_reward' => 10.00],
                    ['tier' => 8,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2500,  'daily_reward' => 12.50],
                    ['tier' => 9,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 3500,  'daily_reward' => 17.50],
                    ['tier' => 10, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 5000,  'daily_reward' => 23.33],
                ],
            ],
            'caps' => [
                'total_payout_cap_percent' => 0.35,
                'total_payout_cap_enforcement' => 'proportional_reduction',
                'total_payout_cap_window' => 'rolling_30d',
                'viral_commission_cap' => [
                    'percent_of_company_volume' => 0.15,
                    'window' => 'rolling_30d',
                    'enforcement' => 'daily_reduction',
                    'reduction_method' => 'proportional_overage',
                ],
                'enforcement_order' => ['viral_cap_first', 'then_global_cap'],
            ],
            'wallet' => [
                'credit_timing' => 'weekly',
                'release_delay_days' => 0,
                'minimum_withdrawal' => 0,
                'clawback_window_days' => 30,
            ],
        ];
    }
}
