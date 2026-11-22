@props([
    'isAdmin' => false,
    'isFiltered' => false,
    'renters' => collect(),
])

{{-- Page Header --}}
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-green-700">
            {{ $isAdmin ? 'Laporan & Seluruh Transaksi Persewaan' : 'Riwayat Persewaan Saya' }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $isAdmin ? 'Pantau hasil persewaan garasi: per bulan, per hari, per tipe garasi, dan per penyewa.' : 'Lihat riwayat pemesanan garasi dan rincian transaksi persewaan Anda.' }}
        </p>
    </div>

    <div class="flex items-center gap-2 no-print">
        @if (!$isAdmin)
            <a href="{{ route('garages.index') }}"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                <span class="mr-1.5 text-lg leading-none">+</span>
                Sewa Garasi Baru
            </a>
        @endif
    </div>
</div>

{{-- Printable Header (Visible Only on Print) --}}
<div class="print-only mb-6 border-b pb-4">
    <div class="flex justify-between items-start">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Laporan Ringkasan Persewaan Garasi</h2>
            <p class="text-xs text-gray-500 mt-1">Dicetak pada: {{ now()->translatedFormat('d M Y H:i') }} WIB</p>
        </div>
        <div class="text-right text-xs text-gray-600">
            <div class="font-bold">Sistem Manajemen Persewaan Garasi</div>
            <div>Cakupan: {{ $isAdmin ? 'Seluruh Transaksi' : 'Laporan Pribadi Penyewa' }}</div>
        </div>
    </div>

    @if ($isFiltered)
        <div
            class="mt-3 p-2.5 bg-gray-50 border border-gray-200 rounded text-xs text-gray-700 flex flex-wrap gap-x-4 gap-y-1">
            <span class="font-semibold text-gray-900">Kriteria Filter Aktif:</span>
            @if (request('start_date'))
                <span>Dari Tanggal:
                    <strong>{{ \Carbon\Carbon::parse(request('start_date'))->translatedFormat('d M Y') }}</strong></span>
            @endif
            @if (request('end_date'))
                <span>Sampai Tanggal: <strong>{{ \Carbon\Carbon::parse(request('end_date'))->translatedFormat('d M Y') }}</strong></span>
            @endif
            @if (request('garage_type'))
                <span>Tipe Garasi: <strong>{{ request('garage_type') }}</strong></span>
            @endif
            @if (request('renter_id'))
                @php $rObj = $renters->firstWhere('id', request('renter_id')); @endphp
                <span>Penyewa: <strong>{{ $rObj?->name ?? 'ID ' . request('renter_id') }}</strong></span>
            @endif
        </div>
    @endif
</div>
