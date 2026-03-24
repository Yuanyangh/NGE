@props([
    'icon' => 'inbox',
    'heading' => 'No results found',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16']) }}>
    <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
        <x-dynamic-component :component="'heroicon-o-' . $icon" class="size-7 text-slate-400 dark:text-slate-500" />
    </div>
    <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">{{ $heading }}</h3>
    @if ($description)
        <p class="mt-1 text-center text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
