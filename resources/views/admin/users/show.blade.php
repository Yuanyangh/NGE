<x-admin-layout title="{{ $user->name }}">
    <x-admin.page-header :title="$user->name" description="User details and account information.">
        <x-slot:actions>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
            </a>
            <a href="{{ route('admin.users.edit', $user->id) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                Edit
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6">
        <x-admin.form-section title="Account Information">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->company?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Email</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Role</dt>
                    <dd class="mt-1">
                        @php
                            $roleColor = match($user->role) {
                                'admin' => 'primary',
                                'affiliate' => 'success',
                                'customer' => 'info',
                                default => 'gray',
                            };
                        @endphp
                        <x-admin.badge :color="$roleColor" size="md">{{ $user->role }}</x-admin.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</dt>
                    <dd class="mt-1">
                        @php
                            $statusColor = match($user->status) {
                                'active' => 'success',
                                'inactive' => 'gray',
                                'suspended' => 'danger',
                                default => 'gray',
                            };
                        @endphp
                        <x-admin.badge :color="$statusColor" size="md">{{ $user->status }}</x-admin.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Enrolled At</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->enrolled_at?->format('M j, Y g:i A') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Last Order</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->last_order_at?->format('M j, Y g:i A') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Last Reward</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->last_reward_at?->format('M j, Y g:i A') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Created At</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ $user->created_at?->format('M j, Y g:i A') }}</dd>
                </div>
            </dl>
        </x-admin.form-section>
    </div>
</x-admin-layout>
