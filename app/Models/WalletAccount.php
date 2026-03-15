<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy(CompanyScope::class)]
class WalletAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'currency',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(WalletMovement::class);
    }

    public function balance(): string
    {
        return $this->movements()
            ->where('status', '!=', 'reversed')
            ->sum('amount');
    }
}
