@props([
    'rents',
    'isAdmin' => false,
])

{{-- Rental Transactions List --}}
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">
                {{ $isAdmin ? 'Rental Transactions & Report' : 'My Booking History & Details' }}
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ request()->anyFilled(['start_date', 'end_date', 'garage_type', 'renter_id']) ? 'Showing transactions matching the applied filter criteria.' : 'List of all garage rental transactions and payment receipts.' }}
            </p>
        </div>
        <div class="text-xs text-gray-400">
            Showing {{ $rents->count() }} of {{ $rents->total() }} entries
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

            <h3 class="text-lg font-semibold text-gray-700">No rentals found</h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ $isAdmin ? 'No customer rental transactions have been recorded yet.' : "You haven't rented any garage yet." }}
            </p>

            @if (!$isAdmin)
                <a href="{{ route('garages.index') }}"
                    class="inline-block mt-5 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                    Browse Available Garages
                </a>
            @endif
        </div>
    @else
        <div class="overflow-x-auto py-3 px-6">
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
                ['label' => 'Actions', 'class' => 'text-center no-print'],
            ])">
                @foreach ($rents as $rent)
                    <tr @click="openDetail({{ json_encode($rent) }})"
                        class="hover:bg-gray-50/80 transition cursor-pointer group"
                        title="Click to view details & receipts">
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
                            <div class="font-medium text-gray-900">{{ $rent->garage?->name ?? '-' }}</div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span
                                    class="font-mono text-xs text-green-700">{{ $rent->garage?->garage_code ?? '-' }}</span>
                                <span class="text-xs text-gray-400">&bull;</span>
                                <span class="text-xs text-gray-500">{{ $rent->garage?->category ?? '-' }}</span>
                            </div>
                        </td>

                        {{-- Vehicle Info --}}
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $rent->vehicle?->brand ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $rent->vehicle?->license_plate ?? 'No Plate' }}
                            </div>
                        </td>

                        {{-- Rental Period --}}
                        <td class="px-6 py-4 text-gray-700 text-xs">
                            <div>{{ $rent->start_date ? $rent->start_date->format('d M Y') : '-' }}</div>
                            <div class="text-gray-400">to
                                {{ $rent->end_date ? $rent->end_date->format('d M Y') : '-' }}
                            </div>
                        </td>

                        {{-- Total Days --}}
                        <td class="px-6 py-4 text-gray-600 font-medium">
                            <div>{{ $rent->total_day }} days</div>
                            @php
                                $extensionCount = $rent->rentDetails->where('is_extension', true)->count();
                            @endphp
                            @if ($extensionCount > 0)
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 mt-0.5 rounded text-[10px] font-medium bg-green-50 text-green-700 border border-green-200">
                                    +{{ $extensionCount }} extension{{ $extensionCount > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </td>

                        {{-- Total Price (Grand Total) --}}
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">
                                Rp {{ number_format($rent->total_price + $rent->evCharges->sum('price_charge'), 0, ',', '.') }}
                            </div>
                            @if ($rent->evCharges->isNotEmpty())
                                <div class="text-[11px] text-gray-500 mt-0.5 space-y-0.5">
                                    <div>Rent: Rp {{ number_format($rent->total_price, 0, ',', '.') }}</div>
                                    <div class="text-emerald-600 font-medium">⚡ EV: Rp {{ number_format($rent->evCharges->sum('price_charge'), 0, ',', '.') }}</div>
                                </div>
                            @endif
                        </td>

                        {{-- Payment Receipt Link --}}
                        <td class="px-6 py-4 text-xs">
                            @php
                                $receiptUrl = $rent->payments->first()?->url_payment_receipt;
                                $evReceiptUrl = $rent->evCharges->first()?->url_payment_receipt;
                            @endphp

                            <div class="flex flex-col gap-1">
                                @if ($receiptUrl)
                                    <a href="{{ $receiptUrl }}" target="_blank" @click.stop
                                        class="inline-flex items-center gap-1 text-green-600 hover:text-green-800 font-medium hover:underline">
                                        <span>Rent Receipt</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @endif

                                @if ($evReceiptUrl && $evReceiptUrl !== $receiptUrl)
                                    <a href="{{ $evReceiptUrl }}" target="_blank" @click.stop
                                        class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 font-medium hover:underline">
                                        <span>⚡ EV Receipt</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @endif

                                @if (!$receiptUrl && !$evReceiptUrl)
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </td>

                        {{-- Status Badge --}}
                        <td class="px-6 py-4 text-center">
                            @if ($rent->end_date && $rent->end_date >= today())
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    Active
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    Completed
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 text-center no-print" @click.stop>
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" @click="openDetail({{ json_encode($rent) }})"
                                    class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition"
                                    title="View Breakdown & Receipts">
                                    Details
                                </button>

                                @if ($isAdmin && $rent->end_date && $rent->end_date >= today())
                                    <button type="button" @click="openExtend({{ json_encode($rent) }})"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-green-50 border border-green-200 text-green-700 hover:bg-green-600 hover:text-white text-xs font-semibold rounded-lg transition"
                                        title="Extend this rental contract">
                                        Extend
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-commons.datatable>

            @if ($rents->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 no-print">
                    {{ $rents->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
