<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[ScopedBy(CompanyScope::class)]
class BonusLedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'commission_run_id',
        'user_id',
        'bonus_type_id',
        'amount',
        'tier_achieved',
        'qualification_snapshot',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'tier_achieved' => 'integer',
            'qualification_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Set to true only within CommissionRunOrchestrator::deleteExistingRun() to allow idempotent re-runs. */
    public static bool $allowDeletion = false;

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('BonusLedgerEntry records are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        if (!static::$allowDeletion) {
            throw new LogicException('BonusLedgerEntry is immutable. Use CommissionRunOrchestrator for re-runs.');
        }

        return parent::delete();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function commissionRun(): BelongsTo
    {
        return $this->belongsTo(CommissionRun::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bonusType(): BelongsTo
    {
        return $this->belongsTo(BonusType::class);
    }
}
