@props([
    'isAdmin' => false,
    'isFiltered' => false,
    'garageCategories' => [],
    'renters' => collect(),
])

{{-- Filter & Report Criteria Form Panel --}}
<div class="bg-white border border-gray-200 rounded-xl p-5 mb-8 shadow-sm no-print">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 mb-1 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-800">Filter & Report Criteria</h2>
            </div>
        </div>

        @if ($isFiltered)
            <div class="flex items-center gap-2">
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                    Active Filter
                </span>
                <a href="{{ route('rents.index') }}"
                    class="text-xs text-rose-600 hover:text-rose-800 font-medium hover:underline">
                    Reset Filter
                </a>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route('rents.index') }}">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div class="w-full flex-1 grid grid-cols-1 sm:grid-cols-2 {{ $isAdmin ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-3.5">
                {{-- 1. Start Date --}}
                <div>
                    <label for="filter_start_date" class="block text-xs font-medium text-gray-700 mb-1">
                        Start Date
                    </label>
                    <input type="date" name="start_date" id="filter_start_date" value="{{ request('start_date') }}"
                        class="w-full text-xs rounded-lg py-2 px-3 border-[0.5px] border-gray-500 shadow-sm focus:border-green-500 focus:ring-green-500 bg-white">
                </div>

                {{-- 2. End Date --}}
                <div>
                    <label for="filter_end_date" class="block text-xs font-medium text-gray-700 mb-1">
                        End Date
                    </label>
                    <input type="date" name="end_date" id="filter_end_date" value="{{ request('end_date') }}"
                        class="w-full text-xs rounded-lg py-2 px-3 border-[0.5px] border-gray-500 shadow-sm focus:border-green-500 focus:ring-green-500 bg-white">
                </div>

                {{-- 3. Garage Type --}}
                <div>
                    <label for="filter_garage_type" class="block text-xs font-medium text-gray-700 mb-1">
                        Garage Type
                    </label>
                    <div class="relative">
                        <select name="garage_type" id="filter_garage_type"
                            class="w-full appearance-none text-xs rounded-lg py-2 pl-3 pr-8 border-[0.5px] border-gray-500 shadow-sm focus:border-green-500 focus:ring-green-500 bg-white">
                            <option value="">All Garage Types</option>
                            @foreach ($garageCategories as $category)
                                <option value="{{ $category }}"
                                    {{ request('garage_type') == $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- 4. Renter (Admin Only) --}}
                @if ($isAdmin)
                    <div>
                        <label for="filter_renter_id" class="block text-xs font-medium text-gray-700 mb-1">
                            Renter
                        </label>
                        <div class="relative">
                            <select name="renter_id" id="filter_renter_id"
                                class="w-full appearance-none text-xs rounded-lg py-2 pl-3 pr-8 border-[0.5px] border-gray-500 shadow-sm focus:border-green-500 focus:ring-green-500 bg-white">
                                <option value="">All Renters</option>
                                @foreach ($renters as $renter)
                                    <option value="{{ $renter->id }}"
                                        {{ request('renter_id') == $renter->id ? 'selected' : '' }}>
                                        {{ $renter->name }} ({{ $renter->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-end gap-2 shrink-0 pt-2 lg:pt-0">
                @if ($isFiltered)
                    <a href="{{ route('rents.index') }}"
                        class="px-4 py-2 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        Reset
                    </a>
                @endif
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg shadow-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1.5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Apply Filter
                </button>
            </div>
        </div>
    </form>
</div>
