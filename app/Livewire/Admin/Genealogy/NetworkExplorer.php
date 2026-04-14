<?php

namespace App\Livewire\Admin\Genealogy;

use App\DTOs\ChurnRiskResult;
use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Services\Compliance\ChurnDetector;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NetworkExplorer extends Component
{
    #[Locked]
    public int $companyId;

    public string $search = '';

    /** IDs of nodes that are currently expanded (loaded children visible). */
    public array $expandedNodeIds = [];

    /** The GenealogyNode ID currently selected for the side-panel. */
    public ?int $selectedNodeId = null;

    public function mount(int $companyId): void
    {
        $this->companyId = $companyId;
    }

    // -----------------------------------------------------------------
    // Interactions
    // -----------------------------------------------------------------

    public function expandNode(int $nodeId): void
    {
        if (!in_array($nodeId, $this->expandedNodeIds, true)) {
            $this->expandedNodeIds[] = $nodeId;
        }
    }

    public function collapseNode(int $nodeId): void
    {
        $this->expandedNodeIds = array_values(
            array_filter($this->expandedNodeIds, fn ($id) => $id !== $nodeId)
        );
    }

    public function selectNode(int $nodeId): void
    {
        $this->selectedNodeId = ($this->selectedNodeId === $nodeId) ? null : $nodeId;
    }

    // -----------------------------------------------------------------
    // Computed properties
    // -----------------------------------------------------------------

    #[Computed]
    public function rootNodes(): Collection
    {
        return GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->with('user:id,name,status,enrolled_at')
            ->where('company_id', $this->companyId)
            ->whereNull('sponsor_id')
            ->when($this->search !== '', function ($q) {
                $q->whereHas('user', fn ($uq) =>
                    $uq->where('name', 'like', '%' . $this->search . '%')
                );
            })
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function childrenOf(): array
    {
        if (empty($this->expandedNodeIds)) {
            return [];
        }

        $rows = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->with('user:id,name,status,enrolled_at')
            ->where('company_id', $this->companyId)
            ->whereIn('sponsor_id', $this->expandedNodeIds)
            ->orderBy('id')
            ->get();

        return $rows->groupBy('sponsor_id')->map->values()->toArray();
    }

    #[Computed]
    public function churnByUserId(): array
    {
        $detector = app(ChurnDetector::class);
        $results  = $detector->scan($this->companyId, Carbon::today());

        return $results->keyBy('user_id')->toArray();
    }

    #[Computed]
    public function selectedNode(): ?GenealogyNode
    {
        if ($this->selectedNodeId === null) {
            return null;
        }

        return GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->with('user')
            ->where('company_id', $this->companyId)
            ->find($this->selectedNodeId);
    }

    #[Computed]
    public function selectedNodeStats(): array
    {
        if ($this->selectedNodeId === null || $this->selectedNode === null) {
            return [];
        }

        $userId = $this->selectedNode->user_id;

        $directDownline = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('sponsor_id', $this->selectedNodeId)
            ->count();

        $totalDownline = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('id', $this->selectedNodeId)
            ->first()
            ?->descendants()
            ->where('company_id', $this->companyId)
            ->count() ?? 0;

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

        return [
            'direct_downline' => $directDownline,
            'total_downline'  => $totalDownline,
            'volume_30d'      => $last30Volume,
        ];
    }

    public function render()
    {
        return view('livewire.admin.genealogy.network-explorer');
    }
}
