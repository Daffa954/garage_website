@extends('layouts.app')

@section('title', 'Kendaraan Saya - Sewa Garasi')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{ showCreateModal: {{ $errors->any() ? 'true' : 'false' }} }">

        {{-- Page Header --}}
        <div class="max-w-7xl mx-auto px-4 py-8 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Kendaraan Saya</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Kelola data kendaraan yang terdaftar pada akun Anda.
                </p>
            </div>

            <button type="button" @click="showCreateModal = true"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                <span class="mr-2 text-lg">+</span>
                Tambah Kendaraan
            </button>
        </div>

        {{-- Info Total Vehicle --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Kendaraan Terdaftar</p>
                    <h2 class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $vehicles->total() }}
                    </h2>
                    <p class="text-xs text-gray-400 mt-1">Jumlah kendaraan milik Anda yang dapat diparkir</p>
                </div>

                <div class="w-14 h-14 flex items-center justify-center bg-green-50 text-green-600 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M5 17h14l1-5-2-5H6l-2 5 1 5Zm0 0v2m14-2v2M7 17h.01M17 17h.01M4 12h16" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- User Vehicle List --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Kendaraan</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Seluruh kendaraan yang telah terdaftar untuk disewakan garasi.
                </p>
            </div>

            @if ($vehicles->isEmpty())
                <div class="py-16 px-6 text-center">
                    <div class="text-gray-300 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mx-auto" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M5 17h14l1-5-2-5H6l-2 5 1 5Zm0 0v2m14-2v2M7 17h.01M17 17h.01M4 12h16" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-700">
                        Belum ada kendaraan terdaftar
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Silakan daftarkan kendaraan Anda untuk mulai menyewa garasi yang sesuai.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">

                    <x-commons.datatable :headers="[
                        ['label' => '#'],
                        ['label' => 'Merek & Tipe'],
                        ['label' => 'Nomor Plat'],
                        ['label' => 'Kategori'],
                        ['label' => 'Kapasitas Baterai Listrik'],
                        ['label' => 'Status Sewa', 'class' => 'text-center'],
                    ]">

                        @foreach ($vehicles as $vehicle)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $vehicles->firstItem() + $loop->index }}
                                </td>

                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ $vehicle->brand }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $vehicle->license_plate ?? 'Tanpa Plat' }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ match ($vehicle->category) {
                                        'Bike' => 'Sepeda',
                                        'Motor Cycle' => 'Sepeda Motor',
                                        'Car' => 'Mobil (Bensin)',
                                        'Electric Car' => 'Mobil Listrik',
                                        default => $vehicle->category,
                                    } }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $vehicle->total_kwh !== null ? $vehicle->total_kwh . ' kWh' : '-' }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if ($vehicle->is_parked)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Sedang Terparkir
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Bisa Disewakan
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                    </x-commons.datatable>

                    @if ($vehicles->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $vehicles->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
        <x-vehicle.create />

    </div>
@endsection
