<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app()->bound('current_company_id')
            ? app('current_company_id')
            : null;

        if ($companyId !== null) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
