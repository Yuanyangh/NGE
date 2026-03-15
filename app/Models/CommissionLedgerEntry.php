<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ScopedBy(CompanyScope::class)]
class CommissionLedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'commission_run_id',
        'user_id',
        'type',
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

    public function walletMovement(): HasOne
    {
        return $this->hasOne(WalletMovement::class, 'reference_id')
            ->where('reference_type', 'commission_ledger_entry');
    }
}
