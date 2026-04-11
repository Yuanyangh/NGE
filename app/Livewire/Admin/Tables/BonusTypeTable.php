<?php

namespace App\Livewire\Admin\Tables;

use App\Models\BonusType;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class BonusTypeTable extends Component
{
    use WithPagination;

    public int $companyId;
    public int $planId;

    public string $filterActive = '';
    public string $sortField = 'priority';
    public string $sortDirection = 'asc';
    public int $perPage = 25;

    public function mount(Company $company, CompensationPlan $plan): void
    {
        $this->companyId = $company->id;
        $this->planId    = $plan->id;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField    = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingFilterActive(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = BonusType::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('compensation_plan_id', $this->planId);

        if ($this->filterActive !== '') {
            $query->where('is_active', $this->filterActive === '1');
        }

        $allowedSortFields = ['priority', 'name', 'type', 'is_active', 'created_at'];
        $sortField = in_array($this->sortField, $allowedSortFields) ? $this->sortField : 'priority';
        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $bonusTypes = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate($this->perPage);

        $company = Company::find($this->companyId);
        $plan    = CompensationPlan::withoutGlobalScope(CompanyScope::class)->find($this->planId);

        return view('livewire.admin.tables.bonus-type-table', [
            'bonusTypes' => $bonusTypes,
            'company'    => $company,
            'plan'       => $plan,
        ]);
    }
}
