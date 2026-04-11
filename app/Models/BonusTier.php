<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusTier extends Model
{
    use HasFactory;

    protected $table = 'bonus_type_tiers';

    protected $fillable = [
        'bonus_type_id',
        'level',
        'label',
        'qualifier_value',
        'qualifier_type',
        'rate',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'qualifier_value' => 'decimal:2',
            'rate' => 'decimal:4',
            'amount' => 'decimal:4',
        ];
    }

    public function bonusType(): BelongsTo
    {
        return $this->belongsTo(BonusType::class);
    }
}
