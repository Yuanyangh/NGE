<x-admin-layout title="Reports — {{ $company->name }}">
    <x-admin.page-header
        title="Reports"
        description="{{ $company->name }} — select a report to view detailed analytics."
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.companies.dashboard', $company) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
                KPI Dashboard
            </a>
            <a
                href="{{ route('admin.companies.edit', $company) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to Company
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Report cards grid --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($reports as $slug => $meta)
            @php
                $colorMap = [
                    'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-500/10',  'icon' => 'text-indigo-600 dark:text-indigo-400',  'hover' => 'hover:border-indigo-300 dark:hover:border-indigo-700'],
                    'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-500/10',    'icon' => 'text-amber-600 dark:text-amber-400',    'hover' => 'hover:border-amber-300 dark:hover:border-amber-700'],
                    'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/10','icon' => 'text-emerald-600 dark:text-emerald-400','hover' => 'hover:border-emerald-300 dark:hover:border-emerald-700'],
                    'sky'     => ['bg' => 'bg-sky-50 dark:bg-sky-500/10',        'icon' => 'text-sky-600 dark:text-sky-400',        'hover' => 'hover:border-sky-300 dark:hover:border-sky-700'],
                    'violet'  => ['bg' => 'bg-violet-50 dark:bg-violet-500/10',  'icon' => 'text-violet-600 dark:text-violet-400',  'hover' => 'hover:border-violet-300 dark:hover:border-violet-700'],
                    'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-500/10',      'icon' => 'text-rose-600 dark:text-rose-400',      'hover' => 'hover:border-rose-300 dark:hover:border-rose-700'],
                    'orange'  => ['bg' => 'bg-orange-50 dark:bg-orange-500/10',  'icon' => 'text-orange-600 dark:text-orange-400',  'hover' => 'hover:border-orange-300 dark:hover:border-orange-700'],
                    'red'     => ['bg' => 'bg-red-50 dark:bg-red-500/10',        'icon' => 'text-red-600 dark:text-red-400',        'hover' => 'hover:border-red-300 dark:hover:border-red-700'],
                    'teal'    => ['bg' => 'bg-teal-50 dark:bg-teal-500/10',      'icon' => 'text-teal-600 dark:text-teal-400',      'hover' => 'hover:border-teal-300 dark:hover:border-teal-700'],
                ];
                $c = $colorMap[$meta['color']] ?? $colorMap['indigo'];
            @endphp
            <a
                href="{{ route('admin.companies.reports.show', [$company, $slug]) }}"
                class="group flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-150 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 {{ $c['hover'] }}"
            >
                {{-- Icon --}}
                <div class="flex size-12 items-center justify-center rounded-xl {{ $c['bg'] }}">
                    @include('admin.reports.partials.report-icon', ['icon' => $meta['icon'], 'class' => 'size-6 ' . $c['icon']])
                </div>

                {{-- Content --}}
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400">
                        {{ $meta['title'] }}
                    </h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                        {{ $meta['description'] }}
                    </p>
                </div>

                {{-- Arrow --}}
                <div class="flex items-center gap-1 text-xs font-medium text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                    View report
                    <svg class="size-3.5 transition-transform group-hover:translate-x-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </div>
            </a>
        @endforeach
    </div>
</x-admin-layout>
