@props(['rents', 'isAdmin' => false])

{{-- Rental Transactions List --}}
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">
                {{ $isAdmin ? 'Daftar Transaksi Persewaan & Laporan' : 'Riwayat & Rincian Persewaan Saya' }}
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ request()->anyFilled(['start_date', 'end_date', 'garage_type', 'renter_id']) ? 'Menampilkan data transaksi yang sesuai dengan kriteria filter.' : 'Daftar seluruh transaksi persewaan garasi dan bukti pelunasan biaya.' }}
            </p>
        </div>
        <div class="text-xs text-gray-400">
            Menampilkan {{ $rents->count() }} dari {{ $rents->total() }} data
        </div>
    </div>

    @if ($rents->isEmpty())
        <div class="py-16 px-6 text-center">
            <div class="text-gray-300 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mx-auto" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>

            <h3 class="text-lg font-semibold text-gray-700">Tidak ada transaksi ditemukan</h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ $isAdmin ? 'Belum ada transaksi persewaan pelanggan yang tercatat.' : 'Anda belum pernah menyewa garasi.' }}
            </p>

            @if (!$isAdmin)
                <a href="{{ route('garages.index') }}"
                    class="inline-block mt-5 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition shadow-sm">
                    Lihat Garasi yang Tersedia
                </a>
            @endif
        </div>
    @else
        <div class="overflow-x-auto py-3 px-6">
            <x-commons.datatable :headers="array_filter([
                ['label' => '#'],
                $isAdmin ? ['label' => 'Penyewa'] : null,
                ['label' => 'Garasi'],
                ['label' => 'Kendaraan'],
                ['label' => 'Periode Sewa'],
                ['label' => 'Jumlah Hari'],
                ['label' => 'Total Biaya'],
                ['label' => 'Aksi', 'class' => 'text-center no-print'],
            ])">
                @foreach ($rents as $rent)
                    <tr @click="openDetail({{ json_encode($rent) }})"
                        class="hover:bg-gray-50/80 transition cursor-pointer group"
                        title="Klik untuk melihat rincian & bukti bayar">
                        <td class="px-6 py-4 text-gray-500">
                            {{ $rents->firstItem() + $loop->index }}
                        </td>

                        {{-- Renter Info (Admin Only) --}}
                        @if ($isAdmin)
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $rent->renter?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $rent->renter?->email ?? '-' }}</div>
                            </td>
                        @endif

                        {{-- Garage Info --}}
                        <td class="px-6 py-4">
                            <div class="font-mono font-semibold text-green-700">{{ $rent->garage?->garage_code ?? '-' }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ match ($rent->garage?->category) {
                                    'Bike' => 'Garasi Sepeda',
                                    'Car' => 'Garasi Mobil',
                                    'Electric Car' => 'Garasi Mobil Listrik',
                                    default => $rent->garage?->category ?? '-',
                                } }}
                            </div>
                        </td>

                        {{-- Vehicle Info --}}
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $rent->vehicle?->brand ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $rent->vehicle?->license_plate ?? 'Tanpa Plat' }}
                            </div>
                        </td>

                        {{-- Rental Period --}}
                        <td class="px-6 py-4 text-gray-700 text-xs">
                            <div>{{ $rent->start_date ? $rent->start_date->format('d M Y') : '-' }}</div>
                            <div class="text-gray-400">s.d.
                                {{ $rent->end_date ? $rent->end_date->format('d M Y') : '-' }}
                            </div>
                        </td>

                        {{-- Total Days --}}
                        <td class="px-6 py-4 text-gray-600 font-medium">
                            <div>{{ $rent->total_day }} hari</div>
                            @php
                                $extensionCount = $rent->rentDetails->where('is_extension', true)->count();
                            @endphp
                            @if ($extensionCount > 0)
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 mt-0.5 rounded text-[10px] font-medium bg-green-50 text-green-700 border border-green-200">
                                    +{{ $extensionCount }} perpanjangan
                                </span>
                            @endif
                        </td>

                        {{-- Total Price (Grand Total) --}}
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">
                                Rp
                                {{ number_format($rent->total_price + $rent->evCharges->sum('price_charge'), 0, ',', '.') }}
                            </div>
                            @if ($rent->evCharges->isNotEmpty())
                                <div class="text-[11px] text-gray-500 mt-0.5 space-y-0.5">
                                    <div>Sewa: Rp {{ number_format($rent->total_price, 0, ',', '.') }}</div>
                                    <div class="text-emerald-600 font-medium">⚡ Cas: Rp
                                        {{ number_format($rent->evCharges->sum('price_charge'), 0, ',', '.') }}</div>
                                </div>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 text-center no-print" @click.stop>
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" @click="openDetail({{ json_encode($rent) }})"
                                    class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition"
                                    title="Lihat Rincian & Bukti Bayar">
                                    Detail
                                </button>

                                @if ($isAdmin && $rent->end_date && $rent->end_date >= today())
                                    <button type="button" @click="openExtend({{ json_encode($rent) }})"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-green-50 border border-green-200 text-green-700 hover:bg-green-600 hover:text-white text-xs font-semibold rounded-lg transition"
                                        title="Perpanjang kontrak sewa garasi ini">
                                        Perpanjang
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-commons.datatable>

            @if ($rents->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $rents->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
