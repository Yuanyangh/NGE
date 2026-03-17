<?php

namespace App\Livewire\Team;

use App\Models\GenealogyNode;
use App\Models\User;
use App\Scopes\CompanyScope;
use Livewire\Component;

class GenealogyTree extends Component
{
    public array $tree = [];

    public function mount(): void
    {
        $user = auth()->user();

        $affiliateNode = GenealogyNode::withoutGlobalScope(CompanyScope::class)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->first();

        if (! $affiliateNode) {
            return;
        }

        // Pre-fetch all descendants in one query
        $allNodes = $affiliateNode->descendants()->get()->push($affiliateNode);
        $nodesByParent = $allNodes->groupBy('sponsor_id');

        // Pre-fetch all users in one query
        $userIds = $allNodes->pluck('user_id')->toArray();
        $usersById = User::withoutGlobalScope(CompanyScope::class)
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $this->tree = $this->buildTree($affiliateNode, 0, 3, $nodesByParent, $usersById);
    }

    private function buildTree(
        GenealogyNode $node,
        int $depth,
        int $maxDepth,
        \Illuminate\Support\Collection $nodesByParent,
        \Illuminate\Support\Collection $usersById,
    ): array {
        $user = $usersById->get($node->user_id);
        if (! $user) {
            return [];
        }

        $entry = [
            'name' => $user->name,
            'role' => $user->role,
            'status' => $user->status,
            'depth' => $depth,
            'children' => [],
        ];

        if ($depth < $maxDepth) {
            $children = $nodesByParent->get($node->id, collect());
            foreach ($children as $child) {
                $childEntry = $this->buildTree($child, $depth + 1, $maxDepth, $nodesByParent, $usersById);
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
