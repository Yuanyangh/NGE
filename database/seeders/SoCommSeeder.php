<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompensationPlan;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletAccount;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SoCommSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::create([
            'name' => 'SoComm',
            'slug' => 'socomm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        CompensationPlan::create([
            'company_id' => $company->id,
            'name' => 'SoComm Affiliate Rewards Program',
            'version' => '1.0',
            'config' => $this->soCommConfig(),
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'is_active' => true,
        ]);

        $users = $this->createUsers($company);
        $this->createGenealogyTree($company, $users);
        $this->createTransactions($company, $users);
        $this->createWalletAccounts($company, $users);
    }

    private function createUsers(Company $company): array
    {
        $userData = [
            // Admin
            ['name' => 'Admin User', 'email' => 'admin@socomm.test', 'role' => 'admin'],

            // Top-level affiliates (sponsors)
            ['name' => 'Alice Johnson', 'email' => 'alice@socomm.test', 'role' => 'affiliate'],
            ['name' => 'Bob Smith', 'email' => 'bob@socomm.test', 'role' => 'affiliate'],

            // Alice's direct referrals (her legs)
            ['name' => 'Charlie Brown', 'email' => 'charlie@socomm.test', 'role' => 'affiliate'],   // Leg 1 root
            ['name' => 'Diana Prince', 'email' => 'diana@socomm.test', 'role' => 'affiliate'],      // Leg 2 root
            ['name' => 'Eve Wilson', 'email' => 'eve@socomm.test', 'role' => 'affiliate'],           // Leg 3 root

            // Charlie's downline (Alice's Leg 1 depth)
            ['name' => 'Frank Miller', 'email' => 'frank@socomm.test', 'role' => 'affiliate'],
            ['name' => 'Grace Lee', 'email' => 'grace@socomm.test', 'role' => 'customer'],

            // Frank's downline (Alice's Leg 1, depth 3)
            ['name' => 'Hank Davis', 'email' => 'hank@socomm.test', 'role' => 'customer'],
            ['name' => 'Ivy Chen', 'email' => 'ivy@socomm.test', 'role' => 'customer'],

            // Diana's downline (Alice's Leg 2 depth)
            ['name' => 'Jack Taylor', 'email' => 'jack@socomm.test', 'role' => 'affiliate'],
            ['name' => 'Karen White', 'email' => 'karen@socomm.test', 'role' => 'customer'],

            // Eve's downline (Alice's Leg 3 depth)
            ['name' => 'Leo Martinez', 'email' => 'leo@socomm.test', 'role' => 'customer'],

            // Bob's direct referrals
            ['name' => 'Mia Anderson', 'email' => 'mia@socomm.test', 'role' => 'affiliate'],
            ['name' => 'Noah Thomas', 'email' => 'noah@socomm.test', 'role' => 'customer'],

            // Mia's downline
            ['name' => 'Olivia Jackson', 'email' => 'olivia@socomm.test', 'role' => 'customer'],
            ['name' => 'Peter Harris', 'email' => 'peter@socomm.test', 'role' => 'customer'],

            // Standalone customers referred by various affiliates
            ['name' => 'Quinn Robinson', 'email' => 'quinn@socomm.test', 'role' => 'customer'],
            ['name' => 'Rachel Clark', 'email' => 'rachel@socomm.test', 'role' => 'customer'],
            ['name' => 'Sam Lewis', 'email' => 'sam@socomm.test', 'role' => 'customer'],
            ['name' => 'Tina Walker', 'email' => 'tina@socomm.test', 'role' => 'customer'],
            ['name' => 'Uma Hall', 'email' => 'uma@socomm.test', 'role' => 'customer'],
            ['name' => 'Victor King', 'email' => 'victor@socomm.test', 'role' => 'customer'],
        ];

        $users = [];
        foreach ($userData as $data) {
            $users[$data['email']] = User::create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => 'password',
                'role' => $data['role'],
                'status' => 'active',
                'enrolled_at' => $data['role'] === 'affiliate' ? now()->subMonths(rand(2, 6)) : null,
            ]);
        }

        return $users;
    }

    private function createGenealogyTree(Company $company, array $users): void
    {
        $makeNode = function (User $user, ?GenealogyNode $sponsor, int $depth) use ($company): GenealogyNode {
            return GenealogyNode::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'sponsor_id' => $sponsor?->id,
                'tree_depth' => $depth,
            ]);
        };

        // Level 0: Top-level (no sponsor)
        $adminNode   = $makeNode($users['admin@socomm.test'], null, 0);
        $aliceNode   = $makeNode($users['alice@socomm.test'], null, 0);
        $bobNode     = $makeNode($users['bob@socomm.test'], null, 0);

        // Level 1: Alice's direct referrals (3 legs)
        $charlieNode = $makeNode($users['charlie@socomm.test'], $aliceNode, 1);
        $dianaNode   = $makeNode($users['diana@socomm.test'], $aliceNode, 1);
        $eveNode     = $makeNode($users['eve@socomm.test'], $aliceNode, 1);

        // Level 2: Charlie's downline (Alice Leg 1)
        $frankNode   = $makeNode($users['frank@socomm.test'], $charlieNode, 2);
        $graceNode   = $makeNode($users['grace@socomm.test'], $charlieNode, 2);

        // Level 3: Frank's downline (Alice Leg 1, deep)
        $hankNode    = $makeNode($users['hank@socomm.test'], $frankNode, 3);
        $ivyNode     = $makeNode($users['ivy@socomm.test'], $frankNode, 3);

        // Level 2: Diana's downline (Alice Leg 2)
        $jackNode    = $makeNode($users['jack@socomm.test'], $dianaNode, 2);
        $karenNode   = $makeNode($users['karen@socomm.test'], $dianaNode, 2);

        // Level 2: Eve's downline (Alice Leg 3)
        $leoNode     = $makeNode($users['leo@socomm.test'], $eveNode, 2);

        // Level 1: Bob's direct referrals
        $miaNode     = $makeNode($users['mia@socomm.test'], $bobNode, 1);
        $noahNode    = $makeNode($users['noah@socomm.test'], $bobNode, 1);

        // Level 2: Mia's downline
        $oliviaNode  = $makeNode($users['olivia@socomm.test'], $miaNode, 2);
        $peterNode   = $makeNode($users['peter@socomm.test'], $miaNode, 2);

        // Standalone customers referred by various affiliates
        $makeNode($users['quinn@socomm.test'], $aliceNode, 1);
        $makeNode($users['rachel@socomm.test'], $aliceNode, 1);
        $makeNode($users['sam@socomm.test'], $bobNode, 1);
        $makeNode($users['tina@socomm.test'], $charlieNode, 2);
        $makeNode($users['uma@socomm.test'], $dianaNode, 2);
        $makeNode($users['victor@socomm.test'], $eveNode, 2);
    }

    private function createTransactions(Company $company, array $users): void
    {
        $today = Carbon::today();

        // Helper to get the sponsor (referred_by) for a user based on genealogy
        $sponsorMap = [
            // Alice's direct referrals — referred_by = Alice
            'charlie@socomm.test' => 'alice@socomm.test',
            'diana@socomm.test'   => 'alice@socomm.test',
            'eve@socomm.test'     => 'alice@socomm.test',
            'quinn@socomm.test'   => 'alice@socomm.test',
            'rachel@socomm.test'  => 'alice@socomm.test',

            // Charlie's referrals
            'frank@socomm.test'   => 'charlie@socomm.test',
            'grace@socomm.test'   => 'charlie@socomm.test',
            'tina@socomm.test'    => 'charlie@socomm.test',

            // Frank's referrals
            'hank@socomm.test'    => 'frank@socomm.test',
            'ivy@socomm.test'     => 'frank@socomm.test',

            // Diana's referrals
            'jack@socomm.test'    => 'diana@socomm.test',
            'karen@socomm.test'   => 'diana@socomm.test',
            'uma@socomm.test'     => 'diana@socomm.test',

            // Eve's referrals
            'leo@socomm.test'     => 'eve@socomm.test',
            'victor@socomm.test'  => 'eve@socomm.test',

            // Bob's referrals
            'mia@socomm.test'     => 'bob@socomm.test',
            'noah@socomm.test'    => 'bob@socomm.test',
            'sam@socomm.test'     => 'bob@socomm.test',

            // Mia's referrals
            'olivia@socomm.test'  => 'mia@socomm.test',
            'peter@socomm.test'   => 'mia@socomm.test',
        ];

        $txn = function (string $buyerEmail, int $daysAgo, float $xp, string $type = 'purchase') use ($company, $users, $sponsorMap) {
            $buyer = $users[$buyerEmail];
            $referrer = isset($sponsorMap[$buyerEmail]) ? $users[$sponsorMap[$buyerEmail]] : null;

            Transaction::create([
                'company_id' => $company->id,
                'user_id' => $buyer->id,
                'referred_by_user_id' => $referrer?->id,
                'type' => $type,
                'amount' => $xp,
                'xp' => $xp,
                'currency' => 'USD',
                'status' => 'confirmed',
                'qualifies_for_commission' => true,
                'transaction_date' => Carbon::today()->subDays($daysAgo),
                'reference' => 'SEED-' . $buyer->id . '-' . $daysAgo,
            ]);
        };

        // === Alice's referred customers — spread across 30-day window ===
        // Charlie (affiliate, Leg 1 root) — multiple orders
        $txn('charlie@socomm.test', 0, 50);    // Today
        $txn('charlie@socomm.test', 10, 80);
        $txn('charlie@socomm.test', 20, 60);

        // Diana (affiliate, Leg 2 root)
        $txn('diana@socomm.test', 3, 40);
        $txn('diana@socomm.test', 15, 70);

        // Eve (affiliate, Leg 3 root)
        $txn('eve@socomm.test', 5, 30);
        $txn('eve@socomm.test', 25, 50);

        // Quinn (customer, direct referral of Alice)
        $txn('quinn@socomm.test', 2, 25);
        $txn('quinn@socomm.test', 12, 35);

        // Rachel (customer, direct referral of Alice)
        $txn('rachel@socomm.test', 7, 45);

        // === Leg 1 deep volume (Charlie's subtree) ===
        // Frank's purchases (referred by Charlie)
        $txn('frank@socomm.test', 1, 100);
        $txn('frank@socomm.test', 14, 75);

        // Grace's purchases (referred by Charlie)
        $txn('grace@socomm.test', 4, 30);

        // Tina's purchases (referred by Charlie)
        $txn('tina@socomm.test', 8, 25);

        // Hank's purchases (referred by Frank, deep in Leg 1)
        $txn('hank@socomm.test', 3, 50);
        $txn('hank@socomm.test', 18, 40);

        // Ivy's purchases (referred by Frank, deep in Leg 1)
        $txn('ivy@socomm.test', 6, 35);

        // === Leg 2 volume (Diana's subtree) ===
        // Jack's purchases (referred by Diana)
        $txn('jack@socomm.test', 2, 60);
        $txn('jack@socomm.test', 22, 45);

        // Karen's purchases (referred by Diana)
        $txn('karen@socomm.test', 9, 30);

        // Uma's purchases (referred by Diana)
        $txn('uma@socomm.test', 11, 40);

        // === Leg 3 volume (Eve's subtree) ===
        // Leo's purchases (referred by Eve)
        $txn('leo@socomm.test', 4, 25);

        // Victor's purchases (referred by Eve)
        $txn('victor@socomm.test', 13, 20);

        // === Bob's referred customers ===
        $txn('mia@socomm.test', 1, 60);
        $txn('mia@socomm.test', 16, 40);
        $txn('noah@socomm.test', 5, 30);
        $txn('sam@socomm.test', 8, 25);

        // === Mia's referred customers ===
        $txn('olivia@socomm.test', 3, 35);
        $txn('peter@socomm.test', 7, 40);

        // === SmartShip orders ===
        $txn('charlie@socomm.test', 0, 30, 'smartship');
        $txn('diana@socomm.test', 0, 30, 'smartship');
        $txn('frank@socomm.test', 0, 30, 'smartship');

        // === Affiliate self-purchases (do NOT earn them commission, but count for sponsor) ===
        $txn('alice@socomm.test', 0, 50);   // No referrer for Alice (top-level)
        $txn('bob@socomm.test', 0, 40);     // No referrer for Bob (top-level)

        // === Transactions outside the window (should NOT count) ===
        Transaction::create([
            'company_id' => $company->id,
            'user_id' => $users['charlie@socomm.test']->id,
            'referred_by_user_id' => $users['alice@socomm.test']->id,
            'type' => 'purchase',
            'amount' => 200,
            'xp' => 200,
            'currency' => 'USD',
            'status' => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date' => Carbon::today()->subDays(45),
            'reference' => 'SEED-OLD-1',
        ]);

        // === Reversed transaction (should NOT count) ===
        Transaction::create([
            'company_id' => $company->id,
            'user_id' => $users['quinn@socomm.test']->id,
            'referred_by_user_id' => $users['alice@socomm.test']->id,
            'type' => 'purchase',
            'amount' => 100,
            'xp' => 100,
            'currency' => 'USD',
            'status' => 'reversed',
            'qualifies_for_commission' => true,
            'transaction_date' => Carbon::today()->subDays(5),
            'reference' => 'SEED-REVERSED-1',
        ]);

        // === Sub-threshold transaction (< 20 XP, should not make customer "active") ===
        Transaction::create([
            'company_id' => $company->id,
            'user_id' => $users['victor@socomm.test']->id,
            'referred_by_user_id' => $users['eve@socomm.test']->id,
            'type' => 'purchase',
            'amount' => 15,
            'xp' => 15,
            'currency' => 'USD',
            'status' => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date' => Carbon::today()->subDays(2),
            'reference' => 'SEED-LOW-XP-1',
        ]);
    }

    private function createWalletAccounts(Company $company, array $users): void
    {
        foreach ($users as $user) {
            if ($user->role === 'affiliate') {
                WalletAccount::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'currency' => 'USD',
                ]);
            }
        }
    }

    private function soCommConfig(): array
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
                    'steps' => [
                        '1. Sum all leg volumes',
                        '2. Identify Large Leg (L)',
                        '3. Sum Small Legs (Y)',
                        '4. Benchmark X = (2/3) * Y',
                        '5. If X >= L: no cap',
                        '6. If X < L: cap L to X',
                        '7. QVV = capped_L + Y',
                    ],
                ],
                'tiers' => [
                    ['tier' => 1,  'min_active_customers' => 2, 'min_referred_volume' => 50,   'min_qvv' => 100,       'daily_reward' => 0.53],
                    ['tier' => 2,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 250,       'daily_reward' => 1.33],
                    ['tier' => 3,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 500,       'daily_reward' => 2.67],
                    ['tier' => 4,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 750,       'daily_reward' => 4.00],
                    ['tier' => 5,  'min_active_customers' => 2, 'min_referred_volume' => 100,  'min_qvv' => 1000,      'daily_reward' => 5.00],
                    ['tier' => 6,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 1500,      'daily_reward' => 7.50],
                    ['tier' => 7,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2000,      'daily_reward' => 10.00],
                    ['tier' => 8,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 2500,      'daily_reward' => 12.50],
                    ['tier' => 9,  'min_active_customers' => 2, 'min_referred_volume' => 150,  'min_qvv' => 3500,      'daily_reward' => 17.50],
                    ['tier' => 10, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 5000,      'daily_reward' => 23.33],
                    ['tier' => 11, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 7000,      'daily_reward' => 32.67],
                    ['tier' => 12, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 9000,      'daily_reward' => 42.00],
                    ['tier' => 13, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 12000,     'daily_reward' => 52.00],
                    ['tier' => 14, 'min_active_customers' => 2, 'min_referred_volume' => 200,  'min_qvv' => 15000,     'daily_reward' => 60.00],
                    ['tier' => 15, 'min_active_customers' => 2, 'min_referred_volume' => 300,  'min_qvv' => 20000,     'daily_reward' => 73.33],
                    ['tier' => 16, 'min_active_customers' => 2, 'min_referred_volume' => 300,  'min_qvv' => 25000,     'daily_reward' => 83.33],
                    ['tier' => 17, 'min_active_customers' => 2, 'min_referred_volume' => 300,  'min_qvv' => 30000,     'daily_reward' => 90.00],
                    ['tier' => 18, 'min_active_customers' => 2, 'min_referred_volume' => 400,  'min_qvv' => 40000,     'daily_reward' => 106.67],
                    ['tier' => 19, 'min_active_customers' => 2, 'min_referred_volume' => 400,  'min_qvv' => 50000,     'daily_reward' => 125.00],
                    ['tier' => 20, 'min_active_customers' => 3, 'min_referred_volume' => 500,  'min_qvv' => 60000,     'daily_reward' => 140.00],
                    ['tier' => 21, 'min_active_customers' => 3, 'min_referred_volume' => 500,  'min_qvv' => 80000,     'daily_reward' => 186.67],
                    ['tier' => 22, 'min_active_customers' => 3, 'min_referred_volume' => 500,  'min_qvv' => 120000,    'daily_reward' => 260.00],
                    ['tier' => 23, 'min_active_customers' => 3, 'min_referred_volume' => 600,  'min_qvv' => 160000,    'daily_reward' => 320.00],
                    ['tier' => 24, 'min_active_customers' => 3, 'min_referred_volume' => 600,  'min_qvv' => 240000,    'daily_reward' => 480.00],
                    ['tier' => 25, 'min_active_customers' => 3, 'min_referred_volume' => 600,  'min_qvv' => 320000,    'daily_reward' => 640.00],
                    ['tier' => 26, 'min_active_customers' => 4, 'min_referred_volume' => 800,  'min_qvv' => 400000,    'daily_reward' => 800.00],
                    ['tier' => 27, 'min_active_customers' => 4, 'min_referred_volume' => 800,  'min_qvv' => 500000,    'daily_reward' => 1000.00],
                    ['tier' => 28, 'min_active_customers' => 4, 'min_referred_volume' => 800,  'min_qvv' => 600000,    'daily_reward' => 1200.00],
                    ['tier' => 29, 'min_active_customers' => 4, 'min_referred_volume' => 1000, 'min_qvv' => 700000,    'daily_reward' => 1400.00],
                    ['tier' => 30, 'min_active_customers' => 4, 'min_referred_volume' => 1000, 'min_qvv' => 800000,    'daily_reward' => 1600.00],
                    ['tier' => 31, 'min_active_customers' => 5, 'min_referred_volume' => 1000, 'min_qvv' => 1000000,   'daily_reward' => 2000.00],
                    ['tier' => 32, 'min_active_customers' => 5, 'min_referred_volume' => 1200, 'min_qvv' => 1200000,   'daily_reward' => 2400.00],
                    ['tier' => 33, 'min_active_customers' => 5, 'min_referred_volume' => 1200, 'min_qvv' => 1400000,   'daily_reward' => 2800.00],
                    ['tier' => 34, 'min_active_customers' => 5, 'min_referred_volume' => 1200, 'min_qvv' => 1700000,   'daily_reward' => 2975.00],
                    ['tier' => 35, 'min_active_customers' => 5, 'min_referred_volume' => 1300, 'min_qvv' => 2000000,   'daily_reward' => 3200.00],
                    ['tier' => 36, 'min_active_customers' => 5, 'min_referred_volume' => 1300, 'min_qvv' => 2300000,   'daily_reward' => 3526.67],
                    ['tier' => 37, 'min_active_customers' => 5, 'min_referred_volume' => 1300, 'min_qvv' => 2600000,   'daily_reward' => 3871.97],
                    ['tier' => 38, 'min_active_customers' => 6, 'min_referred_volume' => 1400, 'min_qvv' => 3000000,   'daily_reward' => 4467.63],
                    ['tier' => 39, 'min_active_customers' => 6, 'min_referred_volume' => 1400, 'min_qvv' => 3400000,   'daily_reward' => 5063.33],
                    ['tier' => 40, 'min_active_customers' => 6, 'min_referred_volume' => 1400, 'min_qvv' => 3800000,   'daily_reward' => 5425.00],
                    ['tier' => 41, 'min_active_customers' => 6, 'min_referred_volume' => 1500, 'min_qvv' => 4200000,   'daily_reward' => 5786.67],
                    ['tier' => 42, 'min_active_customers' => 6, 'min_referred_volume' => 1500, 'min_qvv' => 4600000,   'daily_reward' => 6148.33],
                    ['tier' => 43, 'min_active_customers' => 6, 'min_referred_volume' => 1500, 'min_qvv' => 5000000,   'daily_reward' => 6510.00],
                    ['tier' => 44, 'min_active_customers' => 6, 'min_referred_volume' => 1700, 'min_qvv' => 5500000,   'daily_reward' => 6871.67],
                    ['tier' => 45, 'min_active_customers' => 7, 'min_referred_volume' => 1700, 'min_qvv' => 6000000,   'daily_reward' => 7233.33],
                    ['tier' => 46, 'min_active_customers' => 7, 'min_referred_volume' => 1700, 'min_qvv' => 6500000,   'daily_reward' => 7483.33],
                    ['tier' => 47, 'min_active_customers' => 7, 'min_referred_volume' => 1800, 'min_qvv' => 7000000,   'daily_reward' => 7706.67],
                    ['tier' => 48, 'min_active_customers' => 7, 'min_referred_volume' => 1800, 'min_qvv' => 7500000,   'daily_reward' => 7966.67],
                    ['tier' => 49, 'min_active_customers' => 7, 'min_referred_volume' => 1800, 'min_qvv' => 8000000,   'daily_reward' => 8060.00],
                    ['tier' => 50, 'min_active_customers' => 7, 'min_referred_volume' => 2000, 'min_qvv' => 8500000,   'daily_reward' => 8395.83],
                    ['tier' => 51, 'min_active_customers' => 8, 'min_referred_volume' => 2000, 'min_qvv' => 9000000,   'daily_reward' => 8731.67],
                    ['tier' => 52, 'min_active_customers' => 8, 'min_referred_volume' => 2000, 'min_qvv' => 9500000,   'daily_reward' => 9067.50],
                    ['tier' => 53, 'min_active_customers' => 8, 'min_referred_volume' => 2100, 'min_qvv' => 10000000,  'daily_reward' => 9403.33],
                    ['tier' => 54, 'min_active_customers' => 8, 'min_referred_volume' => 2100, 'min_qvv' => 10500000,  'daily_reward' => 9739.17],
                    ['tier' => 55, 'min_active_customers' => 8, 'min_referred_volume' => 2100, 'min_qvv' => 11000000,  'daily_reward' => 10075.00],
                    ['tier' => 56, 'min_active_customers' => 8, 'min_referred_volume' => 2200, 'min_qvv' => 11500000,  'daily_reward' => 10410.83],
                    ['tier' => 57, 'min_active_customers' => 8, 'min_referred_volume' => 2200, 'min_qvv' => 12000000,  'daily_reward' => 10746.67],
                    ['tier' => 58, 'min_active_customers' => 8, 'min_referred_volume' => 2200, 'min_qvv' => 12500000,  'daily_reward' => 11082.50],
                    ['tier' => 59, 'min_active_customers' => 8, 'min_referred_volume' => 2300, 'min_qvv' => 13000000,  'daily_reward' => 11418.33],
                    ['tier' => 60, 'min_active_customers' => 8, 'min_referred_volume' => 2300, 'min_qvv' => 13500000,  'daily_reward' => 11754.17],
                    ['tier' => 61, 'min_active_customers' => 8, 'min_referred_volume' => 2300, 'min_qvv' => 14000000,  'daily_reward' => 12090.00],
                    ['tier' => 62, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 14500000,  'daily_reward' => 12425.83],
                    ['tier' => 63, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 15000000,  'daily_reward' => 12761.67],
                    ['tier' => 64, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 15500000,  'daily_reward' => 13097.50],
                    ['tier' => 65, 'min_active_customers' => 8, 'min_referred_volume' => 2400, 'min_qvv' => 16000000,  'daily_reward' => 13433.33],
                    ['tier' => 66, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 16500000,  'daily_reward' => 13769.17],
                    ['tier' => 67, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 17000000,  'daily_reward' => 14105.00],
                    ['tier' => 68, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 17500000,  'daily_reward' => 14440.83],
                    ['tier' => 69, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 18000000,  'daily_reward' => 14776.67],
                    ['tier' => 70, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 18500000,  'daily_reward' => 15112.50],
                    ['tier' => 71, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 19000000,  'daily_reward' => 15448.33],
                    ['tier' => 72, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 19500000,  'daily_reward' => 15784.17],
                    ['tier' => 73, 'min_active_customers' => 8, 'min_referred_volume' => 2500, 'min_qvv' => 20000000,  'daily_reward' => 16120.00],
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
                    'description' => 'If rolling 30-day viral commissions exceed 15% of rolling 30-day company volume, reduce all viral payouts for that day by the overage percentage',
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
