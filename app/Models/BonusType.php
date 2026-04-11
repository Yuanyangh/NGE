<?php

namespace App\Models;

use App\Enums\BonusTypeEnum;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy(CompanyScope::class)]
class BonusType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'compensation_plan_id',
        'type',
        'name',
        'description',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'type' => BonusTypeEnum::class,
            'is_active' => 'boolean',
            'priority' => 'integer',
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

    public function configs(): HasMany
    {
        return $this->hasMany(BonusTypeConfig::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(BonusTier::class)->orderBy('level');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(BonusLedgerEntry::class);
    }
}
