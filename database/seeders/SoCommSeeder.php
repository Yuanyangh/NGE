<?php

namespace Database\Seeders;

use App\Enums\BonusTypeEnum;
use App\Models\BonusLedgerEntry;
use App\Models\BonusTier;
use App\Models\BonusType;
use App\Models\BonusTypeConfig;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionRun;
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

class SoCommSeeder extends Seeder
{
    private Company $company;
    private CompensationPlan $plan;
    private array $users = [];
    private array $nodes = [];

    public function run(): void
    {
        $this->company = Company::create([
            'name' => 'SoComm',
            'slug' => 'socomm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->plan = CompensationPlan::create([
            'company_id' => $this->company->id,
            'name' => 'SoComm Affiliate Rewards Program',
            'version' => '1.0',
            'config' => $this->soCommConfig(),
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
    }

    private function createUsers(): void
    {
        $userData = [
            // Admin
            ['name' => 'Admin User', 'email' => 'admin@socomm.test', 'role' => 'admin', 'months_ago' => 12],

            // ===== TOP-LEVEL AFFILIATES =====
            ['name' => 'Alice Johnson', 'email' => 'alice@socomm.test', 'role' => 'affiliate', 'months_ago' => 10],
            ['name' => 'Bob Smith', 'email' => 'bob@socomm.test', 'role' => 'affiliate', 'months_ago' => 8],

            // ===== ALICE'S NETWORK (3 legs, deep tree) =====
            // Leg 1: Charlie's branch (strong leg)
            ['name' => 'Charlie Brown', 'email' => 'charlie@socomm.test', 'role' => 'affiliate', 'months_ago' => 9],
            ['name' => 'Frank Miller', 'email' => 'frank@socomm.test', 'role' => 'affiliate', 'months_ago' => 7],
            ['name' => 'Grace Lee', 'email' => 'grace@socomm.test', 'role' => 'customer', 'months_ago' => 6],
            ['name' => 'Hank Davis', 'email' => 'hank@socomm.test', 'role' => 'customer', 'months_ago' => 5],
            ['name' => 'Ivy Chen', 'email' => 'ivy@socomm.test', 'role' => 'customer', 'months_ago' => 5],
            ['name' => 'Tina Walker', 'email' => 'tina@socomm.test', 'role' => 'customer', 'months_ago' => 4],
            // Frank's sub-affiliates (depth 4)
            ['name' => 'Nancy Green', 'email' => 'nancy@socomm.test', 'role' => 'affiliate', 'months_ago' => 4],
            ['name' => 'Oscar White', 'email' => 'oscar@socomm.test', 'role' => 'customer', 'months_ago' => 3],
            ['name' => 'Pam Red', 'email' => 'pam@socomm.test', 'role' => 'customer', 'months_ago' => 3],
            // Nancy's downline (depth 5)
            ['name' => 'Will Turner', 'email' => 'will@socomm.test', 'role' => 'customer', 'months_ago' => 2],
            ['name' => 'Xena Warrior', 'email' => 'xena@socomm.test', 'role' => 'customer', 'months_ago' => 2],

            // Leg 2: Diana's branch (moderate leg)
            ['name' => 'Diana Prince', 'email' => 'diana@socomm.test', 'role' => 'affiliate', 'months_ago' => 8],
            ['name' => 'Jack Taylor', 'email' => 'jack@socomm.test', 'role' => 'affiliate', 'months_ago' => 6],
            ['name' => 'Karen White', 'email' => 'karen@socomm.test', 'role' => 'customer', 'months_ago' => 5],
            ['name' => 'Uma Hall', 'email' => 'uma@socomm.test', 'role' => 'customer', 'months_ago' => 4],
            // Jack's customers
            ['name' => 'Yuri Gagarin', 'email' => 'yuri@socomm.test', 'role' => 'customer', 'months_ago' => 3],
            ['name' => 'Zoe Quinn', 'email' => 'zoe@socomm.test', 'role' => 'customer', 'months_ago' => 2],

            // Leg 3: Eve's branch (weaker leg)
            ['name' => 'Eve Wilson', 'email' => 'eve@socomm.test', 'role' => 'affiliate', 'months_ago' => 7],
            ['name' => 'Leo Martinez', 'email' => 'leo@socomm.test', 'role' => 'customer', 'months_ago' => 5],
            ['name' => 'Victor King', 'email' => 'victor@socomm.test', 'role' => 'customer', 'months_ago' => 4],

            // Alice's direct customers (not part of any leg subtree)
            ['name' => 'Quinn Robinson', 'email' => 'quinn@socomm.test', 'role' => 'customer', 'months_ago' => 6],
            ['name' => 'Rachel Clark', 'email' => 'rachel@socomm.test', 'role' => 'customer', 'months_ago' => 5],
            ['name' => 'Stella Nova', 'email' => 'stella@socomm.test', 'role' => 'customer', 'months_ago' => 3],
            ['name' => 'Tom Hardy', 'email' => 'tom@socomm.test', 'role' => 'customer', 'months_ago' => 2],

            // ===== BOB'S NETWORK (2 legs) =====
            // Leg 1: Mia's branch
            ['name' => 'Mia Anderson', 'email' => 'mia@socomm.test', 'role' => 'affiliate', 'months_ago' => 7],
            ['name' => 'Olivia Jackson', 'email' => 'olivia@socomm.test', 'role' => 'customer', 'months_ago' => 5],
            ['name' => 'Peter Harris', 'email' => 'peter@socomm.test', 'role' => 'customer', 'months_ago' => 4],
            // Mia's sub-affiliate
            ['name' => 'Rita Moreno', 'email' => 'rita@socomm.test', 'role' => 'affiliate', 'months_ago' => 3],
            ['name' => 'Saul Goodman', 'email' => 'saul@socomm.test', 'role' => 'customer', 'months_ago' => 2],
            ['name' => 'Ursula Major', 'email' => 'ursula@socomm.test', 'role' => 'customer', 'months_ago' => 2],

            // Leg 2: Noah
            ['name' => 'Noah Thomas', 'email' => 'noah@socomm.test', 'role' => 'customer', 'months_ago' => 6],
            ['name' => 'Sam Lewis', 'email' => 'sam@socomm.test', 'role' => 'customer', 'months_ago' => 5],

            // ===== ADDITIONAL AFFILIATES FOR TREE DEPTH =====
            ['name' => 'Dave Grohl', 'email' => 'dave@socomm.test', 'role' => 'affiliate', 'months_ago' => 6],
            ['name' => 'Fiona Apple', 'email' => 'fiona@socomm.test', 'role' => 'customer', 'months_ago' => 4],
            ['name' => 'George Lucas', 'email' => 'george@socomm.test', 'role' => 'customer', 'months_ago' => 3],
            ['name' => 'Hannah Montana', 'email' => 'hannah@socomm.test', 'role' => 'customer', 'months_ago' => 2],
            ['name' => 'Ivan Drago', 'email' => 'ivan@socomm.test', 'role' => 'customer', 'months_ago' => 1],
        ];

        foreach ($userData as $data) {
            $this->users[$data['email']] = User::create([
                'company_id' => $this->company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => 'password',
                'role' => $data['role'],
                'status' => 'active',
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
                'user_id' => $this->users[$email]->id,
                'sponsor_id' => $sponsor?->id,
                'tree_depth' => $depth,
            ]);
        };

        // Top level
        $make('admin@socomm.test', null, 0);
        $make('alice@socomm.test', null, 0);
        $make('bob@socomm.test', null, 0);

        // Alice's 3 legs
        $make('charlie@socomm.test', 'alice@socomm.test', 1);
        $make('diana@socomm.test', 'alice@socomm.test', 1);
        $make('eve@socomm.test', 'alice@socomm.test', 1);
        // Alice's direct customers
        $make('quinn@socomm.test', 'alice@socomm.test', 1);
        $make('rachel@socomm.test', 'alice@socomm.test', 1);
        $make('stella@socomm.test', 'alice@socomm.test', 1);
        $make('tom@socomm.test', 'alice@socomm.test', 1);

        // Charlie's downline (Alice Leg 1)
        $make('frank@socomm.test', 'charlie@socomm.test', 2);
        $make('grace@socomm.test', 'charlie@socomm.test', 2);
        $make('tina@socomm.test', 'charlie@socomm.test', 2);

        // Frank's downline (depth 3)
        $make('hank@socomm.test', 'frank@socomm.test', 3);
        $make('ivy@socomm.test', 'frank@socomm.test', 3);
        $make('nancy@socomm.test', 'frank@socomm.test', 3);
        $make('oscar@socomm.test', 'frank@socomm.test', 3);
        $make('pam@socomm.test', 'frank@socomm.test', 3);

        // Nancy's downline (depth 4)
        $make('will@socomm.test', 'nancy@socomm.test', 4);
        $make('xena@socomm.test', 'nancy@socomm.test', 4);

        // Diana's downline (Alice Leg 2)
        $make('jack@socomm.test', 'diana@socomm.test', 2);
        $make('karen@socomm.test', 'diana@socomm.test', 2);
        $make('uma@socomm.test', 'diana@socomm.test', 2);

        // Jack's customers
        $make('yuri@socomm.test', 'jack@socomm.test', 3);
        $make('zoe@socomm.test', 'jack@socomm.test', 3);

        // Eve's downline (Alice Leg 3)
        $make('leo@socomm.test', 'eve@socomm.test', 2);
        $make('victor@socomm.test', 'eve@socomm.test', 2);

        // Bob's legs
        $make('mia@socomm.test', 'bob@socomm.test', 1);
        $make('noah@socomm.test', 'bob@socomm.test', 1);
        $make('sam@socomm.test', 'bob@socomm.test', 1);

        // Mia's downline
        $make('olivia@socomm.test', 'mia@socomm.test', 2);
        $make('peter@socomm.test', 'mia@socomm.test', 2);
        $make('rita@socomm.test', 'mia@socomm.test', 2);

        // Rita's customers
        $make('saul@socomm.test', 'rita@socomm.test', 3);
        $make('ursula@socomm.test', 'rita@socomm.test', 3);

        // Dave under Jack (extends Diana's leg deeper)
        $make('dave@socomm.test', 'jack@socomm.test', 3);
        $make('fiona@socomm.test', 'dave@socomm.test', 4);
        $make('george@socomm.test', 'dave@socomm.test', 4);
        $make('hannah@socomm.test', 'dave@socomm.test', 4);
        $make('ivan@socomm.test', 'dave@socomm.test', 4);
    }

    private function createTransactions(): void
    {
        $today = Carbon::today();

        // Build sponsor map from tree for referred_by_user_id
        $sponsorMap = [
            'charlie@socomm.test' => 'alice@socomm.test',
            'diana@socomm.test'   => 'alice@socomm.test',
            'eve@socomm.test'     => 'alice@socomm.test',
            'quinn@socomm.test'   => 'alice@socomm.test',
            'rachel@socomm.test'  => 'alice@socomm.test',
            'stella@socomm.test'  => 'alice@socomm.test',
            'tom@socomm.test'     => 'alice@socomm.test',
            'frank@socomm.test'   => 'charlie@socomm.test',
            'grace@socomm.test'   => 'charlie@socomm.test',
            'tina@socomm.test'    => 'charlie@socomm.test',
            'hank@socomm.test'    => 'frank@socomm.test',
            'ivy@socomm.test'     => 'frank@socomm.test',
            'nancy@socomm.test'   => 'frank@socomm.test',
            'oscar@socomm.test'   => 'frank@socomm.test',
            'pam@socomm.test'     => 'frank@socomm.test',
            'will@socomm.test'    => 'nancy@socomm.test',
            'xena@socomm.test'    => 'nancy@socomm.test',
            'jack@socomm.test'    => 'diana@socomm.test',
            'karen@socomm.test'   => 'diana@socomm.test',
            'uma@socomm.test'     => 'diana@socomm.test',
            'yuri@socomm.test'    => 'jack@socomm.test',
            'zoe@socomm.test'     => 'jack@socomm.test',
            'leo@socomm.test'     => 'eve@socomm.test',
            'victor@socomm.test'  => 'eve@socomm.test',
            'mia@socomm.test'     => 'bob@socomm.test',
            'noah@socomm.test'    => 'bob@socomm.test',
            'sam@socomm.test'     => 'bob@socomm.test',
            'olivia@socomm.test'  => 'mia@socomm.test',
            'peter@socomm.test'   => 'mia@socomm.test',
            'rita@socomm.test'    => 'mia@socomm.test',
            'saul@socomm.test'    => 'rita@socomm.test',
            'ursula@socomm.test'  => 'rita@socomm.test',
            'dave@socomm.test'    => 'jack@socomm.test',
            'fiona@socomm.test'   => 'dave@socomm.test',
            'george@socomm.test'  => 'dave@socomm.test',
            'hannah@socomm.test'  => 'dave@socomm.test',
            'ivan@socomm.test'    => 'dave@socomm.test',
        ];

        $txn = function (string $email, int $daysAgo, float $xp, string $type = 'purchase') use ($sponsorMap) {
            $buyer = $this->users[$email];
            $referrer = isset($sponsorMap[$email]) ? $this->users[$sponsorMap[$email]] : null;
            Transaction::create([
                'company_id' => $this->company->id,
                'user_id' => $buyer->id,
                'referred_by_user_id' => $referrer?->id,
                'type' => $type,
                'amount' => $xp,
                'xp' => $xp,
                'currency' => 'USD',
                'status' => 'confirmed',
                'qualifies_for_commission' => true,
                'transaction_date' => Carbon::today()->subDays($daysAgo),
                'reference' => 'SEED-' . $buyer->id . '-' . $daysAgo . '-' . $type,
            ]);
        };

        // ===== ALICE'S REFERRED CUSTOMERS - Heavy volume =====
        // Charlie (Leg 1 root)
        $txn('charlie@socomm.test', 0, 50);
        $txn('charlie@socomm.test', 5, 65);
        $txn('charlie@socomm.test', 10, 80);
        $txn('charlie@socomm.test', 20, 60);
        $txn('charlie@socomm.test', 28, 45);

        // Diana (Leg 2 root)
        $txn('diana@socomm.test', 1, 40);
        $txn('diana@socomm.test', 8, 55);
        $txn('diana@socomm.test', 15, 70);
        $txn('diana@socomm.test', 22, 35);

        // Eve (Leg 3 root)
        $txn('eve@socomm.test', 3, 30);
        $txn('eve@socomm.test', 12, 45);
        $txn('eve@socomm.test', 25, 50);

        // Quinn
        $txn('quinn@socomm.test', 2, 25);
        $txn('quinn@socomm.test', 12, 35);
        $txn('quinn@socomm.test', 19, 30);

        // Rachel
        $txn('rachel@socomm.test', 4, 45);
        $txn('rachel@socomm.test', 14, 40);

        // Stella
        $txn('stella@socomm.test', 1, 55);
        $txn('stella@socomm.test', 9, 30);

        // Tom
        $txn('tom@socomm.test', 0, 35);
        $txn('tom@socomm.test', 7, 28);

        // ===== LEG 1 DEEP VOLUME (Charlie's subtree) =====
        // Frank (referred by Charlie)
        $txn('frank@socomm.test', 0, 100);
        $txn('frank@socomm.test', 7, 85);
        $txn('frank@socomm.test', 14, 75);
        $txn('frank@socomm.test', 21, 90);

        // Grace (referred by Charlie)
        $txn('grace@socomm.test', 2, 30);
        $txn('grace@socomm.test', 11, 25);

        // Tina (referred by Charlie)
        $txn('tina@socomm.test', 5, 25);
        $txn('tina@socomm.test', 18, 35);

        // Hank (referred by Frank)
        $txn('hank@socomm.test', 1, 50);
        $txn('hank@socomm.test', 9, 45);
        $txn('hank@socomm.test', 18, 40);

        // Ivy (referred by Frank)
        $txn('ivy@socomm.test', 3, 35);
        $txn('ivy@socomm.test', 15, 30);

        // Nancy (referred by Frank) - sub-affiliate with own team
        $txn('nancy@socomm.test', 2, 60);
        $txn('nancy@socomm.test', 10, 50);

        // Oscar (referred by Frank)
        $txn('oscar@socomm.test', 4, 40);
        $txn('oscar@socomm.test', 16, 30);

        // Pam (referred by Frank)
        $txn('pam@socomm.test', 6, 25);

        // Will (referred by Nancy, depth 5)
        $txn('will@socomm.test', 3, 45);
        $txn('will@socomm.test', 13, 35);

        // Xena (referred by Nancy, depth 5)
        $txn('xena@socomm.test', 5, 30);
        $txn('xena@socomm.test', 17, 25);

        // ===== LEG 2 VOLUME (Diana's subtree) =====
        // Jack (referred by Diana)
        $txn('jack@socomm.test', 1, 60);
        $txn('jack@socomm.test', 10, 55);
        $txn('jack@socomm.test', 22, 45);

        // Karen (referred by Diana)
        $txn('karen@socomm.test', 3, 30);
        $txn('karen@socomm.test', 14, 25);

        // Uma (referred by Diana)
        $txn('uma@socomm.test', 6, 40);
        $txn('uma@socomm.test', 20, 35);

        // Yuri (referred by Jack)
        $txn('yuri@socomm.test', 2, 35);
        $txn('yuri@socomm.test', 11, 30);

        // Zoe (referred by Jack)
        $txn('zoe@socomm.test', 4, 25);
        $txn('zoe@socomm.test', 15, 20);

        // Dave (referred by Jack, sub-affiliate)
        $txn('dave@socomm.test', 1, 70);
        $txn('dave@socomm.test', 8, 55);
        $txn('dave@socomm.test', 16, 45);

        // Fiona (referred by Dave)
        $txn('fiona@socomm.test', 3, 30);
        $txn('fiona@socomm.test', 12, 25);

        // George (referred by Dave)
        $txn('george@socomm.test', 5, 35);

        // Hannah (referred by Dave)
        $txn('hannah@socomm.test', 7, 28);

        // Ivan (referred by Dave)
        $txn('ivan@socomm.test', 2, 40);

        // ===== LEG 3 VOLUME (Eve's subtree) =====
        // Leo (referred by Eve)
        $txn('leo@socomm.test', 2, 25);
        $txn('leo@socomm.test', 13, 20);

        // Victor (referred by Eve)
        $txn('victor@socomm.test', 8, 22);

        // ===== BOB'S NETWORK =====
        $txn('mia@socomm.test', 0, 60);
        $txn('mia@socomm.test', 6, 55);
        $txn('mia@socomm.test', 16, 40);

        $txn('noah@socomm.test', 2, 30);
        $txn('noah@socomm.test', 14, 25);

        $txn('sam@socomm.test', 4, 25);
        $txn('sam@socomm.test', 11, 35);

        // Mia's referrals
        $txn('olivia@socomm.test', 1, 35);
        $txn('olivia@socomm.test', 10, 30);

        $txn('peter@socomm.test', 3, 40);
        $txn('peter@socomm.test', 15, 35);

        $txn('rita@socomm.test', 2, 50);
        $txn('rita@socomm.test', 9, 45);

        // Rita's referrals
        $txn('saul@socomm.test', 4, 30);
        $txn('saul@socomm.test', 12, 25);

        $txn('ursula@socomm.test', 6, 35);
        $txn('ursula@socomm.test', 18, 28);

        // ===== SMARTSHIP ORDERS =====
        $txn('charlie@socomm.test', 0, 30, 'smartship');
        $txn('diana@socomm.test', 0, 30, 'smartship');
        $txn('frank@socomm.test', 0, 30, 'smartship');
        $txn('mia@socomm.test', 0, 30, 'smartship');
        $txn('nancy@socomm.test', 0, 30, 'smartship');
        $txn('jack@socomm.test', 0, 30, 'smartship');
        $txn('dave@socomm.test', 0, 30, 'smartship');
        $txn('rita@socomm.test', 0, 30, 'smartship');
        $txn('eve@socomm.test', 0, 25, 'smartship');

        // ===== SELF-PURCHASES (no referrer for top-level) =====
        $txn('alice@socomm.test', 0, 50);
        $txn('bob@socomm.test', 0, 40);

        // ===== EDGE CASES =====
        // Old transaction (outside 30-day window — should NOT count)
        Transaction::create([
            'company_id' => $this->company->id,
            'user_id' => $this->users['charlie@socomm.test']->id,
            'referred_by_user_id' => $this->users['alice@socomm.test']->id,
            'type' => 'purchase',
            'amount' => 200,
            'xp' => 200,
            'currency' => 'USD',
            'status' => 'confirmed',
            'qualifies_for_commission' => true,
            'transaction_date' => Carbon::today()->subDays(45),
            'reference' => 'SEED-OLD-1',
        ]);

        // Reversed transaction (should NOT count)
        Transaction::create([
            'company_id' => $this->company->id,
            'user_id' => $this->users['quinn@socomm.test']->id,
            'referred_by_user_id' => $this->users['alice@socomm.test']->id,
            'type' => 'purchase',
            'amount' => 100,
            'xp' => 100,
            'currency' => 'USD',
            'status' => 'reversed',
            'qualifies_for_commission' => true,
            'transaction_date' => Carbon::today()->subDays(5),
            'reference' => 'SEED-REVERSED-1',
        ]);

        // Sub-threshold transaction (< 20 XP, should NOT make customer "active")
        Transaction::create([
            'company_id' => $this->company->id,
            'user_id' => $this->users['victor@socomm.test']->id,
            'referred_by_user_id' => $this->users['eve@socomm.test']->id,
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

    private function createWalletAccounts(): void
    {
        foreach ($this->users as $user) {
            if ($user->role === 'affiliate') {
                WalletAccount::create([
                    'company_id' => $this->company->id,
                    'user_id' => $user->id,
                    'currency' => 'USD',
                ]);
            }
        }
    }

    private function createCommissionHistory(): void
    {
        // Create historical commission runs and ledger entries so
        // the dashboard has data to display
        $affiliates = collect($this->users)->filter(fn (User $u) => $u->role === 'affiliate');

        // Generate 14 days of commission history
        for ($daysAgo = 14; $daysAgo >= 1; $daysAgo--) {
            $runDate = Carbon::today()->subDays($daysAgo);

            $run = CommissionRun::create([
                'company_id' => $this->company->id,
                'compensation_plan_id' => $this->plan->id,
                'run_date' => $runDate,
                'status' => 'completed',
                'total_affiliate_commission' => 0,
                'total_viral_commission' => 0,
                'total_company_volume' => 0,
                'viral_cap_triggered' => false,
                'started_at' => $runDate->copy()->setTime(2, 0, 0),
                'completed_at' => $runDate->copy()->setTime(2, 0, 5),
            ]);

            $totalAff = 0;
            $totalViral = 0;

            foreach ($affiliates as $affiliate) {
                // Affiliate commission — varies by affiliate size
                $baseAffCommission = match(true) {
                    in_array($affiliate->email, ['alice@socomm.test']) => rand(800, 1500) / 100,
                    in_array($affiliate->email, ['bob@socomm.test', 'charlie@socomm.test']) => rand(400, 900) / 100,
                    in_array($affiliate->email, ['frank@socomm.test', 'diana@socomm.test', 'mia@socomm.test']) => rand(200, 600) / 100,
                    default => rand(50, 300) / 100,
                };

                if ($baseAffCommission > 0.50) {
                    CommissionLedgerEntry::create([
                        'company_id' => $this->company->id,
                        'commission_run_id' => $run->id,
                        'user_id' => $affiliate->id,
                        'type' => 'affiliate_commission',
                        'amount' => number_format($baseAffCommission, 4, '.', ''),
                        'tier_achieved' => rand(1, 6),
                        'qualification_snapshot' => ['seeded' => true],
                        'description' => 'Daily affiliate commission',
                        'created_at' => $runDate,
                    ]);
                    $totalAff += $baseAffCommission;
                }

                // Viral commission — only for affiliates with larger trees
                $viralAmount = match(true) {
                    in_array($affiliate->email, ['alice@socomm.test']) => rand(200, 530) / 100,
                    in_array($affiliate->email, ['bob@socomm.test', 'charlie@socomm.test']) => rand(53, 267) / 100,
                    in_array($affiliate->email, ['frank@socomm.test']) => rand(53, 133) / 100,
                    default => 0,
                };

                if ($viralAmount > 0) {
                    CommissionLedgerEntry::create([
                        'company_id' => $this->company->id,
                        'commission_run_id' => $run->id,
                        'user_id' => $affiliate->id,
                        'type' => 'viral_commission',
                        'amount' => number_format($viralAmount, 4, '.', ''),
                        'tier_achieved' => rand(1, 5),
                        'qualification_snapshot' => ['seeded' => true],
                        'description' => 'Daily viral commission',
                        'created_at' => $runDate,
                    ]);
                    $totalViral += $viralAmount;
                }
            }

            $run->update([
                'total_affiliate_commission' => round($totalAff, 2),
                'total_viral_commission' => round($totalViral, 2),
                'total_company_volume' => rand(1200, 2500),
            ]);
        }

        // Create wallet movements from commission entries
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

            // Older entries are "released", recent ones are "pending"
            foreach ($entries as $entry) {
                $isPending = $entry->created_at && $entry->created_at->gt(Carbon::today()->subDays(7));

                WalletMovement::create([
                    'company_id' => $this->company->id,
                    'wallet_account_id' => $wallet->id,
                    'type' => 'commission_credit',
                    'amount' => $entry->amount,
                    'status' => $isPending ? 'pending' : 'released',
                    'reference_type' => 'commission_ledger_entry',
                    'reference_id' => $entry->id,
                    'description' => 'Commission credit from ' . $entry->type,
                    'effective_at' => $entry->created_at ?? now(),
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

    private function seedBonusTypes(): void
    {
        // 1. Matching Bonus
        $matching = BonusType::create([
            'company_id' => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'type' => BonusTypeEnum::Matching,
            'name' => 'Matching Bonus',
            'description' => 'Earn a percentage of your personally enrolled affiliates\' commissions.',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Matching tiers: gen 1 = 15%, gen 2 = 10%
        BonusTier::create([
            'bonus_type_id' => $matching->id,
            'level' => 1,
            'label' => 'Generation 1',
            'qualifier_value' => null,
            'qualifier_type' => 'generation',
            'rate' => 0.1500,
            'amount' => null,
        ]);

        BonusTier::create([
            'bonus_type_id' => $matching->id,
            'level' => 2,
            'label' => 'Generation 2',
            'qualifier_value' => null,
            'qualifier_type' => 'generation',
            'rate' => 0.1000,
            'amount' => null,
        ]);

        // 2. Fast Start Bonus
        $fastStart = BonusType::create([
            'company_id' => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'type' => BonusTypeEnum::FastStart,
            'name' => 'Fast Start Bonus',
            'description' => 'Enhanced commission rate for new affiliates during their first 30 days.',
            'is_active' => true,
            'priority' => 20,
        ]);

        BonusTypeConfig::create([
            'bonus_type_id' => $fastStart->id,
            'key' => 'duration_days',
            'value' => '30',
        ]);

        BonusTypeConfig::create([
            'bonus_type_id' => $fastStart->id,
            'key' => 'enhanced_rate',
            'value' => '2.00',
        ]);

        BonusTypeConfig::create([
            'bonus_type_id' => $fastStart->id,
            'key' => 'applies_to',
            'value' => 'both',
        ]);

        // 3. Rank Advancement Bonus
        $rankAdvancement = BonusType::create([
            'company_id' => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'type' => BonusTypeEnum::RankAdvancement,
            'name' => 'Rank Advancement Bonus',
            'description' => 'One-time cash bonus awarded when an affiliate first achieves a new rank.',
            'is_active' => true,
            'priority' => 30,
        ]);

        $rankTiers = [
            ['level' => 1, 'label' => 'Bronze',   'qualifier_type' => 'rank', 'qualifier_value' => 1,    'amount' => 100.0000],
            ['level' => 2, 'label' => 'Silver',   'qualifier_type' => 'rank', 'qualifier_value' => 2,    'amount' => 250.0000],
            ['level' => 3, 'label' => 'Gold',     'qualifier_type' => 'rank', 'qualifier_value' => 3,    'amount' => 500.0000],
            ['level' => 4, 'label' => 'Platinum', 'qualifier_type' => 'rank', 'qualifier_value' => 4,    'amount' => 1000.0000],
            ['level' => 5, 'label' => 'Diamond',  'qualifier_type' => 'rank', 'qualifier_value' => 5,    'amount' => 2500.0000],
        ];

        foreach ($rankTiers as $tier) {
            BonusTier::create([
                'bonus_type_id' => $rankAdvancement->id,
                'level' => $tier['level'],
                'label' => $tier['label'],
                'qualifier_value' => $tier['qualifier_value'],
                'qualifier_type' => $tier['qualifier_type'],
                'rate' => null,
                'amount' => $tier['amount'],
            ]);
        }
    }
}
