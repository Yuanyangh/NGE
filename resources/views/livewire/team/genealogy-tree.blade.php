<div class="bg-white overflow-hidden shadow rounded-lg p-5">
    <h3 class="text-lg font-medium text-gray-900">Genealogy Tree</h3>

    @if(!empty($tree))
        <div class="mt-4 font-mono text-sm">
            @include('livewire.team.partials.tree-node', ['node' => $tree, 'isLast' => true, 'prefix' => ''])
        </div>
    @else
        <p class="mt-3 text-sm text-gray-500">No genealogy data available.</p>
    @endif
</div>
