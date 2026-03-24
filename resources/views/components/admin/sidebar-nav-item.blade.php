@props([
    'route',
    'label',
])

@php
    $isActive = request()->routeIs($route . '*');
    $href = '#';
    try {
        $href = route($route);
    } catch (\Throwable $e) {
        // Route not yet registered — keep # as fallback
    }
@endphp

<li>
    <a
        href="{{ $href }}"
        @class([
            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
            'bg-slate-800 text-white' => $isActive,
            'text-slate-400 hover:bg-slate-800/50 hover:text-white' => !$isActive,
        ])
    >
        {{-- Icon slot --}}
        <span class="size-5 shrink-0">
            {{ $icon ?? '' }}
        </span>
        {{ $label }}
    </a>
</li>
