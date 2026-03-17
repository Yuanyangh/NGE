<?php

namespace App\Livewire\Commissions;

use App\Models\CommissionLedgerEntry;
use App\Scopes\CompanyScope;
use Livewire\Component;
use Livewire\WithPagination;

class CommissionHistory extends Component
{
    use WithPagination;

    public string $filterType = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->orderByDesc('created_at');

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        return view('livewire.commissions.commission-history', [
            'entries' => $query->paginate(15),
        ]);
    }
}
