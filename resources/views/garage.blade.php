@extends('layouts.app')

@section('title', 'Garages - GarageApp')

@section('content')
    @php
        $role = session('user_role');
        $isAdmin = $role === 'admin';
        $isRenter = $role === 'user';
    @endphp

    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{ showCreateModal: false, showRentModal: false, selectedGarage: null }">
        {{-- Page Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Garages</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $isAdmin ? 'Manage your garage facilities.' : 'Find an available garage for your vehicle.' }}
                </p>
            </div>

            @if ($isAdmin)
                <button type="button" @click="showCreateModal = true"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    <span class="mr-2 text-lg">+</span>
                    Add Garage
                </button>
            @endif
        </div>

        {{-- Role Access Check --}}
        @if (!$isAdmin && !$isRenter)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                You do not have permission to access this page.
            </div>
        @else
            {{-- Garage Content --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden px-6 py-3">
                <div class="px-6 py-4">
                    <h2 class="font-semibold text-gray-800">
                        {{ $isAdmin ? 'Garage List' : 'All Garages' }}
                    </h2>
                </div>

                @if ($garages->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-gray-500 text-sm">No garages found.</p>
                    </div>
                @else
                    <x-commons.datatable :headers="[
                        ['label' => '#'],
                        ['label' => 'Garage Name'],
                        ['label' => 'Category'],
                        ['label' => 'Code'],
                        ['label' => 'Price'],
                        ['label' => 'Price/KWH'],
                        ['label' => 'Status'],
                        ['label' => 'Rent By'],
                        ['label' => 'Actions', 'class' => 'text-center'],
                    ]">
                        @foreach ($garages as $garage)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $garages->firstItem() + $loop->index }}
                                </td>

                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ $garage->name }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ $garage->category }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $garage->garage_code }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    Rp {{ number_format($garage->price, 0, ',', '.') }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $garage->price_kwh !== null ? 'Rp ' . number_format($garage->price_kwh, 0, ',', '.') : '-' }}
                                </td>

                                <td class="px-6 py-4">
                                    @if ($garage->status === 'available')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Available
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                            Occupied
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $garage->status !== 'available' ? ($garage->activeRent?->renter?->name ?? '-') : '-' }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if ($garage->status === 'available')
                                        <button type="button"
                                            @click="selectedGarage = {{ json_encode($garage) }}; showRentModal = true"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                            Rent
                                        </button>
                                    @else
                                        <span
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-gray-400 bg-gray-50 border border-gray-200 cursor-not-allowed">
                                            Unavailable
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-commons.datatable>

                    @if ($garages->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $garages->links() }}
                        </div>
                    @endif
                @endif
            </div>
        @endif

        {{-- Create Garage Modal --}}
        @if ($isAdmin)
            <x-garage.create-modal />
        @endif

        {{-- Rent Garage Modal --}}
        <x-garage.rent-modal :vehicles="$vehicles" />
    </div>
@endsection
