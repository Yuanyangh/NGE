<div class="bg-white overflow-hidden shadow rounded-lg p-5">
    <style>
        /* Tree connector lines */
        .tree-container ul {
            position: relative;
            padding-top: 20px;
            display: flex;
            justify-content: center;
            gap: 0;
            list-style: none;
            margin: 0;
            padding-left: 0;
        }

        .tree-container li {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 8px 0;
            list-style: none;
        }

        .tree-container li::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 20px;
            background: #d1d5db;
        }

        .tree-container li::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #d1d5db;
        }

        .tree-container li:first-child::after {
            width: 50%;
            left: 50%;
        }

        .tree-container li:last-child::after {
            width: 50%;
            left: 0;
            right: auto;
        }

        .tree-container li:only-child::after {
            display: none;
        }

        .tree-container > ul > li::before,
        .tree-container > ul > li::after {
            display: none;
        }

        .tree-container ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 20px;
            background: #d1d5db;
        }

        .tree-container ul ul {
            position: relative;
            padding-top: 20px;
        }

        .tree-fade {
            transition: opacity 150ms ease-in-out;
        }
    </style>
    {{-- Header: title + period toggle --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h3 class="text-lg font-semibold text-gray-900">Genealogy Tree</h3>

        {{-- Segmented period toggle --}}
        <div class="inline-flex rounded-md shadow-sm" role="group" aria-label="Select period">
            <button
                type="button"
                wire:click="setDay"
                class="relative inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-l-md border focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-150
                    {{ $period === 'day'
                        ? 'bg-indigo-600 border-indigo-600 text-white'
                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}"
                aria-pressed="{{ $period === 'day' ? 'true' : 'false' }}"
            >
                Today
            </button>
            <button
                type="button"
                wire:click="setWeek"
                class="relative inline-flex items-center px-3 py-1.5 text-sm font-medium border-t border-b focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-150
                    {{ $period === 'week'
                        ? 'bg-indigo-600 border-indigo-600 text-white'
                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}"
                aria-pressed="{{ $period === 'week' ? 'true' : 'false' }}"
            >
                This Week
            </button>
            <button
                type="button"
                wire:click="setMonth"
                class="relative inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-r-md border focus:z-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-150
                    {{ $period === 'month'
                        ? 'bg-indigo-600 border-indigo-600 text-white'
                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}"
                aria-pressed="{{ $period === 'month' ? 'true' : 'false' }}"
            >
                This Month
            </button>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading class="flex items-center gap-2 mb-3 text-sm text-indigo-600">
        <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span>Updating...</span>
    </div>

    {{-- Tree or empty state --}}
    @if (!empty($tree))
        <div class="overflow-x-auto -mx-5 px-5 pb-4" wire:loading.class="opacity-50 tree-fade">
            <div class="tree-container min-w-max">
                <ul>
                    @include('livewire.team.partials.tree-node', ['node' => $tree])
                </ul>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-10 text-center">
            <svg class="h-10 w-10 text-gray-300 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-sm text-gray-500">No genealogy data available.</p>
        </div>
    @endif
</div>
