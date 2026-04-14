<?php

namespace App\Livewire\Admin\Genealogy;

use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Scopes\CompanyScope;
use App\Services\Compliance\ChurnDetector;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NetworkExplorer extends Component
{
    #[Locked]
    public int $companyId;

    public ?int $selectedNodeId = null;

    public function mount(int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function selectNode(int $nodeId): void
    {
        $this->selectedNodeId = ($this->selectedNodeId === $nodeId) ? null : $nodeId;
    }

    public function clearSelection(): void
    {
        $this->selectedNodeId = null;
    }

    #[Computed]
    public function treeData(): array
    {
        // Load ALL nodes for this company with user data (typically < 1000 nodes)
        $nodes = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->with('user:id,name,email,role,status,enrolled_at')
            ->where('company_id', $this->companyId)
            ->orderBy('id')
            ->get();

        // Get churn risk data
        $churnResults = app(ChurnDetector::class)->scan($this->companyId, Carbon::today());
        $churnByUserId = $churnResults->keyBy(fn ($r) => $r->user_id)->toArray();

        // Build flat array for Alpine.js
        $treeNodes = [];
        foreach ($nodes as $node) {
            $userId = $node->user_id;
            $churn = $churnByUserId[$userId] ?? null;

            $treeNodes[] = [
                'id' => $node->id,
                'user_id' => $userId,
                'sponsor_id' => $node->sponsor_id,
                'name' => $node->user?->name ?? 'Unknown',
                'email' => $node->user?->email ?? '',
                'role' => $node->user?->role ?? 'customer',
                'status' => $node->user?->status ?? 'inactive',
                'enrolled_at' => $node->user?->enrolled_at?->format('M j, Y'),
                'risk_level' => $churn['risk_level'] ?? 'healthy',
                'risk_reason' => $churn['reason'] ?? '',
            ];
        }

        return $treeNodes;
    }

    #[Computed]
    public function stats(): array
    {
        $nodes = $this->treeData;
        $total = count($nodes);
        $affiliates = collect($nodes)->where('role', 'affiliate');

        return [
            'total' => $total,
            'affiliates' => $affiliates->count(),
            'at_risk' => collect($nodes)->whereIn('risk_level', ['at_risk', 'inactive_warning', 'declining'])->count(),
            'healthy' => collect($nodes)->where('risk_level', 'healthy')->count(),
        ];
    }

    #[Computed]
    public function selectedNodeStats(): array
    {
        if ($this->selectedNodeId === null) {
            return [];
        }

        $node = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->with('user')
            ->where('company_id', $this->companyId)
            ->find($this->selectedNodeId);

        if (!$node) {
            return [];
        }

        $userId = $node->user_id;

        $directDownline = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('sponsor_id', $this->selectedNodeId)
            ->count();

        $totalDownline = $node->descendants()
            ->where('company_id', $this->companyId)
            ->count();

        $last30Volume = (string) Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('user_id', $userId)
            ->whereIn('type', ['purchase', 'smartship'])
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereBetween('transaction_date', [
                now()->subDays(29)->toDateString(),
                now()->toDateString(),
            ])
            ->sum('xp');

        // Get 30d earnings
        $earnings = DB::table('commission_ledger_entries')
            ->where('company_id', $this->companyId)
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        return [
            'node' => $node,
            'user' => $node->user,
            'direct_downline' => $directDownline,
            'total_downline' => $totalDownline,
            'volume_30d' => $last30Volume,
            'earnings_30d' => bcadd((string) ($earnings ?? '0'), '0', 2),
        ];
    }

    public function render()
    {
        return view('livewire.admin.genealogy.network-explorer');
    }
}
