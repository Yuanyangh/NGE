<?php

namespace App\Livewire\Admin\Tables;

use App\Models\Company;
use App\Models\User;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterRole = '';
    public string $filterStatus = '';
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

    public function updatingFilterRole(): void
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
        $query = User::withoutGlobalScope(CompanyScope::class)->with('company');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterRole !== '') {
            $query->where('role', $this->filterRole);
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCompany !== '') {
            $query->where('company_id', $this->filterCompany);
        }

        $users = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('livewire.admin.tables.user-table', [
            'users' => $users,
            'companies' => $companies,
        ]);
    }
}
