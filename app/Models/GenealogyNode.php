<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[ScopedBy(CompanyScope::class)]
class GenealogyNode extends Model
{
    use HasFactory, HasRecursiveRelationships;

    protected $fillable = [
        'company_id',
        'user_id',
        'sponsor_id',
        'position',
        'tree_depth',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'tree_depth' => 'integer',
        ];
    }

    public function getParentKeyName(): string
    {
        return 'sponsor_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sponsor_id');
    }
}
