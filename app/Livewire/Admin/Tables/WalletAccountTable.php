<?php

namespace App\Livewire\Admin\Tables;

use App\Models\Company;
use App\Models\WalletAccount;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class WalletAccountTable extends Component
{
    use WithPagination;

    public string $search = '';
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

    public function updatingFilterCompany(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = WalletAccount::withoutGlobalScope(CompanyScope::class)
            ->with(['user', 'company']);

        $authUser = auth()->user();
        if ($authUser && $authUser->isCompanyAdmin()) {
            $query->where('company_id', $authUser->company_id);
        }

        if ($this->search !== '') {
            $searchTerm = '%' . $this->search . '%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $searchTerm)
                ->orWhere('email', 'like', $searchTerm));
        }

        if ($this->filterCompany !== '') {
            $query->where('company_id', $this->filterCompany);
        }

        $accounts = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $accounts->through(fn ($wa) => tap($wa, fn ($w) => $w->computed_balance = $w->totalNonReversed()));

        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('livewire.admin.tables.wallet-account-table', [
            'accounts' => $accounts,
            'companies' => $companies,
        ]);
    }
}
