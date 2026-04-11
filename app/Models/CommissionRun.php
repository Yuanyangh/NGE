<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy(CompanyScope::class)]
class CommissionRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'compensation_plan_id',
        'run_date',
        'status',
        'total_affiliate_commission',
        'total_viral_commission',
        'total_company_volume',
        'viral_cap_triggered',
        'viral_cap_reduction_pct',
        'started_at',
        'completed_at',
        'error_message',
        'total_bonus_amount',
    ];

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'total_affiliate_commission' => 'decimal:2',
            'total_viral_commission' => 'decimal:2',
            'total_bonus_amount' => 'decimal:2',
            'total_company_volume' => 'decimal:2',
            'viral_cap_triggered' => 'boolean',
            'viral_cap_reduction_pct' => 'decimal:4',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function compensationPlan(): BelongsTo
    {
        return $this->belongsTo(CompensationPlan::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CommissionLedgerEntry::class);
    }

    public function bonusLedgerEntries(): HasMany
    {
        return $this->hasMany(BonusLedgerEntry::class);
    }
}
