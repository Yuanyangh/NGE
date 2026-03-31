<x-affiliate-layout title="Dashboard">
    <div class="space-y-6">
        <div>
            <p class="text-sm text-gray-500">Welcome back,</p>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ auth()->user()->name }}</h1>
        </div>

        <livewire:dashboard.earnings-summary />
        <livewire:dashboard.tier-progress />
        <livewire:dashboard.qualification-status />
        <livewire:dashboard.recent-activity />
    </div>
</x-affiliate-layout>
