<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Affiliate Dashboard' }} - {{ app()->bound('current_company') ? app('current_company')->name : '' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="h-full">
    <div class="min-h-full">
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <span class="text-xl font-bold text-indigo-600">
                                {{ app()->bound('current_company') ? app('current_company')->name : 'Dashboard' }}
                            </span>
                        </div>
                        @php $company = app()->bound('current_company') ? app('current_company') : null; @endphp
                        @if($company)
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('affiliate.dashboard', $company->slug) }}"
                               class="{{ request()->routeIs('affiliate.dashboard') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="{{ route('affiliate.team', $company->slug) }}"
                               class="{{ request()->routeIs('affiliate.team') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                Team
                            </a>
                            <a href="{{ route('affiliate.commissions', $company->slug) }}"
                               class="{{ request()->routeIs('affiliate.commissions') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                Commissions
                            </a>
                            <a href="{{ route('affiliate.wallet', $company->slug) }}"
                               class="{{ request()->routeIs('affiliate.wallet') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                Wallet
                            </a>
                        </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">{{ auth()->user()?->name }}</span>
                        <form method="POST" action="{{ route('affiliate.logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Mobile navigation --}}
            @if($company)
            <div class="sm:hidden border-t border-gray-200">
                <div class="flex space-x-1 px-2 py-2">
                    <a href="{{ route('affiliate.dashboard', $company->slug) }}"
                       class="{{ request()->routeIs('affiliate.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500' }} rounded-md px-3 py-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="{{ route('affiliate.team', $company->slug) }}"
                       class="{{ request()->routeIs('affiliate.team') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500' }} rounded-md px-3 py-2 text-sm font-medium">
                        Team
                    </a>
                    <a href="{{ route('affiliate.commissions', $company->slug) }}"
                       class="{{ request()->routeIs('affiliate.commissions') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500' }} rounded-md px-3 py-2 text-sm font-medium">
                        Commissions
                    </a>
                    <a href="{{ route('affiliate.wallet', $company->slug) }}"
                       class="{{ request()->routeIs('affiliate.wallet') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500' }} rounded-md px-3 py-2 text-sm font-medium">
                        Wallet
                    </a>
                </div>
            </div>
            @endif
        </nav>

        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>
    @livewireScripts
</body>
</html>
