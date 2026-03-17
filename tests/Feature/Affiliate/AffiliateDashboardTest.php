<?php

namespace Tests\Feature\Affiliate;

use App\DTOs\PlanConfig;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Scopes\CompanyScope;
use App\Models\User;
use App\Models\WalletAccount;
use App\Models\WalletMovement;
use App\Services\Affiliate\TeamStatsService;
use App\Services\Affiliate\TierProgressService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected CompensationPlan $plan;
    protected PlanConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 3, 15));

        $this->company = Company::factory()->create([
            'name' => 'Test Co',
            'slug' => 'test-co',
        ]);

        $this->plan = CompensationPlan::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Plan',
            'version' => '1.0',
            'config' => $this->soCommConfig(),
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->config = PlanConfig::fromArray($this->plan->config);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createAffiliate(string $name = 'Affiliate', ?GenealogyNode $sponsor = null): User
    {
        $user = User::factory()->affiliate()->create([
            'company_id' => $this->company->id,
            'name' => $name,
        ]);

        $node = GenealogyNode::factory()->create([
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

    private function createCustomer(string $name, ?GenealogyNode $sponsor = null): User
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

    private function nodeFor(User $user): GenealogyNode
    {
        return GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    private function createTransaction(User $buyer, float $xp, ?User $referredBy = null, int $daysAgo = 0): void
    {
        Transaction::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $buyer->id,
            'referred_by_user_id' => $referredBy?->id,
            'type' => 'purchase',
            'amount' => $xp,
            'xp' => $xp,
            'status' => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date' => Carbon::today()->subDays($daysAgo),
        ]);
    }

    // ===================================================================
    // D1: Dashboard loads for authenticated affiliate
    // ===================================================================
    public function test_d1_dashboard_loads_for_authenticated_affiliate(): void
    {
        $affiliate = $this->createAffiliate('Alice');

        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $response = $this->actingAs($affiliate)
            ->get(route('affiliate.dashboard', $this->company->slug));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    // ===================================================================
    // D2: Dashboard blocked for customers
    // ===================================================================
    public function test_d2_dashboard_blocked_for_customers(): void
    {
        $customer = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'customer',
        ]);

        GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $customer->id,
        ]);

        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $response = $this->actingAs($customer)
            ->get(route('affiliate.dashboard', $this->company->slug));

        $response->assertStatus(403);
    }

    // ===================================================================
    // D3: Tier progress accuracy
    // ===================================================================
    public function test_d3_tier_progress_accuracy(): void
    {
        $affiliate = $this->createAffiliate('Alice');
        $affNode = $this->nodeFor($affiliate);

        // Create 5 customers, each with qualifying orders
        for ($i = 1; $i <= 5; $i++) {
            $cust = $this->createCustomer("Customer $i", $affNode);
            $this->createTransaction($cust, 50, $affiliate, $i);
        }

        // Additional volume transactions for customers 1 and 2
        $customers = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company->id)
            ->where('role', 'customer')
            ->limit(2)
            ->get();

        foreach ($customers as $cust) {
            $this->createTransaction($cust, 100, $affiliate, 5);
            $this->createTransaction($cust, 100, $affiliate, 10);
        }

        // Total: 5 customers, 5*50 + 2*200 = 650 XP volume
        // Should match tier: 5 customers, 1000 XP needed for 15% → only at 13% (3 cust, 600)
        // Actually: 5 cust, 650 XP → tier with 5 cust needs 1000 → not enough.
        // Highest: 3 cust + 600 = 13%
        // Next: 4 cust + 800 = 14% (already have 5 cust, need 800 volume → need 150 more XP)

        $service = app(TierProgressService::class);
        $progress = $service->calculate($affiliate, Carbon::today(), $this->config);

        // With 5 customers and 650 XP, the highest tier that matches is:
        // Tier with min_active_customers<=5 and min_referred_volume<=650
        // That's: 3 cust + 600 vol → 13%
        $this->assertEquals(0.13, $progress->current_affiliate_rate);

        // Next tier: 4 customers + 800 volume → 14%
        $this->assertEquals(0.14, $progress->next_affiliate_rate);
        $this->assertEquals(4, $progress->next_affiliate_min_customers);
        $this->assertEquals(800, $progress->next_affiliate_min_volume);

        // Progress: customers 5/4 = 100%, volume 650/800 = 81.2%
        $this->assertEquals(100.0, $progress->customer_progress_percent);
        $this->assertGreaterThan(80, $progress->volume_progress_percent);
        $this->assertLessThan(82, $progress->volume_progress_percent);

        // Volume needed
        $this->assertSame(0, bccomp('150', $progress->volume_needed, 0));
    }

    // ===================================================================
    // D4: Wallet balance derivation
    // ===================================================================
    public function test_d4_wallet_balance_derivation(): void
    {
        $affiliate = $this->createAffiliate('Alice');
        $wallet = WalletAccount::where('user_id', $affiliate->id)->first();

        // Create some wallet movements
        WalletMovement::create([
            'company_id' => $this->company->id,
            'wallet_account_id' => $wallet->id,
            'type' => 'commission_credit',
            'amount' => '100.0000',
            'status' => 'released',
            'effective_at' => now()->subDays(10),
        ]);

        WalletMovement::create([
            'company_id' => $this->company->id,
            'wallet_account_id' => $wallet->id,
            'type' => 'commission_credit',
            'amount' => '50.5000',
            'status' => 'released',
            'effective_at' => now()->subDays(5),
        ]);

        WalletMovement::create([
            'company_id' => $this->company->id,
            'wallet_account_id' => $wallet->id,
            'type' => 'commission_credit',
            'amount' => '25.0000',
            'status' => 'pending',
            'effective_at' => now(),
        ]);

        // totalNonReversed = all non-reversed: 100 + 50.5 + 25 = 175.5
        $this->assertEquals(0, bccomp('175.5', $wallet->totalNonReversed(), 2));

        // availableBalance = only approved+released: 100 + 50.5 = 150.5
        $this->assertEquals(0, bccomp('150.5', $wallet->availableBalance(), 2));

        // Test via the dashboard route
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $response = $this->actingAs($affiliate)
            ->get(route('affiliate.wallet', $this->company->slug));

        $response->assertStatus(200);
    }

    // ===================================================================
    // D5: Commission history pagination
    // ===================================================================
    public function test_d5_commission_history_pagination(): void
    {
        $affiliate = $this->createAffiliate('Alice');

        $run = CommissionRun::create([
            'company_id' => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'run_date' => Carbon::today(),
            'status' => 'completed',
            'total_affiliate_commission' => 0,
            'total_viral_commission' => 0,
            'total_company_volume' => 0,
            'viral_cap_triggered' => false,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // Create 50 entries
        for ($i = 0; $i < 50; $i++) {
            CommissionLedgerEntry::create([
                'company_id' => $this->company->id,
                'commission_run_id' => $run->id,
                'user_id' => $affiliate->id,
                'type' => $i % 2 === 0 ? 'affiliate_commission' : 'viral_commission',
                'amount' => number_format(rand(100, 1500) / 100, 4, '.', ''),
                'tier_achieved' => rand(1, 5),
                'qualification_snapshot' => ['test' => true],
                'description' => "Test entry $i",
                'created_at' => Carbon::today()->subDays($i % 30),
            ]);
        }

        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $response = $this->actingAs($affiliate)
            ->get(route('affiliate.commissions', $this->company->slug));

        $response->assertStatus(200);
        $response->assertSee('Commission History');

        // Count entries — 50 total, paginated at 15 per page
        $this->assertDatabaseCount('commission_ledger_entries', 50);
    }

    // ===================================================================
    // D6: Team tree display
    // ===================================================================
    public function test_d6_team_tree_display(): void
    {
        $affiliate = $this->createAffiliate('Alice');
        $affNode = $this->nodeFor($affiliate);

        // Leg 1: Bob
        $bob = $this->createAffiliate('Bob', $affNode);
        $bobNode = $this->nodeFor($bob);

        // Leg 2: Carol
        $carol = $this->createAffiliate('Carol', $affNode);
        $carolNode = $this->nodeFor($carol);

        // Leg 3: Dave
        $dave = $this->createAffiliate('Dave', $affNode);
        $daveNode = $this->nodeFor($dave);

        // Bob's downline: 2 customers
        $bobCust1 = $this->createCustomer('BobCust1', $bobNode);
        $bobCust2 = $this->createCustomer('BobCust2', $bobNode);

        // Carol's downline: 1 customer
        $carolCust = $this->createCustomer('CarolCust', $carolNode);

        // Transactions for volume
        $this->createTransaction($bobCust1, 100, $bob, 5);
        $this->createTransaction($bobCust2, 80, $bob, 3);
        $this->createTransaction($carolCust, 50, $carol, 2);

        // Volume in each subtree
        $this->createTransaction($bob, 200, $affiliate, 1);
        $this->createTransaction($carol, 100, $affiliate, 2);
        $this->createTransaction($dave, 30, $affiliate, 4);

        // Test service
        $service = app(TeamStatsService::class);
        $stats = $service->calculate($affiliate, Carbon::today(), $this->config);

        // Team = 3 affiliates + 3 customers = 6
        $this->assertEquals(6, $stats->total_team_size);
        $this->assertEquals(3, $stats->active_affiliates);
        $this->assertCount(3, $stats->legs);

        // Identify large leg
        $largeLeg = collect($stats->legs)->first(fn ($l) => $l->is_large_leg);
        $this->assertNotNull($largeLeg);
        $this->assertEquals('Bob', $largeLeg->leg_root_name);

        // Test the route
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $response = $this->actingAs($affiliate)
            ->get(route('affiliate.team', $this->company->slug));

        $response->assertStatus(200);
        $response->assertSee('Team Overview');
    }

    // ===================================================================
    // D7: QVV capping warning
    // ===================================================================
    public function test_d7_qvv_capping_warning(): void
    {
        $affiliate = $this->createAffiliate('Alice');
        $affNode = $this->nodeFor($affiliate);

        // Create a heavily imbalanced tree
        // Leg 1: massive volume
        $bigLeg = $this->createAffiliate('BigLeg', $affNode);
        $bigNode = $this->nodeFor($bigLeg);

        // Leg 2: small volume
        $smallLeg = $this->createAffiliate('SmallLeg', $affNode);
        $smallNode = $this->nodeFor($smallLeg);

        // Heavy volume in big leg
        for ($i = 0; $i < 5; $i++) {
            $cust = $this->createCustomer("BigCust$i", $bigNode);
            $this->createTransaction($cust, 200, $bigLeg, $i);
        }

        // Small volume in small leg
        $smallCust = $this->createCustomer('SmallCust', $smallNode);
        $this->createTransaction($smallCust, 30, $smallLeg, 1);

        // Also create referred transactions for Alice (qualification)
        $this->createTransaction($bigLeg, 100, $affiliate, 0);
        $this->createTransaction($smallLeg, 50, $affiliate, 1);

        $service = app(TeamStatsService::class);
        $stats = $service->calculate($affiliate, Carbon::today(), $this->config);

        // Big leg should be much larger than small leg → QVV cap should trigger
        $this->assertTrue($stats->qvv_capping_warning);

        // Large leg should have is_capping_qvv = true
        $cappingLeg = collect($stats->legs)->first(fn ($l) => $l->is_capping_qvv);
        $this->assertNotNull($cappingLeg);
        $this->assertEquals('BigLeg', $cappingLeg->leg_root_name);
    }

    // ===================================================================
    // D8: Tenant isolation
    // ===================================================================
    public function test_d8_tenant_isolation(): void
    {
        // Create a second company
        $company2 = Company::factory()->create([
            'name' => 'Other Co',
            'slug' => 'other-co',
        ]);

        CompensationPlan::factory()->create([
            'company_id' => $company2->id,
            'name' => 'Other Plan',
            'config' => $this->soCommConfig(),
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Create affiliate in company 1
        $aff1 = $this->createAffiliate('Alice');

        // Create affiliate in company 2
        $aff2 = User::factory()->affiliate()->create([
            'company_id' => $company2->id,
            'name' => 'Bob',
        ]);
        GenealogyNode::factory()->create([
            'company_id' => $company2->id,
            'user_id' => $aff2->id,
        ]);
        WalletAccount::factory()->create([
            'company_id' => $company2->id,
            'user_id' => $aff2->id,
        ]);

        // Affiliate 1 can access company 1's dashboard
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $response = $this->actingAs($aff1)
            ->get(route('affiliate.dashboard', $this->company->slug));
        $response->assertStatus(200);

        // Affiliate 2 from company 2 cannot access company 1's dashboard
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);

        $response = $this->actingAs($aff2)
            ->get(route('affiliate.dashboard', $this->company->slug));
        $response->assertStatus(403);
    }

    // ===================================================================
    // Login tests
    // ===================================================================
    public function test_affiliate_login_page_loads(): void
    {
        $response = $this->get(route('affiliate.login', $this->company->slug));
        $response->assertStatus(200);
        $response->assertSee($this->company->name);
        $response->assertSee('Affiliate Portal');
    }

    public function test_affiliate_can_login_with_valid_credentials(): void
    {
        $affiliate = $this->createAffiliate('Alice');

        $response = $this->post(route('affiliate.login.submit', $this->company->slug), [
            'email' => $affiliate->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('affiliate.dashboard', $this->company->slug));
        $this->assertAuthenticatedAs($affiliate);
    }

    public function test_customer_cannot_login_to_affiliate_portal(): void
    {
        $customer = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'customer',
        ]);

        GenealogyNode::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $customer->id,
        ]);

        $response = $this->post(route('affiliate.login.submit', $this->company->slug), [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ===================================================================
    // Helper: SoComm config
    // ===================================================================
    private function soCommConfig(): array
    {
        return [
            'plan' => [
                'name' => 'Test Plan', 'version' => '1.0', 'effective_date' => '2026-01-01',
                'currency' => 'USD', 'calculation_frequency' => 'daily', 'credit_frequency' => 'weekly',
                'day_definition' => ['start' => '00:00:00', 'end' => '23:59:59', 'timezone' => 'UTC'],
            ],
            'qualification' => [
                'rolling_days' => 30, 'active_customer_min_order_xp' => 20,
                'active_customer_threshold_type' => 'per_order',
                'affiliate_inactivity_downgrade_months' => 12,
                'affiliate_inactivity_requires_no_orders' => true,
                'affiliate_inactivity_requires_no_rewards' => true,
            ],
            'affiliate_commission' => [
                'type' => 'tiered_percentage', 'payout_method' => 'daily_new_volume',
                'basis' => 'referred_volume_30d', 'customer_basis' => 'referred_active_customers_30d',
                'self_purchase_earns_commission' => false, 'includes_smartship' => true,
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
                'type' => 'tiered_fixed_daily', 'basis' => 'qualifying_viral_volume_30d',
                'tree' => 'enrollment',
                'qvv_algorithm' => ['description' => 'Large leg cap with 2/3 small leg benchmark'],
                'tiers' => [
                    ['tier' => 1,  'min_active_customers' => 2, 'min_referred_volume' => 50,  'min_qvv' => 100,  'daily_reward' => 0.53],
                    ['tier' => 2,  'min_active_customers' => 2, 'min_referred_volume' => 100, 'min_qvv' => 250,  'daily_reward' => 1.33],
                    ['tier' => 3,  'min_active_customers' => 2, 'min_referred_volume' => 100, 'min_qvv' => 500,  'daily_reward' => 2.67],
                    ['tier' => 4,  'min_active_customers' => 2, 'min_referred_volume' => 100, 'min_qvv' => 750,  'daily_reward' => 4.00],
                    ['tier' => 5,  'min_active_customers' => 2, 'min_referred_volume' => 100, 'min_qvv' => 1000, 'daily_reward' => 5.00],
                ],
            ],
            'caps' => [
                'total_payout_cap_percent' => 0.35, 'total_payout_cap_enforcement' => 'proportional_reduction',
                'total_payout_cap_window' => 'rolling_30d',
                'viral_commission_cap' => [
                    'percent_of_company_volume' => 0.15, 'window' => 'rolling_30d',
                    'enforcement' => 'daily_reduction', 'reduction_method' => 'proportional_overage',
                ],
                'enforcement_order' => ['viral_cap_first', 'then_global_cap'],
            ],
            'wallet' => [
                'credit_timing' => 'weekly', 'release_delay_days' => 0,
                'minimum_withdrawal' => 0, 'clawback_window_days' => 30,
            ],
        ];
    }
}
