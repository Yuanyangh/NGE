<?php

namespace App\Livewire\Admin\Tables;

use App\Models\CommissionLedgerEntry;
use App\Models\Company;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class CommissionLedgerTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterType = '';
    public string $filterCompany = '';
    public string $sortField = 'id';
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

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCompany(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->with(['company', 'user', 'commissionRun']);

        if ($this->search !== '') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
        }

        if ($this->filterType !== '') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterCompany !== '') {
            $query->where('company_id', $this->filterCompany);
        }

        $entries = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('livewire.admin.tables.commission-ledger-table', [
            'entries' => $entries,
            'companies' => $companies,
        ]);
    }
}
