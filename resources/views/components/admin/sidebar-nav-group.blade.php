@props([
    'label',
])

<div x-data="{ open: true }" {{ $attributes->merge(['class' => 'mt-6 first:mt-0']) }}>
    <button
        @click="open = !open"
        class="flex w-full items-center justify-between px-3 py-1"
    >
        <h3 class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ $label }}</h3>
        <svg
            class="size-3.5 text-slate-500 transition-transform duration-200"
            :class="open ? 'rotate-0' : '-rotate-90'"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>
    <ul
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="mt-1 space-y-0.5"
    >
        {{ $slot }}
    </ul>
</div>
