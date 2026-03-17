<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'name',
        'slug',
        'timezone',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function genealogyNodes(): HasMany
    {
        return $this->hasMany(GenealogyNode::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function compensationPlans(): HasMany
    {
        return $this->hasMany(CompensationPlan::class);
    }

    public function activePlan(): HasOne
    {
        return $this->hasOne(CompensationPlan::class)
            ->where('is_active', true)
            ->latest('effective_from');
    }

    public function commissionRuns(): HasMany
    {
        return $this->hasMany(CommissionRun::class);
    }

    public function walletAccounts(): HasMany
    {
        return $this->hasMany(WalletAccount::class);
    }
}
