@props([
    'name',
    'label',
    'checked' => false,
    'wire' => null,
])

<div
    x-data="{ on: @js((bool) $checked) }"
    {{ $attributes->merge(['class' => 'flex items-center justify-between']) }}
>
    <label for="{{ $name }}" class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</label>

    <input type="hidden" name="{{ $name }}" :value="on ? '1' : '0'" @if ($wire) wire:model="{{ $wire }}" @endif>

    <button
        type="button"
        id="{{ $name }}"
        role="switch"
        :aria-checked="on.toString()"
        @click="on = !on; @if($wire) $wire.set('{{ $wire }}', on) @endif"
        :class="on ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-700'"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
    >
        <span
            :class="on ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block size-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
        ></span>
    </button>
</div>
