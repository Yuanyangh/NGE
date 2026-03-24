<x-admin-layout title="Edit User">
    <x-admin.page-header title="Edit User" description="Update user account details.">
        <x-slot:actions>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mt-6">
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <x-admin.form-section title="User Details" description="Account information and access control.">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-admin.select
                        name="company_id"
                        label="Company"
                        :options="$companies"
                        :value="old('company_id', $user->company_id)"
                        required
                        placeholder="Select a company"
                    />
                    <x-admin.input name="name" label="Name" :value="old('name', $user->name)" required />
                    <x-admin.input name="email" label="Email" type="email" :value="old('email', $user->email)" required />
                    <x-admin.select
                        name="role"
                        label="Role"
                        :options="['customer' => 'Customer', 'affiliate' => 'Affiliate', 'admin' => 'Admin']"
                        :value="old('role', $user->role)"
                        required
                    />
                    <x-admin.select
                        name="status"
                        label="Status"
                        :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']"
                        :value="old('status', $user->status)"
                        required
                    />
                </div>
            </x-admin.form-section>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    Update User
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
