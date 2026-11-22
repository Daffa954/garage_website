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
        :title="$isFiltered ? 'Jumlah Transaksi (Sesuai Filter)' : 'Total Transaksi Sewa'"
        :subtitle="'Durasi: ' . number_format($reportTotalDays, 0, ',', '.') . ' total hari'"
    >
        <x-slot:value-slot>
            <h3 class="text-2xl font-bold text-gray-800">
                {{ $reportCount }} <span class="text-xs font-normal text-gray-500">transaksi</span>
            </h3>
        </x-slot:value-slot>
    </x-commons.card>

    {{-- Metric 2: Total Garage Rental Income --}}
    <x-commons.card
        title="Pendapatan Sewa Garasi"
        title-color="text-green-600"
        :value="'Rp ' . number_format($reportRentalRevenue, 0, ',', '.')"
        value-color="text-green-700"
        subtitle="Pendapatan dasar sewa garasi"
    />

    {{-- Metric 3: Total EV Charging Revenue --}}
    <x-commons.card
        title="Pendapatan Cas Listrik"
        title-color="text-emerald-600"
        :value="'Rp ' . number_format($reportEvRevenue, 0, ',', '.')"
        value-color="text-emerald-600"
        subtitle="Biaya isi listrik mobil listrik"
    />

    {{-- Metric 4: Grand Total Revenue --}}
    <x-commons.card
        class="bg-green-600 border-green-600 text-white"
        title="Total Seluruh Pendapatan"
        title-color="text-white font-bold"
        :value="'Rp ' . number_format($reportGrandTotal, 0, ',', '.')"
        value-color="text-white"
        subtitle="Sewa Garasi + Pengisian Listrik"
        subtitle-color="text-gray-100"
    />
</div>
