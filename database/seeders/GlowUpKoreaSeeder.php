<?php

namespace Database\Seeders;

use App\Enums\BonusTypeEnum;
use App\Models\BonusLedgerEntry;
use App\Models\BonusTier;
use App\Models\BonusType;
use App\Models\BonusTypeConfig;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
use App\Models\CompanySetting;
use App\Scopes\CompanyScope;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletAccount;
use App\Models\WalletMovement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GlowUpKoreaSeeder extends Seeder
{
    private Company $company;
    private CompensationPlan $plan;
    private array $users = [];
    private array $nodes = [];

    public function run(): void
    {
        $this->company = Company::create([
            'name' => 'GlowUp Korea',
            'slug' => 'glowup-korea',
            'timezone' => 'Asia/Seoul',
            'currency' => 'KRW',
            'is_active' => true,
        ]);

        $this->plan = CompensationPlan::create([
            'company_id' => $this->company->id,
            'name' => 'GlowUp Affiliate Rewards',
            'version' => '1.0',
            'config' => $this->glowUpConfig(),
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'is_active' => true,
        ]);

        $this->createUsers();
        $this->createGenealogyTree();
        $this->createTransactions();
        $this->createWalletAccounts();
        $this->createCommissionHistory();
        $this->seedBonusTypes();
        $this->seedCompanySettings();
    }

    private function createUsers(): void
    {
        $userData = [
            // Admin (GlowUp operator/manager)
            ['name' => 'GlowUp Manager', 'email' => 'admin@glowup.test', 'role' => 'admin', 'months_ago' => 10],

            // ===== TOP-LEVEL AFFILIATES =====
            ['name' => 'Jimin Park',    'email' => 'jimin@glowup.test',    'role' => 'affiliate', 'months_ago' => 8],
            ['name' => 'Soo-Yeon Kim', 'email' => 'sooyeon@glowup.test',  'role' => 'affiliate', 'months_ago' => 7],

            // ===== JIMIN'S STRONG LEG =====
            ['name' => 'Hyun-Woo Lee', 'email' => 'hyunwoo@glowup.test',  'role' => 'affiliate', 'months_ago' => 6],
            ['name' => 'Min-Ji Choi',  'email' => 'minji@glowup.test',    'role' => 'customer',  'months_ago' => 5],
            ['name' => 'Tae-Hee Jung', 'email' => 'taehee@glowup.test',   'role' => 'customer',  'months_ago' => 4],
            // Under Hyun-Woo
            ['name' => 'Ye-Jin Song',  'email' => 'yejin@glowup.test',    'role' => 'affiliate', 'months_ago' => 4],
            ['name' => 'Ji-Hoon Kang', 'email' => 'jihoon@glowup.test',   'role' => 'customer',  'months_ago' => 3],

            // ===== JIMIN'S MODERATE LEG =====
            ['name' => 'Da-Eun Oh',    'email' => 'daeun@glowup.test',    'role' => 'affiliate', 'months_ago' => 5],
            ['name' => 'Sung-Ho Yoon', 'email' => 'sungho@glowup.test',   'role' => 'customer',  'months_ago' => 4],

            // ===== JIMIN'S DIRECT CUSTOMERS =====
            ['name' => 'Hae-Won Bae',  'email' => 'haewon@glowup.test',   'role' => 'customer',  'months_ago' => 5],
            ['name' => 'Se-Jun Ryu',   'email' => 'sejun@glowup.test',    'role' => 'customer',  'months_ago' => 3],

            // ===== SOO-YEON'S NETWORK =====
            ['name' => 'Eun-Bi Lim',   'email' => 'eunbi@glowup.test',    'role' => 'affiliate', 'months_ago' => 6],
            ['name' => 'Woo-Jin Han',  'email' => 'woojin@glowup.test',   'role' => 'customer',  'months_ago' => 4],
            ['name' => 'Na-Young Shin','email' => 'nayoung@glowup.test',  'role' => 'customer',  'months_ago' => 3],

            // ===== SOO-YEON'S DIRECT CUSTOMERS =====
            ['name' => 'Bo-Ram Kwon',  'email' => 'boram@glowup.test',    'role' => 'customer',  'months_ago' => 4],
            ['name' => 'In-Soo Moon',  'email' => 'insoo@glowup.test',    'role' => 'customer',  'months_ago' => 2],
        ];

        foreach ($userData as $data) {
            $this->users[$data['email']] = User::create([
                'company_id' => $this->company->id,
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => 'password',
                'role'       => $data['role'],
                'status'     => 'active',
                'enrolled_at' => now()->subMonths($data['months_ago']),
            ]);
        }
    }

    private function createGenealogyTree(): void
    {
        $make = function (string $email, ?string $sponsorEmail, int $depth): void {
            $sponsor = $sponsorEmail ? ($this->nodes[$sponsorEmail] ?? null) : null;
            $this->nodes[$email] = GenealogyNode::create([
                'company_id' => $this->company->id,
                'user_id'    => $this->users[$email]->id,
                'sponsor_id' => $sponsor?->id,
                'tree_depth' => $depth,
            ]);
        };

        // Roots — no sponsor
        $make('admin@glowup.test',   null, 0);
        $make('jimin@glowup.test',   null, 0);
        $make('sooyeon@glowup.test', null, 0);

        // Jimin's strong leg (depth 1)
        $make('hyunwoo@glowup.test', 'jimin@glowup.test', 1);
        $make('minji@glowup.test',   'jimin@glowup.test', 1);
        $make('taehee@glowup.test',  'jimin@glowup.test', 1);

        // Hyun-Woo's downline (depth 2)
        $make('yejin@glowup.test',  'hyunwoo@glowup.test', 2);
        $make('jihoon@glowup.test', 'hyunwoo@glowup.test', 2);

        // Jimin's moderate leg (depth 1)
        $make('daeun@glowup.test',  'jimin@glowup.test', 1);
        $make('sungho@glowup.test', 'jimin@glowup.test', 1);

        // Jimin's direct customers (depth 1)
        $make('haewon@glowup.test', 'jimin@glowup.test', 1);
        $make('sejun@glowup.test',  'jimin@glowup.test', 1);

        // Soo-Yeon's network (depth 1)
        $make('eunbi@glowup.test',   'sooyeon@glowup.test', 1);
        $make('woojin@glowup.test',  'sooyeon@glowup.test', 1);
        $make('nayoung@glowup.test', 'sooyeon@glowup.test', 1);

        // Soo-Yeon's direct customers (depth 1)
        $make('boram@glowup.test',   'sooyeon@glowup.test', 1);
        $make('insoo@glowup.test',   'sooyeon@glowup.test', 1);
    }

    private function createTransactions(): void
    {
        // Sponsor map mirrors the genealogy tree for referred_by_user_id
        $sponsorMap = [
            'hyunwoo@glowup.test'  => 'jimin@glowup.test',
            'minji@glowup.test'    => 'jimin@glowup.test',
            'taehee@glowup.test'   => 'jimin@glowup.test',
            'daeun@glowup.test'    => 'jimin@glowup.test',
            'sungho@glowup.test'   => 'jimin@glowup.test',
            'haewon@glowup.test'   => 'jimin@glowup.test',
            'sejun@glowup.test'    => 'jimin@glowup.test',
            'yejin@glowup.test'    => 'hyunwoo@glowup.test',
            'jihoon@glowup.test'   => 'hyunwoo@glowup.test',
            'eunbi@glowup.test'    => 'sooyeon@glowup.test',
            'woojin@glowup.test'   => 'sooyeon@glowup.test',
            'nayoung@glowup.test'  => 'sooyeon@glowup.test',
            'boram@glowup.test'    => 'sooyeon@glowup.test',
            'insoo@glowup.test'    => 'sooyeon@glowup.test',
        ];

        $txn = function (string $email, int $daysAgo, float $xp, string $type = 'purchase') use ($sponsorMap): void {
            $buyer    = $this->users[$email];
            $referrer = isset($sponsorMap[$email]) ? $this->users[$sponsorMap[$email]] : null;
            Transaction::create([
                'company_id'             => $this->company->id,
                'user_id'                => $buyer->id,
                'referred_by_user_id'    => $referrer?->id,
                'type'                   => $type,
                'amount'                 => $xp,
                'xp'                     => $xp,
                'currency'               => 'KRW',
                'status'                 => 'confirmed',
                'qualifies_for_commission' => true,
                'transaction_date'       => Carbon::today()->subDays($daysAgo),
                'reference'              => 'SEED-GU-' . $buyer->id . '-' . $daysAgo . '-' . $type,
            ]);
        };

        // ===== JIMIN'S DIRECT REFERRALS =====
        // Hyun-Woo (strong leg root)
        $txn('hyunwoo@glowup.test', 0,  55);
        $txn('hyunwoo@glowup.test', 7,  70);
        $txn('hyunwoo@glowup.test', 15, 60);
        $txn('hyunwoo@glowup.test', 24, 45);

        // Min-Ji
        $txn('minji@glowup.test', 2,  35);
        $txn('minji@glowup.test', 14, 40);

        // Tae-Hee
        $txn('taehee@glowup.test', 5,  30);
        $txn('taehee@glowup.test', 18, 25);

        // Da-Eun (moderate leg root)
        $txn('daeun@glowup.test', 1,  45);
        $txn('daeun@glowup.test', 10, 50);
        $txn('daeun@glowup.test', 22, 35);

        // Sung-Ho
        $txn('sungho@glowup.test', 3,  25);
        $txn('sungho@glowup.test', 16, 30);

        // Hae-Won (direct customer)
        $txn('haewon@glowup.test', 4,  40);
        $txn('haewon@glowup.test', 20, 35);

        // Se-Jun (direct customer)
        $txn('sejun@glowup.test', 6,  22);
        $txn('sejun@glowup.test', 25, 28);

        // ===== HYUN-WOO'S DOWNLINE =====
        // Ye-Jin
        $txn('yejin@glowup.test', 2,  50);
        $txn('yejin@glowup.test', 11, 45);

        // Ji-Hoon
        $txn('jihoon@glowup.test', 4,  30);
        $txn('jihoon@glowup.test', 19, 25);

        // ===== SOO-YEON'S NETWORK =====
        // Eun-Bi
        $txn('eunbi@glowup.test', 1,  55);
        $txn('eunbi@glowup.test', 9,  60);
        $txn('eunbi@glowup.test', 20, 45);

        // Woo-Jin
        $txn('woojin@glowup.test', 3,  35);
        $txn('woojin@glowup.test', 13, 30);

        // Na-Young
        $txn('nayoung@glowup.test', 5,  25);
        $txn('nayoung@glowup.test', 17, 22);

        // Bo-Ram (direct customer)
        $txn('boram@glowup.test', 7,  40);
        $txn('boram@glowup.test', 21, 35);

        // In-Soo (direct customer)
        $txn('insoo@glowup.test', 2,  28);

        // ===== SELF-PURCHASES (top-level, no referrer) =====
        $txn('jimin@glowup.test',   0, 50);
        $txn('sooyeon@glowup.test', 0, 45);

        // ===== SMARTSHIP ORDERS =====
        $txn('hyunwoo@glowup.test',  0, 30, 'smartship');
        $txn('daeun@glowup.test',    0, 30, 'smartship');
        $txn('yejin@glowup.test',    0, 25, 'smartship');
        $txn('eunbi@glowup.test',    0, 30, 'smartship');

        // ===== EDGE CASES =====
        // Old transaction (outside 30-day window — should NOT count)
        Transaction::create([
            'company_id'             => $this->company->id,
            'user_id'                => $this->users['hyunwoo@glowup.test']->id,
            'referred_by_user_id'    => $this->users['jimin@glowup.test']->id,
            'type'                   => 'purchase',
            'amount'                 => 150,
            'xp'                     => 150,
            'currency'               => 'KRW',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date'       => Carbon::today()->subDays(45),
            'reference'              => 'SEED-GU-OLD-1',
        ]);

        // Reversed transaction (should NOT count)
        Transaction::create([
            'company_id'             => $this->company->id,
            'user_id'                => $this->users['minji@glowup.test']->id,
            'referred_by_user_id'    => $this->users['jimin@glowup.test']->id,
            'type'                   => 'purchase',
            'amount'                 => 80,
            'xp'                     => 80,
            'currency'               => 'KRW',
            'status'                 => 'reversed',
            'qualifies_for_commission' => true,
            'transaction_date'       => Carbon::today()->subDays(3),
            'reference'              => 'SEED-GU-REVERSED-1',
        ]);

        // Sub-threshold transaction (< 20 XP — should NOT make customer "active")
        Transaction::create([
            'company_id'             => $this->company->id,
            'user_id'                => $this->users['sungho@glowup.test']->id,
            'referred_by_user_id'    => $this->users['jimin@glowup.test']->id,
            'type'                   => 'purchase',
            'amount'                 => 15,
            'xp'                     => 15,
            'currency'               => 'KRW',
            'status'                 => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date'       => Carbon::today()->subDays(1),
            'reference'              => 'SEED-GU-LOW-XP-1',
        ]);
    }

    private function createWalletAccounts(): void
    {
        foreach ($this->users as $user) {
            if (in_array($user->role, ['affiliate', 'admin'])) {
                WalletAccount::create([
                    'company_id' => $this->company->id,
                    'user_id'    => $user->id,
                    'currency'   => 'KRW',
                ]);
            }
        }
    }

    private function createCommissionHistory(): void
    {
        $affiliates = collect($this->users)->filter(fn (User $u) => $u->role === 'affiliate');

        // Generate 6 days of commission history
        for ($daysAgo = 6; $daysAgo >= 1; $daysAgo--) {
            $runDate = Carbon::today()->subDays($daysAgo);

            $run = CommissionRun::create([
                'company_id'              => $this->company->id,
                'compensation_plan_id'    => $this->plan->id,
                'run_date'               => $runDate,
                'status'                 => 'completed',
                'total_affiliate_commission' => 0,
                'total_viral_commission' => 0,
                'total_company_volume'   => 0,
                'viral_cap_triggered'    => false,
                'started_at'             => $runDate->copy()->setTime(2, 0, 0),
                'completed_at'           => $runDate->copy()->setTime(2, 0, 5),
            ]);

            $totalAff   = 0;
            $totalViral = 0;

            foreach ($affiliates as $affiliate) {
                // KRW amounts (~1300x USD scale)
                $baseAffCommission = match (true) {
                    in_array($affiliate->email, ['jimin@glowup.test'])                           => rand(700, 1400),
                    in_array($affiliate->email, ['sooyeon@glowup.test', 'hyunwoo@glowup.test'])  => rand(400, 800),
                    in_array($affiliate->email, ['daeun@glowup.test', 'eunbi@glowup.test'])      => rand(200, 600),
                    default                                                                        => rand(80, 250),
                };

                if ($baseAffCommission > 300) {
                    CommissionLedgerEntry::create([
                        'company_id'              => $this->company->id,
                        'commission_run_id'        => $run->id,
                        'user_id'                 => $affiliate->id,
                        'type'                    => 'affiliate_commission',
                        'amount'                  => number_format($baseAffCommission, 4, '.', ''),
                        'tier_achieved'           => rand(1, 6),
                        'qualification_snapshot'  => ['seeded' => true],
                        'description'             => 'Daily affiliate commission',
                        'created_at'              => $runDate,
                    ]);
                    $totalAff += $baseAffCommission;
                }

                // Viral commissions — only for top-tier affiliates
                $viralAmount = match (true) {
                    in_array($affiliate->email, ['jimin@glowup.test'])          => rand(200, 689),
                    in_array($affiliate->email, ['sooyeon@glowup.test'])        => rand(69, 346),
                    in_array($affiliate->email, ['hyunwoo@glowup.test'])        => rand(69, 173),
                    default                                                       => 0,
                };

                if ($viralAmount > 0) {
                    CommissionLedgerEntry::create([
                        'company_id'              => $this->company->id,
                        'commission_run_id'        => $run->id,
                        'user_id'                 => $affiliate->id,
                        'type'                    => 'viral_commission',
                        'amount'                  => number_format($viralAmount, 4, '.', ''),
                        'tier_achieved'           => rand(1, 5),
                        'qualification_snapshot'  => ['seeded' => true],
                        'description'             => 'Daily viral commission',
                        'created_at'              => $runDate,
                    ]);
                    $totalViral += $viralAmount;
                }
            }

            $run->update([
                'total_affiliate_commission' => round($totalAff, 2),
                'total_viral_commission'     => round($totalViral, 2),
                'total_company_volume'       => rand(800000, 1800000),
            ]);
        }

        // Create wallet movements from commission ledger entries
        foreach ($affiliates as $affiliate) {
            $wallet = WalletAccount::where('user_id', $affiliate->id)
                ->where('company_id', $this->company->id)
                ->first();

            if (! $wallet) {
                continue;
            }

            $entries = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
                ->where('user_id', $affiliate->id)
                ->where('company_id', $this->company->id)
                ->get();

            foreach ($entries as $entry) {
                $isPending = $entry->created_at && $entry->created_at->gt(Carbon::today()->subDays(7));

                WalletMovement::create([
                    'company_id'       => $this->company->id,
                    'wallet_account_id' => $wallet->id,
                    'type'             => 'commission_credit',
                    'amount'           => $entry->amount,
                    'status'           => $isPending ? 'pending' : 'released',
                    'reference_type'   => 'commission_ledger_entry',
                    'reference_id'     => $entry->id,
                    'description'      => 'Commission credit from ' . $entry->type,
                    'effective_at'     => $entry->created_at ?? now(),
                ]);
            }
        }
    }

    private function glowUpConfig(): array
    {
        return [
            'plan' => [
                'name'                  => 'GlowUp Affiliate Rewards',
                'version'               => '1.0',
                'effective_date'        => '2026-01-01',
                'currency'              => 'KRW',
                'calculation_frequency' => 'daily',
                'credit_frequency'      => 'weekly',
                'day_definition'        => [
                    'start'    => '00:00:00',
                    'end'      => '23:59:59',
                    'timezone' => 'Asia/Seoul',
                ],
            ],
            'qualification' => [
                'rolling_days'                              => 30,
                'active_customer_min_order_xp'              => 20,
                'active_customer_threshold_type'            => 'per_order',
                'affiliate_inactivity_downgrade_months'     => 12,
                'affiliate_inactivity_requires_no_orders'   => true,
                'affiliate_inactivity_requires_no_rewards'  => true,
            ],
            'affiliate_commission' => [
                'type'                          => 'tiered_percentage',
                'payout_method'                 => 'daily_new_volume',
                'basis'                         => 'referred_volume_30d',
                'customer_basis'                => 'referred_active_customers_30d',
                'self_purchase_earns_commission' => false,
                'includes_smartship'             => true,
                'tiers'                          => [
                    ['min_active_customers' => 1,  'min_referred_volume' => 0,    'rate' => 0.10],
                    ['min_active_customers' => 2,  'min_referred_volume' => 200,  'rate' => 0.11],
                    ['min_active_customers' => 2,  'min_referred_volume' => 400,  'rate' => 0.12],
                    ['min_active_customers' => 3,  'min_referred_volume' => 600,  'rate' => 0.13],
                    ['min_active_customers' => 4,  'min_referred_volume' => 800,  'rate' => 0.14],
                    ['min_active_customers' => 5,  'min_referred_volume' => 1000, 'rate' => 0.15],
                    ['min_active_customers' => 6,  'min_referred_volume' => 1200, 'rate' => 0.16],
                    ['min_active_customers' => 7,  'min_referred_volume' => 1400, 'rate' => 0.17],
                    ['min_active_customers' => 8,  'min_referred_volume' => 1600, 'rate' => 0.18],
                    ['min_active_customers' => 9,  'min_referred_volume' => 1800, 'rate' => 0.19],
                    ['min_active_customers' => 10, 'min_referred_volume' => 2000, 'rate' => 0.20],
                ],
            ],
            'viral_commission' => [
                'type'      => 'tiered_fixed_daily',
                'basis'     => 'qualifying_viral_volume_30d',
                'tree'      => 'enrollment',
                'qvv_algorithm' => [
                    'description' => 'Large leg cap with 2/3 small leg benchmark',
                    'steps'       => [
                        '1. Sum all leg volumes',
                        '2. Identify Large Leg (L)',
                        '3. Sum Small Legs (Y)',
                        '4. Benchmark X = (2/3) * Y',
                        '5. If X >= L: no cap',
                        '6. If X < L: cap L to X',
                        '7. QVV = capped_L + Y',
                    ],
                ],
                // KRW-denominated tiers (~1300x USD values for daily_reward)
                'tiers' => [
                    ['tier' => 1,  'min_active_customers' => 2, 'min_referred_volume' => 50,   'min_qvv' => 100,       'daily_reward' => 700],
                    ['tier' => 2,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 250,       'daily_reward' => 1730],
                    ['tier' => 3,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 500,       'daily_reward' => 3470],
                    ['tier' => 4,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 750,       'daily_reward' => 5200],
                    ['tier' => 5,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 1000,      'daily_reward' => 6500],
                    ['tier' => 6,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 1500,      'daily_reward' => 9750],
                    ['tier' => 7,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2000,      'daily_reward' => 13000],
                    ['tier' => 8,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2500,      'daily_reward' => 16250],
                    ['tier' => 9,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 3500,      'daily_reward' => 22750],
                    ['tier' => 10, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 5000,      'daily_reward' => 30330],
                    ['tier' => 11, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 7000,      'daily_reward' => 42470],
                    ['tier' => 12, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 9000,      'daily_reward' => 54600],
                    ['tier' => 13, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 12000,     'daily_reward' => 67600],
                    ['tier' => 14, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 15000,     'daily_reward' => 78000],
                    ['tier' => 15, 'min_active_customers' => 2, 'min_referred_volume' => 300,  'min_qvv' => 20000,     'daily_reward' => 95330],
                    ['tier' => 16, 'min_active_customers' => 2, 'min_referred_volume' => 300,  'min_qvv' => 25000,     'daily_reward' => 108330],
                    ['tier' => 17, 'min_active_customers' => 2, 'min_referred_volume' => 300,  'min_qvv' => 30000,     'daily_reward' => 117000],
                    ['tier' => 18, 'min_active_customers' => 2, 'min_referred_volume' => 400,  'min_qvv' => 40000,     'daily_reward' => 138670],
                    ['tier' => 19, 'min_active_customers' => 2, 'min_referred_volume' => 400,  'min_qvv' => 50000,     'daily_reward' => 162500],
                    ['tier' => 20, 'min_active_customers' => 3, 'min_referred_volume' => 500,  'min_qvv' => 60000,     'daily_reward' => 182000],
                    ['tier' => 21, 'min_active_customers' => 3, 'min_referred_volume' => 500,  'min_qvv' => 80000,     'daily_reward' => 242670],
                    ['tier' => 22, 'min_active_customers' => 3, 'min_referred_volume' => 500,  'min_qvv' => 120000,    'daily_reward' => 338000],
                    ['tier' => 23, 'min_active_customers' => 3, 'min_referred_volume' => 600,  'min_qvv' => 160000,    'daily_reward' => 416000],
                    ['tier' => 24, 'min_active_customers' => 3, 'min_referred_volume' => 600,  'min_qvv' => 240000,    'daily_reward' => 624000],
                    ['tier' => 25, 'min_active_customers' => 3, 'min_referred_volume' => 600,  'min_qvv' => 320000,    'daily_reward' => 832000],
                    ['tier' => 26, 'min_active_customers' => 4, 'min_referred_volume' => 800,  'min_qvv' => 400000,    'daily_reward' => 1040000],
                    ['tier' => 27, 'min_active_customers' => 4, 'min_referred_volume' => 800,  'min_qvv' => 500000,    'daily_reward' => 1300000],
                    ['tier' => 28, 'min_active_customers' => 4, 'min_referred_volume' => 800,  'min_qvv' => 600000,    'daily_reward' => 1560000],
                    ['tier' => 29, 'min_active_customers' => 4, 'min_referred_volume' => 1000, 'min_qvv' => 700000,    'daily_reward' => 1820000],
                    ['tier' => 30, 'min_active_customers' => 4, 'min_referred_volume' => 1000, 'min_qvv' => 800000,    'daily_reward' => 2080000],
                    ['tier' => 31, 'min_active_customers' => 5, 'min_referred_volume' => 1000, 'min_qvv' => 1000000,   'daily_reward' => 2600000],
                    ['tier' => 32, 'min_active_customers' => 5, 'min_referred_volume' => 1200, 'min_qvv' => 1200000,   'daily_reward' => 3120000],
                    ['tier' => 33, 'min_active_customers' => 5, 'min_referred_volume' => 1200, 'min_qvv' => 1400000,   'daily_reward' => 3640000],
                    ['tier' => 34, 'min_active_customers' => 5, 'min_referred_volume' => 1200, 'min_qvv' => 1700000,   'daily_reward' => 3867500],
                    ['tier' => 35, 'min_active_customers' => 5, 'min_referred_volume' => 1300, 'min_qvv' => 2000000,   'daily_reward' => 4160000],
                    ['tier' => 36, 'min_active_customers' => 5, 'min_referred_volume' => 1300, 'min_qvv' => 2300000,   'daily_reward' => 4584670],
                    ['tier' => 37, 'min_active_customers' => 5, 'min_referred_volume' => 1300, 'min_qvv' => 2600000,   'daily_reward' => 5033560],
                    ['tier' => 38, 'min_active_customers' => 6, 'min_referred_volume' => 1400, 'min_qvv' => 3000000,   'daily_reward' => 5807920],
                    ['tier' => 39, 'min_active_customers' => 6, 'min_referred_volume' => 1400, 'min_qvv' => 3400000,   'daily_reward' => 6582330],
                    ['tier' => 40, 'min_active_customers' => 6, 'min_referred_volume' => 1400, 'min_qvv' => 3800000,   'daily_reward' => 7052500],
                    ['tier' => 41, 'min_active_customers' => 6, 'min_referred_volume' => 1500, 'min_qvv' => 4200000,   'daily_reward' => 7522670],
                    ['tier' => 42, 'min_active_customers' => 6, 'min_referred_volume' => 1500, 'min_qvv' => 4600000,   'daily_reward' => 7992830],
                    ['tier' => 43, 'min_active_customers' => 6, 'min_referred_volume' => 1500, 'min_qvv' => 5000000,   'daily_reward' => 8463000],
                    ['tier' => 44, 'min_active_customers' => 6, 'min_referred_volume' => 1700, 'min_qvv' => 5500000,   'daily_reward' => 8933170],
                    ['tier' => 45, 'min_active_customers' => 7, 'min_referred_volume' => 1700, 'min_qvv' => 6000000,   'daily_reward' => 9403330],
                    ['tier' => 46, 'min_active_customers' => 7, 'min_referred_volume' => 1700, 'min_qvv' => 6500000,   'daily_reward' => 9728330],
                    ['tier' => 47, 'min_active_customers' => 7, 'min_referred_volume' => 1800, 'min_qvv' => 7000000,   'daily_reward' => 10018670],
                    ['tier' => 48, 'min_active_customers' => 7, 'min_referred_volume' => 1800, 'min_qvv' => 7500000,   'daily_reward' => 10356670],
                    ['tier' => 49, 'min_active_customers' => 7, 'min_referred_volume' => 1800, 'min_qvv' => 8000000,   'daily_reward' => 10478000],
                    ['tier' => 50, 'min_active_customers' => 7, 'min_referred_volume' => 2000, 'min_qvv' => 8500000,   'daily_reward' => 10914580],
                    ['tier' => 51, 'min_active_customers' => 8, 'min_referred_volume' => 2000, 'min_qvv' => 9000000,   'daily_reward' => 11351170],
                    ['tier' => 52, 'min_active_customers' => 8, 'min_referred_volume' => 2000, 'min_qvv' => 9500000,   'daily_reward' => 11787750],
                    ['tier' => 53, 'min_active_customers' => 8, 'min_referred_volume' => 2100, 'min_qvv' => 10000000,  'daily_reward' => 12224330],
                    ['tier' => 54, 'min_active_customers' => 8, 'min_referred_volume' => 2100, 'min_qvv' => 10500000,  'daily_reward' => 12660920],
                    ['tier' => 55, 'min_active_customers' => 8, 'min_referred_volume' => 2100, 'min_qvv' => 11000000,  'daily_reward' => 13097500],
                    ['tier' => 56, 'min_active_customers' => 8, 'min_referred_volume' => 2200, 'min_qvv' => 11500000,  'daily_reward' => 13534080],
                    ['tier' => 57, 'min_active_customers' => 8, 'min_referred_volume' => 2200, 'min_qvv' => 12000000,  'daily_reward' => 13970670],
                    ['tier' => 58, 'min_active_customers' => 8, 'min_referred_volume' => 2200, 'min_qvv' => 12500000,  'daily_reward' => 14407250],
                    ['tier' => 59, 'min_active_customers' => 8, 'min_referred_volume' => 2300, 'min_qvv' => 13000000,  'daily_reward' => 14843830],
                    ['tier' => 60, 'min_active_customers' => 8, 'min_referred_volume' => 2300, 'min_qvv' => 13500000,  'daily_reward' => 15280420],
                    ['tier' => 61, 'min_active_customers' => 8, 'min_referred_volume' => 2300, 'min_qvv' => 14000000,  'daily_reward' => 15717000],
                    ['tier' => 62, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 14500000,  'daily_reward' => 16153580],
                    ['tier' => 63, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 15000000,  'daily_reward' => 16590170],
                    ['tier' => 64, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 15500000,  'daily_reward' => 17026750],
                    ['tier' => 65, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 16000000,  'daily_reward' => 17463330],
                    ['tier' => 66, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 16500000,  'daily_reward' => 17899920],
                    ['tier' => 67, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 17000000,  'daily_reward' => 18336500],
                    ['tier' => 68, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 17500000,  'daily_reward' => 18773080],
                    ['tier' => 69, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 18000000,  'daily_reward' => 19209670],
                    ['tier' => 70, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 18500000,  'daily_reward' => 19646250],
                    ['tier' => 71, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 19000000,  'daily_reward' => 20082830],
                    ['tier' => 72, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 19500000,  'daily_reward' => 20519420],
                    ['tier' => 73, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 20000000,  'daily_reward' => 20956000],
                ],
            ],
            'caps' => [
                'total_payout_cap_percent'      => 0.35,
                'total_payout_cap_enforcement'  => 'proportional_reduction',
                'total_payout_cap_window'       => 'rolling_30d',
                'viral_commission_cap' => [
                    'percent_of_company_volume' => 0.15,
                    'window'                    => 'rolling_30d',
                    'enforcement'               => 'daily_reduction',
                    'reduction_method'          => 'proportional_overage',
                    'description'               => 'If rolling 30-day viral commissions exceed 15% of rolling 30-day company volume, reduce all viral payouts for that day by the overage percentage',
                ],
                'enforcement_order' => ['viral_cap_first', 'then_global_cap'],
            ],
            'wallet' => [
                'credit_timing'        => 'weekly',
                'release_delay_days'   => 0,
                'minimum_withdrawal'   => 0,
                'clawback_window_days' => 30,
            ],
        ];
    }

    private function seedBonusTypes(): void
    {
        // 1. Matching Bonus (gen1=12%, gen2=8%)
        $matching = BonusType::create([
            'company_id'           => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'type'                 => BonusTypeEnum::Matching,
            'name'                 => 'Matching Bonus',
            'description'          => 'Earn a percentage of your personally enrolled affiliates\' commissions.',
            'is_active'            => true,
            'priority'             => 10,
        ]);

        BonusTier::create([
            'bonus_type_id'    => $matching->id,
            'level'            => 1,
            'label'            => 'Generation 1',
            'qualifier_value'  => null,
            'qualifier_type'   => 'generation',
            'rate'             => 0.1200,
            'amount'           => null,
        ]);

        BonusTier::create([
            'bonus_type_id'    => $matching->id,
            'level'            => 2,
            'label'            => 'Generation 2',
            'qualifier_value'  => null,
            'qualifier_type'   => 'generation',
            'rate'             => 0.0800,
            'amount'           => null,
        ]);

        // 2. Rank Advancement Bonus (3 tiers, KRW amounts)
        $rankAdvancement = BonusType::create([
            'company_id'           => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'type'                 => BonusTypeEnum::RankAdvancement,
            'name'                 => 'Rank Advancement Bonus',
            'description'          => 'One-time KRW bonus awarded when an affiliate first achieves a new rank.',
            'is_active'            => true,
            'priority'             => 20,
        ]);

        $rankTiers = [
            ['level' => 1, 'label' => 'Bronze', 'qualifier_type' => 'rank', 'qualifier_value' => 1, 'amount' => 130000.0000],
            ['level' => 2, 'label' => 'Silver', 'qualifier_type' => 'rank', 'qualifier_value' => 2, 'amount' => 325000.0000],
            ['level' => 3, 'label' => 'Gold',   'qualifier_type' => 'rank', 'qualifier_value' => 3, 'amount' => 650000.0000],
        ];

        foreach ($rankTiers as $tier) {
            BonusTier::create([
                'bonus_type_id'   => $rankAdvancement->id,
                'level'           => $tier['level'],
                'label'           => $tier['label'],
                'qualifier_value' => $tier['qualifier_value'],
                'qualifier_type'  => $tier['qualifier_type'],
                'rate'            => null,
                'amount'          => $tier['amount'],
            ]);
        }
    }

    private function seedCompanySettings(): void
    {
        $settings = [
            'inventory_loading_threshold' => '0.80',
            'churn_at_risk_days'          => '30',
            'churn_inactive_days'         => '60',
            'churn_volume_decline_pct'    => '50',
            'churn_stagnant_leader_days'  => '60',
        ];

        foreach ($settings as $key => $value) {
            CompanySetting::create([
                'company_id' => $this->company->id,
                'key'        => $key,
                'value'      => $value,
            ]);
        }
    }
}
