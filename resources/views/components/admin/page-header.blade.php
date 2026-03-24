@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
