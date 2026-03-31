<?php

namespace App\Livewire\Team;

use App\Models\GenealogyNode;
use App\Models\Transaction;
use App\Models\User;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Livewire\Component;

class GenealogyTree extends Component
{
    public array $tree = [];
    public string $period = 'month';

    public function mount(): void
    {
        $this->loadTree();
    }

    public function setDay(): void
    {
        $this->period = 'day';
        $this->loadTree();
    }

    public function setWeek(): void
    {
        $this->period = 'week';
        $this->loadTree();
    }

    public function setMonth(): void
    {
        $this->period = 'month';
        $this->loadTree();
    }

    public function loadTree(): void
    {
        $this->tree = [];

        $user = auth()->user();

        $affiliateNode = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->first();

        if (! $affiliateNode) {
            return;
        }

        // Pre-fetch all descendants up to max depth + root node in one query
        $descendants = $affiliateNode->descendants()->get();
        $allNodes = $descendants->push($affiliateNode);

        // Pre-fetch all users in one query
        $userIds = $allNodes->pluck('user_id')->unique()->toArray();
        $usersById = User::withoutGlobalScope(CompanyScope::class)
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        // Determine date range from period
        $startDate = match ($this->period) {
            'day'   => Carbon::today(),
            'week'  => Carbon::today()->subDays(6),
            default => Carbon::today()->subDays(29),
        };

        // Pre-fetch transaction volumes grouped by user_id
        $volumesByUserId = Transaction::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $user->company_id)
            ->whereIn('user_id', $userIds)
            ->where('status', 'confirmed')
            ->where('qualifies_for_commission', true)
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', Carbon::today())
            ->selectRaw('user_id, SUM(xp) as total_xp')
            ->groupBy('user_id')
            ->pluck('total_xp', 'user_id');

        // Group nodes by their sponsor_id for child lookups
        $nodesByParent = $allNodes->groupBy('sponsor_id');

        // Count direct children per node from pre-fetched data
        $directRecruitCounts = $allNodes
            ->filter(fn($node) => $node->id !== $affiliateNode->id)
            ->groupBy('sponsor_id')
            ->map(fn($children) => $children->count());

        $this->tree = $this->buildTree(
            $affiliateNode,
            0,
            10,
            $nodesByParent,
            $usersById,
            $volumesByUserId,
            $directRecruitCounts,
            true
        );
    }

    private function buildTree(
        GenealogyNode $node,
        int $depth,
        int $maxDepth,
        \Illuminate\Support\Collection $nodesByParent,
        \Illuminate\Support\Collection $usersById,
        \Illuminate\Support\Collection $volumesByUserId,
        \Illuminate\Support\Collection $directRecruitCounts,
        bool $isRoot = false,
    ): array {
        $user = $usersById->get($node->user_id);

        if (! $user) {
            return [];
        }

        $entry = [
            'name'             => $user->name,
            'role'             => $user->role,
            'status'           => $user->status,
            'depth'            => $depth,
            'personal_volume'  => (string) ($volumesByUserId->get($node->user_id) ?? '0'),
            'direct_recruits'  => (int) ($directRecruitCounts->get($node->id) ?? 0),
            'is_root'          => $isRoot,
            'children'         => [],
        ];

        if ($depth < $maxDepth) {
            $children = $nodesByParent->get($node->id, collect());
            foreach ($children as $child) {
                $childEntry = $this->buildTree(
                    $child,
                    $depth + 1,
                    $maxDepth,
                    $nodesByParent,
                    $usersById,
                    $volumesByUserId,
                    $directRecruitCounts,
                    false
                );
                if (! empty($childEntry)) {
                    $entry['children'][] = $childEntry;
                }
            }
        }

        return $entry;
    }

    public function render()
    {
        return view('livewire.team.genealogy-tree');
    }
}
