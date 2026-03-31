<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Affiliate Dashboard' }} - {{ app()->bound('current_company') ? app('current_company')->name : '' }}</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
        }
    </style>
    @livewireStyles
</head>
<body class="h-full bg-gray-50" style="font-family: 'Inter', sans-serif;">
    <div class="min-h-full">

        {{-- Top navigation bar --}}
        <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">

                    {{-- Left: logo + desktop nav links --}}
                    <div class="flex items-center gap-8">
                        <div class="flex-shrink-0">
                            <span class="text-xl font-bold text-indigo-600 tracking-tight">
                                {{ app()->bound('current_company') ? app('current_company')->name : 'Dashboard' }}
                            </span>
                        </div>

                        @php $company = app()->bound('current_company') ? app('current_company') : null; @endphp
                        @if($company)
                        <div class="hidden sm:flex sm:items-center sm:gap-1">

                            {{-- Dashboard --}}
                            <a href="{{ route('affiliate.dashboard', $company->slug) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                                      {{ request()->routeIs('affiliate.dashboard')
                                         ? 'bg-indigo-50 text-indigo-600'
                                         : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}"
                               aria-current="{{ request()->routeIs('affiliate.dashboard') ? 'page' : 'false' }}">
                                {{-- Heroicons outline: home (20x20) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 flex-shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12L10 4.25 17.75 12M4.5 10.5V17.25a.75.75 0 00.75.75h3.75v-4.5h2v4.5h3.75a.75.75 0 00.75-.75V10.5" />
                                </svg>
                                Dashboard
                            </a>

                            {{-- Team --}}
                            <a href="{{ route('affiliate.team', $company->slug) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                                      {{ request()->routeIs('affiliate.team')
                                         ? 'bg-indigo-50 text-indigo-600'
                                         : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}"
                               aria-current="{{ request()->routeIs('affiliate.team') ? 'page' : 'false' }}">
                                {{-- Heroicons outline: user-group (20x20) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 flex-shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a6 6 0 00-12 0M12 18.75a6 6 0 00-12 0M15 8.25a3 3 0 11-6 0 3 3 0 016 0zM6 8.25a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Team
                            </a>

                            {{-- Commissions --}}
                            <a href="{{ route('affiliate.commissions', $company->slug) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                                      {{ request()->routeIs('affiliate.commissions')
                                         ? 'bg-indigo-50 text-indigo-600'
                                         : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}"
                               aria-current="{{ request()->routeIs('affiliate.commissions') ? 'page' : 'false' }}">
                                {{-- Heroicons outline: banknotes (20x20) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 flex-shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75A2.25 2.25 0 014.5 16.5h15a2.25 2.25 0 012.25 2.25v.75a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25v-.75z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75A2.25 2.25 0 014.5 4.5h15a2.25 2.25 0 012.25 2.25v6a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 12.75v-6zM9.75 9.75a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0z" />
                                </svg>
                                Commissions
                            </a>

                            {{-- Wallet --}}
                            <a href="{{ route('affiliate.wallet', $company->slug) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                                      {{ request()->routeIs('affiliate.wallet')
                                         ? 'bg-indigo-50 text-indigo-600'
                                         : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}"
                               aria-current="{{ request()->routeIs('affiliate.wallet') ? 'page' : 'false' }}">
                                {{-- Heroicons outline: wallet (20x20) --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5 flex-shrink-0" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h15.5M2.25 9v6.75a2.25 2.25 0 002.25 2.25h11a2.25 2.25 0 002.25-2.25V9M3.5 5.25l1-2.25h11l1 2.25M13.5 12.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                                Wallet
                            </a>

                        </div>
                        @endif
                    </div>

                    {{-- Right: user avatar + name + logout --}}
                    <div class="flex items-center gap-3">
                        @php
                            $userName = auth()->user()?->name ?? '';
                            $initials = collect(explode(' ', trim($userName)))
                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                ->take(2)
                                ->implode('');
                        @endphp

                        {{-- Avatar circle with initials --}}
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold flex-shrink-0 select-none"
                              aria-hidden="true">
                            {{ $initials ?: '?' }}
                        </span>

                        <span class="hidden sm:block text-sm font-medium text-gray-700">{{ $userName }}</span>

                        <form method="POST" action="{{ route('affiliate.logout') }}">
                            @csrf
                            <button type="submit"
                                    class="text-sm text-gray-400 hover:text-gray-600 transition-colors duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded"
                                    aria-label="Log out">
                                Logout
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            {{-- Mobile navigation: horizontally scrollable pills with icons --}}
            @if($company ?? false)
            <div class="sm:hidden border-t border-gray-100">
                <div class="flex overflow-x-auto no-scrollbar gap-1 px-3 py-2">

                    <a href="{{ route('affiliate.dashboard', $company->slug) }}"
                       class="inline-flex items-center gap-1.5 flex-shrink-0 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('affiliate.dashboard')
                                 ? 'bg-indigo-50 text-indigo-600'
                                 : 'text-gray-500 hover:text-gray-700' }}"
                       aria-current="{{ request()->routeIs('affiliate.dashboard') ? 'page' : 'false' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-4 h-4 flex-shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12L10 4.25 17.75 12M4.5 10.5V17.25a.75.75 0 00.75.75h3.75v-4.5h2v4.5h3.75a.75.75 0 00.75-.75V10.5" />
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('affiliate.team', $company->slug) }}"
                       class="inline-flex items-center gap-1.5 flex-shrink-0 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('affiliate.team')
                                 ? 'bg-indigo-50 text-indigo-600'
                                 : 'text-gray-500 hover:text-gray-700' }}"
                       aria-current="{{ request()->routeIs('affiliate.team') ? 'page' : 'false' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-4 h-4 flex-shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.75a6 6 0 00-12 0M12 18.75a6 6 0 00-12 0M15 8.25a3 3 0 11-6 0 3 3 0 016 0zM6 8.25a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Team
                    </a>

                    <a href="{{ route('affiliate.commissions', $company->slug) }}"
                       class="inline-flex items-center gap-1.5 flex-shrink-0 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('affiliate.commissions')
                                 ? 'bg-indigo-50 text-indigo-600'
                                 : 'text-gray-500 hover:text-gray-700' }}"
                       aria-current="{{ request()->routeIs('affiliate.commissions') ? 'page' : 'false' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-4 h-4 flex-shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75A2.25 2.25 0 014.5 16.5h15a2.25 2.25 0 012.25 2.25v.75a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25v-.75z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75A2.25 2.25 0 014.5 4.5h15a2.25 2.25 0 012.25 2.25v6a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 12.75v-6zM9.75 9.75a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0z" />
                        </svg>
                        Commissions
                    </a>

                    <a href="{{ route('affiliate.wallet', $company->slug) }}"
                       class="inline-flex items-center gap-1.5 flex-shrink-0 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
                              {{ request()->routeIs('affiliate.wallet')
                                 ? 'bg-indigo-50 text-indigo-600'
                                 : 'text-gray-500 hover:text-gray-700' }}"
                       aria-current="{{ request()->routeIs('affiliate.wallet') ? 'page' : 'false' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" class="w-4 h-4 flex-shrink-0" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h15.5M2.25 9v6.75a2.25 2.25 0 002.25 2.25h11a2.25 2.25 0 002.25-2.25V9M3.5 5.25l1-2.25h11l1 2.25M13.5 12.75a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                        </svg>
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
