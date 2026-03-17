<?php

namespace App\Livewire\Dashboard;

use App\Models\CommissionLedgerEntry;
use App\Scopes\CompanyScope;
use Livewire\Component;

class RecentActivity extends Component
{
    public array $entries = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->entries = CommissionLedgerEntry::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (CommissionLedgerEntry $e) => [
                'type' => $e->type,
                'amount' => $e->amount,
                'tier_achieved' => $e->tier_achieved,
                'description' => $e->description,
                'created_at' => $e->created_at?->format('M j, Y'),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.recent-activity');
    }
}
