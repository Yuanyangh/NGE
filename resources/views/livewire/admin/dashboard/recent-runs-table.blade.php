<div>
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Commission Runs</h2>
        </div>

        @if ($runs->isEmpty())
            <div class="px-5 py-12 text-center">
                <x-admin.empty-state
                    icon="bolt"
                    heading="No commission runs yet"
                    description="Commission runs will appear here once they are executed."
                />
            </div>
        @else
            <x-admin.data-table>
                <x-slot:header>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Run Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Affiliate $</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Viral $</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Cap</th>
                </x-slot:header>

                <x-slot:body>
                    @foreach ($runs as $run)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                {{ $run->company?->name ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ $run->run_date->format('M j, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @php
                                    $statusColor = match($run->status) {
                                        'completed' => 'success',
                                        'running' => 'warning',
                                        'failed' => 'danger',
                                        'pending' => 'info',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-admin.badge :color="$statusColor">{{ $run->status }}</x-admin.badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <x-admin.money :amount="$run->total_affiliate_commission" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <x-admin.money :amount="$run->total_viral_commission" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @if ($run->viral_cap_triggered)
                                    <svg class="mx-auto size-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-admin.data-table>
        @endif
    </div>
</div>
