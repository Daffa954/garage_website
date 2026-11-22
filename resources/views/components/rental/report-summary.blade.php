@props([
    'reportCount' => 0,
    'reportTotalDays' => 0,
    'reportRentalRevenue' => 0,
    'reportEvRevenue' => 0,
    'reportGrandTotal' => 0,
    'isFiltered' => false,
])

{{-- Financial Summary & Rental Metrics --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    {{-- Metric 1: Total Filtered Transactions --}}
    <x-commons.card
        :title="$isFiltered ? 'Filtered Bookings' : 'Total Bookings'"
        :subtitle="'Duration: ' . number_format($reportTotalDays, 0, ',', '.') . ' total days'"
    >
        <x-slot:value-slot>
            <h3 class="text-2xl font-bold text-gray-800">
                {{ $reportCount }} <span class="text-xs font-normal text-gray-500">bookings</span>
            </h3>
        </x-slot:value-slot>
    </x-commons.card>

    {{-- Metric 2: Total Garage Rental Income --}}
    <x-commons.card
        title="Rental Income"
        title-color="text-green-600"
        :value="'Rp ' . number_format($reportRentalRevenue, 0, ',', '.')"
        value-color="text-green-700"
        subtitle="Garage base rental revenue"
    />

    {{-- Metric 3: Total EV Charging Revenue --}}
    <x-commons.card
        title="EV Charging Revenue"
        title-color="text-emerald-600"
        :value="'Rp ' . number_format($reportEvRevenue, 0, ',', '.')"
        value-color="text-emerald-600"
        subtitle="Electric vehicle charging fees"
    />

    {{-- Metric 4: Grand Total Revenue --}}
    <x-commons.card
        class="bg-green-600 border-green-600 text-white"
        title="Grand Total Revenue"
        title-color="text-white font-bold"
        :value="'Rp ' . number_format($reportGrandTotal, 0, ',', '.')"
        value-color="text-white"
        subtitle="Garage Rental + EV Charging"
        subtitle-color="text-gray-100"
    />
</div>
