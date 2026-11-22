@extends('layouts.app')

@section('title', 'My Vehicles - Garage Rental')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8" x-data="{ showCreateModal: {{ $errors->any() ? 'true' : 'false' }} }">

        {{-- Page Header --}}
        <div class="max-w-7xl mx-auto px-4 py-8 flex justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">My Vehicles</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Manage your registered vehicles.
                </p>
            </div>

            <button type="button" @click="showCreateModal = true"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <span class="mr-2 text-lg">+</span>
                Add Vehicle
            </button>
        </div>
        {{-- Info Total Vehicle --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Registered Vehicles</p>
                    <h2 class="text-3xl font-bold text-gray-800 mt-2">
                        {{ $vehicles->total() }}
                    </h2>
                    <p class="text-xs text-gray-400 mt-1">Vehicles registered to your account</p>
                </div>

                <div class="w-14 h-14 flex items-center justify-center bg-blue-50 text-blue-600 rounded-xl">
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
                <h2 class="text-lg font-semibold text-gray-800">Vehicle List</h2>
                <p class="text-sm text-gray-500 mt-1">
                    All vehicles registered under your account.
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
                        No vehicles found
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        You haven't registered any vehicles yet.
                    </p>

                   
                </div>
            @else
                <div class="overflow-x-auto">

                    <x-commons.datatable :headers="[
                        ['label' => '#'],
                        ['label' => 'Brand'],
                        ['label' => 'License Plate'],
                        ['label' => 'Category'],
                        ['label' => 'Battery Capacity'],
                        ['label' => 'Actions', 'class' => 'text-center'],
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
                                    {{ $vehicle->license_plate ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $vehicle->category ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-gray-600">
                                    {{ $vehicle->total_kwh !== null ? $vehicle->total_kwh . ' kWh' : '-' }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    {{ $vehicle->owner_id ?? '-' }}
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
