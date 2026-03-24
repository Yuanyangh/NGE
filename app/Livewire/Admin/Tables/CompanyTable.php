<?php

namespace App\Livewire\Admin\Tables;

use App\Models\Company;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterActive = '';
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

    public function render()
    {
        $query = Company::query();

        if ($this->search !== '') {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->filterActive !== '') {
            $query->where('is_active', $this->filterActive === '1');
        }

        $companies = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.tables.company-table', [
            'companies' => $companies,
        ]);
    }
}
