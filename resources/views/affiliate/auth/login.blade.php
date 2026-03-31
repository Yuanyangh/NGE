<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $company ? 'Affiliate Login - ' . $company->name : 'Affiliate Portal' }}</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen" style="font-family: 'Inter', sans-serif;">

    <div class="flex min-h-screen flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                    {{ $company?->name ?? 'Affiliate Portal' }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">Sign in to your affiliate dashboard</p>
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-xl p-8">

                @if (isset($companyError))
                    {{-- Company not found error --}}
                    <div class="rounded-xl bg-red-50 border border-red-100 p-4" role="alert">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-red-600">{{ $companyError }}</p>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <a href="/"
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded">
                            &larr; Back to Home
                        </a>
                    </div>

                @else

                    {{-- Validation errors --}}
                    @if ($errors->any())
                        <div class="mb-6 rounded-xl bg-red-50 border border-red-100 p-4" role="alert">
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                                <div class="text-sm text-red-600 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('affiliate.login.submit', $company->slug) }}" class="space-y-5">
                        @csrf

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Email address
                            </label>
                            <input id="email"
                                   name="email"
                                   type="email"
                                   autocomplete="email"
                                   required
                                   value="{{ old('email') }}"
                                   class="block w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm
                                          focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0
                                          transition-colors duration-200 sm:text-sm"
                                   placeholder="you@example.com">
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Password
                            </label>
                            <input id="password"
                                   name="password"
                                   type="password"
                                   autocomplete="current-password"
                                   required
                                   class="block w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-gray-900 placeholder-gray-400 shadow-sm
                                          focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0
                                          transition-colors duration-200 sm:text-sm"
                                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                        </div>

                        {{-- Submit --}}
                        <div class="pt-1">
                            <button type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-3 px-4
                                           font-semibold text-sm transition-all duration-200 shadow-sm hover:shadow-md
                                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                                           cursor-pointer">
                                Sign in
                            </button>
                        </div>
                    </form>

                @endif

            </div>
            {{-- End card --}}

        </div>
    </div>

</body>
</html>
