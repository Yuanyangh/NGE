<?php

namespace App\Livewire\Admin\Tables;

use App\Models\CommissionRun;
use App\Models\Company;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class CommissionRunTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterCompany = '';
    public string $sortField = 'run_date';
    public string $sortDirection = 'desc';
    public int $perPage = 25;

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCompany(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->with(['company', 'compensationPlan']);

        if ($this->search !== '') {
            $query->whereHas('company', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCompany !== '') {
            $query->where('company_id', $this->filterCompany);
        }

        $runs = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('livewire.admin.tables.commission-run-table', [
            'runs' => $runs,
            'companies' => $companies,
        ]);
    }
}
