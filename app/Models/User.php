<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[ScopedBy(CompanyScope::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'enrolled_at',
        'last_order_at',
        'last_reward_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'enrolled_at' => 'datetime',
            'last_order_at' => 'datetime',
            'last_reward_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function genealogyNode(): HasOne
    {
        return $this->hasOne(GenealogyNode::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function referredTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'referred_by_user_id');
    }

    public function commissionLedgerEntries(): HasMany
    {
        return $this->hasMany(CommissionLedgerEntry::class);
    }

    public function bonusLedgerEntries(): HasMany
    {
        return $this->hasMany(BonusLedgerEntry::class);
    }

    public function walletAccount(): HasOne
    {
        return $this->hasOne(WalletAccount::class);
    }

    public function isAffiliate(): bool
    {
        return $this->role === 'affiliate';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

}
