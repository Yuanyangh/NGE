<?php

namespace Tests\Feature\Commission;

use App\Models\BonusLedgerEntry;
use App\Models\BonusTier;
use App\Models\BonusType;
use App\Models\BonusTypeConfig;
use App\Models\CommissionRun;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Intermediate base class for Bonus Type Engine tests.
 *
 * Extends CommissionTestCase so all genealogy/transaction/qualification helpers
 * are available. Adds bonus-specific helpers for creating bonus types, configs,
 * tiers, and asserting amounts with bcmath precision.
 *
 * Relies on RefreshDatabase (inherited) + existing bonus migrations to create
 * bonus_types, bonus_type_configs, bonus_type_tiers, bonus_ledger_entries,
 * and company_settings tables automatically.
 */
abstract class BonusTestCase extends CommissionTestCase
{
    /**
     * Create a BonusType row and return the model.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function createBonusType(array $overrides = []): BonusType
    {
        $defaults = [
            'company_id'           => $this->company->id,
            'compensation_plan_id' => $this->plan->id,
            'type'                 => 'matching',
            'name'                 => 'Test Bonus',
            'description'          => null,
            'is_active'            => true,
            'priority'             => 0,
        ];

        $id = DB::table('bonus_types')->insertGetId(
            array_merge($defaults, $overrides, [
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );

        return BonusType::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
    }

    /**
     * Create a BonusTypeConfig (key/value pair) for a given BonusType.
     *
     * @param  BonusType  $bonusType
     * @param  string     $key
     * @param  string     $value
     */
    protected function createBonusConfig(BonusType $bonusType, string $key, string $value): BonusTypeConfig
    {
        $id = DB::table('bonus_type_configs')->insertGetId([
            'bonus_type_id' => $bonusType->id,
            'key'           => $key,
            'value'         => $value,
        ]);

        return BonusTypeConfig::findOrFail($id);
    }

    /**
     * Create a BonusTier row and return the model.
     *
     * @param  BonusType              $bonusType
     * @param  array<string, mixed>   $overrides
     */
    protected function createBonusTier(BonusType $bonusType, array $overrides = []): BonusTier
    {
        $defaults = [
            'bonus_type_id'  => $bonusType->id,
            'level'          => 1,
            'label'          => null,
            'qualifier_value'=> null,
            'qualifier_type' => null,
            'rate'           => null,
            'amount'         => null,
        ];

        $id = DB::table('bonus_type_tiers')->insertGetId(
            array_merge($defaults, $overrides, [
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );

        return BonusTier::findOrFail($id);
    }

    /**
     * Build a plain-array commission result suitable for passing to calculators
     * and the BonusOrchestrator. Mirrors the shape written to commission_ledger_entries.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function makeCommissionResult(array $overrides = []): array
    {
        return array_merge([
            'user_id'                  => 0,
            'affiliate_commission'     => '0',
            'affiliate_tier_index'     => null,
            'affiliate_tier_rate'      => null,
            'viral_commission'         => '0',
            'viral_tier'               => null,
            'qualification_snapshot'   => [],
        ], $overrides);
    }

    /**
     * Create a CommissionRun record scoped to the test company.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function createCommissionRun(array $overrides = []): CommissionRun
    {
        $defaults = [
            'company_id'             => $this->company->id,
            'compensation_plan_id'   => $this->plan->id,
            'run_date'               => $this->today->toDateString(),
            'status'                 => 'running',
            'total_affiliate_commission' => '0',
            'total_viral_commission' => '0',
            'total_bonus_amount'     => '0',
            'total_company_volume'   => '0',
            'viral_cap_triggered'    => false,
            'started_at'             => now(),
        ];

        return CommissionRun::create(array_merge($defaults, $overrides));
    }

    /**
     * Assert a bonus amount equals the expected value using bcmath precision.
     * Uses 4 decimal places, matching the DECIMAL(12,4) column definition.
     *
     * @param  string  $expected  e.g. '150.0000'
     * @param  string  $actual
     * @param  string  $message
     */
    protected function assertBonusEquals(string $expected, string $actual, string $message = ''): void
    {
        $this->assertSame(
            0,
            bccomp($expected, $actual, 4),
            $message ?: "Expected bonus {$expected}, got {$actual}"
        );
    }

    /**
     * Retrieve all BonusLedgerEntry rows for a user inside a given run,
     * bypassing the CompanyScope for test convenience.
     *
     * @return \Illuminate\Support\Collection<int, BonusLedgerEntry>
     */
    protected function bonusEntriesFor(int $userId, CommissionRun $run): \Illuminate\Support\Collection
    {
        return BonusLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $userId)
            ->where('commission_run_id', $run->id)
            ->get();
    }

    /**
     * Sum the amounts of a collection of BonusLedgerEntry models using bcmath.
     *
     * @param  \Illuminate\Support\Collection<int, BonusLedgerEntry>  $entries
     */
    protected function sumBonusEntries(\Illuminate\Support\Collection $entries): string
    {
        return $entries->reduce(
            fn (string $carry, BonusLedgerEntry $entry) => bcadd($carry, (string) $entry->amount, 4),
            '0'
        );
    }
}
