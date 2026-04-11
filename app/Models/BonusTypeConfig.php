<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusTypeConfig extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'bonus_type_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function bonusType(): BelongsTo
    {
        return $this->belongsTo(BonusType::class);
    }
}
