<div>
    {{-- Filters --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search by name or email..."
                class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
            >
        </div>
        <select
            wire:model.live="filterCompany"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        >
            <option value="">All Companies</option>
            @foreach ($companies as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        <select
            wire:model.live="filterRole"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        >
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="affiliate">Affiliate</option>
            <option value="customer">Customer</option>
        </select>
        <select
            wire:model.live="filterStatus"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
        >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
        </select>
    </div>

    @if ($users->isEmpty())
        <x-admin.empty-state
            icon="users"
            heading="No users found"
            description="Try adjusting your search or filters."
        />
    @else
        <x-admin.data-table>
            <x-slot:header>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Company</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <button wire:click="sortBy('name')" class="group inline-flex items-center gap-1">
                        Name
                        @if ($sortField === 'name')
                            <svg class="size-3.5 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd"/></svg>
                        @else
                            <svg class="size-3.5 text-slate-300 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .55.24l3.25 3.5a.75.75 0 1 1-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 0 1-1.1-1.02l3.25-3.5A.75.75 0 0 1 10 3Zm-3.76 9.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Role</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <button wire:click="sortBy('enrolled_at')" class="group inline-flex items-center gap-1">
                        Enrolled
                        @if ($sortField === 'enrolled_at')
                            <svg class="size-3.5 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd"/></svg>
                        @else
                            <svg class="size-3.5 text-slate-300 dark:text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .55.24l3.25 3.5a.75.75 0 1 1-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 0 1-1.1-1.02l3.25-3.5A.75.75 0 0 1 10 3Zm-3.76 9.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
            </x-slot:header>

            <x-slot:body>
                @foreach ($users as $user)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            {{ $user->company?->name ?? 'N/A' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                            {{ $user->name }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            {{ $user->email }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $roleColor = match($user->role) {
                                    'admin' => 'primary',
                                    'affiliate' => 'success',
                                    'customer' => 'info',
                                    default => 'gray',
                                };
                            @endphp
                            <x-admin.badge :color="$roleColor">{{ $user->role }}</x-admin.badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @php
                                $statusColor = match($user->status) {
                                    'active' => 'success',
                                    'inactive' => 'gray',
                                    'suspended' => 'danger',
                                    default => 'gray',
                                };
                            @endphp
                            <x-admin.badge :color="$statusColor">{{ $user->status }}</x-admin.badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            {{ $user->enrolled_at?->format('M j, Y') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="font-medium text-slate-600 hover:text-slate-500 dark:text-slate-400 dark:hover:text-slate-300">View</a>
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Edit</a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-rose-600 hover:text-rose-500 dark:text-rose-400 dark:hover:text-rose-300">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>

            <x-slot:pagination>
                {{ $users->links() }}
            </x-slot:pagination>
        </x-admin.data-table>
    @endif
</div>
