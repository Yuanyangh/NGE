<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy(CompanyScope::class)]
class CompanySetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getValue(int $companyId, string $key, mixed $default = null): mixed
    {
        $setting = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }
}
