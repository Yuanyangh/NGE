@props([
    'name',
    'label',
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'value' => null,
    'wire' => null,
    'hint' => null,
])

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
        {{ $label }}
        @if ($required)
            <span class="text-rose-500">*</span>
        @endif
    </label>
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        @if ($wire) wire:model="{{ $wire }}" @endif
        @if ($required) required @endif
        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        {{ $attributes->except('class') }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @forelse ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value !== null && (string) $value === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @empty
            {{ $slot }}
        @endforelse
    </select>
    @error($name)
        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
