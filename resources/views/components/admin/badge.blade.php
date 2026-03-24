@props([
    'color' => 'gray',
    'size' => 'sm',
])

@php
    $colorClasses = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-400/20',
        'danger'  => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/20',
        'info'    => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-400 dark:ring-sky-400/20',
        'primary' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-400/20',
        'gray'    => 'bg-slate-50 text-slate-700 ring-slate-600/20 dark:bg-slate-500/10 dark:text-slate-400 dark:ring-slate-400/20',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-full font-medium uppercase tracking-wide ring-1 ring-inset '
        . ($colorClasses[$color] ?? $colorClasses['gray']) . ' '
        . ($sizeClasses[$size] ?? $sizeClasses['sm']),
]) }}>
    {{ $slot }}
</span>
