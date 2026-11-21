@extends('layouts.app')

@section('title', 'Garages - GarageApp')

@section('content')
    @php
        $role = session('user_role');
        $isAdmin = $role === 'admin';
        $isRenter = $role === 'user';
    @endphp

    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{ showCreateModal: false }">
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
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
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
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-800">
                        {{ $isAdmin ? 'Garage List' : 'Available Garages' }}
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
                                    {{ $loop->iteration }}
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

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $garage->status ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $garage->activeRent?->renter?->name ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if ($garage->status === 'available')
                                        <a 
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                            Rent
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-sm">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-commons.datatable>
                @endif
            </div>
        @endif

        {{-- Create Garage Modal --}}
        @if ($isAdmin)
            <x-garage.create-modal />
        @endif
    </div>
@endsection
