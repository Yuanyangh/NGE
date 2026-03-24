@props([
    'amount',
    'currency' => 'USD',
    'decimals' => 2,
])

@php
    $numericAmount = is_numeric($amount) ? (float) $amount : 0;
    $isNegative = $numericAmount < 0;
    $formatted = ($isNegative ? '-' : '') . '$' . number_format(abs($numericAmount), $decimals);
@endphp

<span {{ $attributes->merge([
    'class' => 'tabular-nums font-medium text-right '
        . ($isNegative
            ? 'text-rose-600 dark:text-rose-400'
            : ($numericAmount > 0
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-slate-500 dark:text-slate-400')),
]) }}>
    {{ $formatted }}
</span>
