<?php

namespace App\Models;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy(CompanyScope::class)]
class SimulationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'compensation_plan_id',
        'name',
        'config',
        'results',
        'projection_days',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'results' => 'array',
            'projection_days' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function compensationPlan(): BelongsTo
    {
        return $this->belongsTo(CompensationPlan::class);
    }
}
