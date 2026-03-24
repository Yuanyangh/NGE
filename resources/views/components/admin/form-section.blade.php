@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900']) }}>
    <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>
    <div class="px-6 py-5">
        {{ $slot }}
    </div>
</div>
