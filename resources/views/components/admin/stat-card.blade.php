@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'indigo',
    'trend' => null,
    'trendUp' => true,
])

@php
    $colorMap = [
        'indigo' => [
            'bg' => 'bg-indigo-50 dark:bg-indigo-500/10',
            'text' => 'text-indigo-600 dark:text-indigo-400',
            'ring' => 'ring-indigo-500/20 dark:ring-indigo-400/20',
        ],
        'emerald' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-500/10',
            'text' => 'text-emerald-600 dark:text-emerald-400',
            'ring' => 'ring-emerald-500/20 dark:ring-emerald-400/20',
        ],
        'amber' => [
            'bg' => 'bg-amber-50 dark:bg-amber-500/10',
            'text' => 'text-amber-600 dark:text-amber-400',
            'ring' => 'ring-amber-500/20 dark:ring-amber-400/20',
        ],
        'rose' => [
            'bg' => 'bg-rose-50 dark:bg-rose-500/10',
            'text' => 'text-rose-600 dark:text-rose-400',
            'ring' => 'ring-rose-500/20 dark:ring-rose-400/20',
        ],
    ];
    $c = $colorMap[$color] ?? $colorMap['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'relative rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900']) }}>
    {{-- Trend indicator --}}
    @if ($trend)
        <div class="absolute right-5 top-5 flex items-center gap-1 text-xs font-medium {{ $trendUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
            @if ($trendUp)
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
            @else
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.986l.776 5.169m0 0 2.214-5.837m-2.214 5.837-5.837-2.214"/></svg>
            @endif
            {{ $trend }}
        </div>
    @endif

    <div class="flex items-start gap-4">
        {{-- Icon circle --}}
        @if ($icon)
            <div class="flex size-11 shrink-0 items-center justify-center rounded-lg ring-1 {{ $c['bg'] }} {{ $c['ring'] }}">
                <x-dynamic-component :component="'heroicon-o-' . $icon" class="size-5 {{ $c['text'] }}" />
            </div>
        @endif

        <div class="min-w-0">
            <p class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $value }}</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>
        </div>
    </div>
</div>
