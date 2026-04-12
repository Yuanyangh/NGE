<?php

namespace App\Models;

use App\Events\TransactionRefunded;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy(CompanyScope::class)]
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'referred_by_user_id',
        'type',
        'amount',
        'xp',
        'currency',
        'status',
        'qualifies_for_commission',
        'transaction_date',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'xp' => 'decimal:2',
            'qualifies_for_commission' => 'boolean',
            'transaction_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * Create a refund transaction and fire the TransactionRefunded event.
     *
     * @param Transaction $originalTransaction The original transaction being refunded
     * @param string $refundAmount The refund monetary amount
     * @param string $refundXp The refund XP amount (may be partial)
     * @param string|null $reference Optional reference override; defaults to original transaction ID
     */
    public static function createRefund(
        Transaction $originalTransaction,
        string $refundAmount,
        string $refundXp,
        ?string $reference = null,
    ): static {
        // Validate amounts are positive
        if (bccomp($refundAmount, '0', 2) <= 0 || bccomp($refundXp, '0', 2) <= 0) {
            throw new \InvalidArgumentException('Refund amount and XP must be positive');
        }

        // Validate refund doesn't exceed original
        if (bccomp($refundAmount, $originalTransaction->amount, 2) > 0) {
            throw new \InvalidArgumentException('Refund amount cannot exceed original transaction amount');
        }
        if (bccomp($refundXp, $originalTransaction->xp, 2) > 0) {
            throw new \InvalidArgumentException('Refund XP cannot exceed original transaction XP');
        }

        $refund = static::create([
            'company_id' => $originalTransaction->company_id,
            'user_id' => $originalTransaction->user_id,
            'referred_by_user_id' => $originalTransaction->referred_by_user_id,
            'type' => 'refund',
            'amount' => $refundAmount,
            'xp' => $refundXp,
            'currency' => $originalTransaction->currency,
            'status' => 'confirmed',
            'qualifies_for_commission' => false,
            'transaction_date' => now()->toDateString(),
            'reference' => $reference ?? (string) $originalTransaction->id,
        ]);

        TransactionRefunded::dispatch($refund);

        return $refund;
    }
}
