<?php

namespace App\Livewire\Admin\Tables;

use App\Models\Company;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class CompensationPlanTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterActive = '';
    public string $filterCompany = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public int $perPage = 25;

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterActive(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCompany(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = CompensationPlan::withoutGlobalScope(CompanyScope::class)->with('company');

        if ($this->search !== '') {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterActive !== '') {
            $query->where('is_active', $this->filterActive === '1');
        }

        if ($this->filterCompany !== '') {
            $query->where('company_id', $this->filterCompany);
        }

        $plans = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('livewire.admin.tables.compensation-plan-table', [
            'plans' => $plans,
            'companies' => $companies,
        ]);
    }
}
