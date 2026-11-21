@extends('layouts.app')

@section('title', $isAdmin ? 'All Rentals - GarageApp' : 'My Rentals - GarageApp')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $isAdmin ? 'All Rental Transactions' : 'My Rentals' }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $isAdmin ? 'Monitor and manage all customer garage bookings.' : 'Track your active and past garage bookings.' }}
                </p>
            </div>

            @if (!$isAdmin)
                <a href="{{ route('garages.index') }}"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <span class="mr-2 text-lg">+</span>
                    Rent a Garage
                </a>
            @endif
        </div>

        {{-- Rental Stats Overview --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">
                    {{ $isAdmin ? 'Total Bookings' : 'My Total Bookings' }}
                </p>
                <h2 class="text-3xl font-bold text-gray-800 mt-2">
                    {{ $rents->count() }}
                </h2>
                <p class="text-xs text-gray-400 mt-1">Total recorded rental transactions</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm font-medium text-emerald-600">Active Rentals</p>
                <h2 class="text-3xl font-bold text-emerald-600 mt-2">
                    {{ $rents->filter(fn($r) => $r->end_date && $r->end_date >= today())->count() }}
                </h2>
                <p class="text-xs text-gray-400 mt-1">Currently ongoing garage leases</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Completed Rentals</p>
                <h2 class="text-3xl font-bold text-gray-700 mt-2">
                    {{ $rents->filter(fn($r) => $r->end_date && $r->end_date < today())->count() }}
                </h2>
                <p class="text-xs text-gray-400 mt-1">Expired or completed leases</p>
            </div>
        </div>

        {{-- Rental Transactions List --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        {{ $isAdmin ? 'All Customer Bookings' : 'My Booking History' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Detailed breakdown of garage rental periods and payment receipts.
                    </p>
                </div>
            </div>

            @if ($rents->isEmpty())
                <div class="py-16 px-6 text-center">
                    <div class="text-gray-300 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-700">No rentals found</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $isAdmin ? 'No customer rental transactions have been recorded yet.' : "You haven't rented any garage yet." }}
                    </p>

                    @if (!$isAdmin)
                        <a href="{{ route('garages.index') }}"
                            class="inline-block mt-5 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                            Browse Available Garages
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <x-commons.datatable :headers="array_filter([
                        ['label' => '#'],
                        $isAdmin ? ['label' => 'Renter'] : null,
                        ['label' => 'Garage'],
                        ['label' => 'Vehicle'],
                        ['label' => 'Rental Period'],
                        ['label' => 'Days'],
                        ['label' => 'Total Price'],
                        ['label' => 'Receipt'],
                        ['label' => 'Status', 'class' => 'text-center'],
                    ])">
                        @foreach ($rents as $rent)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $loop->iteration }}
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
                                    <div class="font-medium text-gray-900">{{ $rent->garage?->name ?? '-' }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono text-xs text-indigo-600">{{ $rent->garage?->garage_code ?? '-' }}</span>
                                        <span class="text-xs text-gray-400">&bull;</span>
                                        <span class="text-xs text-gray-500">{{ $rent->garage?->category ?? '-' }}</span>
                                    </div>
                                </td>

                                {{-- Vehicle Info --}}
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $rent->vehicle?->brand ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $rent->vehicle?->license_plate ?? 'No Plate' }}</div>
                                </td>

                                {{-- Rental Period --}}
                                <td class="px-6 py-4 text-gray-700 text-xs">
                                    <div>{{ $rent->start_date ? $rent->start_date->format('d M Y') : '-' }}</div>
                                    <div class="text-gray-400">to {{ $rent->end_date ? $rent->end_date->format('d M Y') : '-' }}</div>
                                </td>

                                {{-- Total Days --}}
                                <td class="px-6 py-4 text-gray-600 font-medium">
                                    {{ $rent->total_day }} days
                                </td>

                                {{-- Total Price --}}
                                <td class="px-6 py-4 font-semibold text-gray-900">
                                    Rp {{ number_format($rent->total_price, 0, ',', '.') }}
                                    @if ($rent->evCharges->isNotEmpty())
                                        <span class="block text-[11px] font-normal text-emerald-600 mt-0.5">
                                            ⚡ EV Charge: Rp {{ number_format($rent->evCharges->sum('price_charge'), 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Payment Receipt Link --}}
                                <td class="px-6 py-4 text-xs">
                                    @php
                                        $receiptUrl = $rent->payments->first()?->url_payment_receipt;
                                    @endphp

                                    @if ($receiptUrl)
                                        <a href="{{ $receiptUrl }}" target="_blank"
                                            class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            <span>View</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-6 py-4 text-center">
                                    @if ($rent->end_date && $rent->end_date >= today())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                            Completed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-commons.datatable>
                </div>
            @endif
        </div>

    </div>
@endsection
