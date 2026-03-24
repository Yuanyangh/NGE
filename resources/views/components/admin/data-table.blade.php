@props([])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50/75 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/75">
                <tr>
                    {{ $header }}
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                {{ $body }}
            </tbody>
        </table>
    </div>

    @if (isset($pagination))
        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
            {{ $pagination }}
        </div>
    @endif
</div>
