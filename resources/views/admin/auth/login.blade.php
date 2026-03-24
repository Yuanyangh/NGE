<!DOCTYPE html>
<html lang="en" x-data x-bind:class="$store.darkMode.on ? 'dark' : ''" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in - TyeUps NGE</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/admin.css'])
</head>
<body class="h-full">
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('darkMode', {
                on: localStorage.getItem('admin-dark-mode') === 'true',
                toggle() {
                    this.on = !this.on;
                    localStorage.setItem('admin-dark-mode', this.on);
                }
            });
        });
    </script>

    <div class="flex min-h-full items-center justify-center bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 px-4 py-12 dark:from-slate-900 dark:via-indigo-950 dark:to-slate-900">
        <div class="w-full max-w-sm">
            {{-- Brand --}}
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-white">TyeUps</h1>
                <span class="mt-1 inline-block rounded-full bg-white/15 px-3 py-0.5 text-xs font-semibold uppercase tracking-widest text-indigo-100">
                    Network Growth Engine
                </span>
            </div>

            {{-- Login card --}}
            <div class="rounded-xl bg-white p-8 shadow-2xl dark:bg-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Sign in to your account</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Admin panel access only.</p>

                @if ($errors->any())
                    <div class="mt-4 rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                        @foreach ($errors->all() as $error)
                            <p class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            autofocus
                            value="{{ old('email') }}"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20"
                            placeholder="admin@example.com"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400/20"
                            placeholder="Enter your password"
                        >
                    </div>

                    <div class="flex items-center">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700"
                        >
                        <label for="remember" class="ml-2 text-sm text-slate-600 dark:text-slate-400">Remember me</label>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
