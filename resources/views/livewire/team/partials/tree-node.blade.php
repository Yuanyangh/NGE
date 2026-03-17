@php
    $statusColor = $node['status'] === 'active' ? 'text-green-600' : 'text-gray-400';
    $roleLabel = $node['role'] === 'affiliate' ? 'affiliate' : 'customer';
@endphp

<div class="flex items-center gap-1 py-0.5">
    <span class="text-gray-400">{!! $prefix !!}{{ $isLast ? '&#x2514;&#x2500;&#x2500; ' : '&#x251C;&#x2500;&#x2500; ' }}</span>
    <span class="font-medium text-gray-900">{{ $node['name'] }}</span>
    <span class="text-xs {{ $statusColor }}">({{ $roleLabel }}, {{ $node['status'] }})</span>
</div>

@if(!empty($node['children']))
    @foreach($node['children'] as $index => $child)
        @include('livewire.team.partials.tree-node', [
            'node' => $child,
            'isLast' => $index === count($node['children']) - 1,
            'prefix' => $prefix . ($isLast ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '&#x2502;&nbsp;&nbsp;&nbsp;'),
        ])
    @endforeach
@endif
