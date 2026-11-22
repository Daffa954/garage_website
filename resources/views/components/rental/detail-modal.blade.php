 @php
        $role = session('user_role');
        $isAdmin = $role === 'admin';
        $isRenter = $role === 'user';
    @endphp
<div
    x-show="showDetailModal"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6"
    @click.self="showDetailModal = false"
    @keydown.escape.window="showDetailModal = false"
>
    <div
        x-show="showDetailModal"
        x-transition
        class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden"
    >
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                    #<span x-text="selectedRent?.id"></span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">
                        Rental Booking Details
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Created on <span x-text="formatDate(selectedRent?.created_at)"></span>
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="showDetailModal = false"
                class="text-gray-400 hover:text-gray-600 rounded-lg p-1 hover:bg-gray-100 transition text-2xl leading-none"
            >
                &times;
            </button>
        </div>

        {{-- Modal Body (Scrollable) --}}
        <div class="p-6 overflow-y-auto space-y-6">

            {{-- 1. Summary Cards Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Garage Card --}}
                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Garage</span>
                    <h4 class="font-bold text-gray-800 mt-1" x-text="selectedRent?.garage?.name"></h4>
                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                        <span class="font-mono text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded" x-text="selectedRent?.garage?.garage_code"></span>
                        <span x-text="selectedRent?.garage?.category"></span>
                    </div>
                </div>

                {{-- Vehicle Card --}}
                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Vehicle</span>
                    <h4 class="font-bold text-gray-800 mt-1" x-text="selectedRent?.vehicle?.brand"></h4>
                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                        <span class="font-semibold text-gray-700" x-text="selectedRent?.vehicle?.license_plate || 'No Plate'"></span>
                        <span x-text="selectedRent?.vehicle?.category"></span>
                    </div>
                </div>

                {{-- Renter Card --}}
                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Renter</span>
                    <h4 class="font-bold text-gray-800 mt-1" x-text="selectedRent?.renter?.name"></h4>
                    <p class="text-xs text-gray-500 mt-1 truncate" x-text="selectedRent?.renter?.email"></p>
                </div>
            </div>

            {{-- 2. Rental Duration & Overall Billing Summary --}}
            <div class="p-4 rounded-xl bg-gradient-to-r from-blue-50/80 to-indigo-50/80 border border-blue-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-blue-700">Contract Duration</span>
                    <div class="text-sm font-bold text-gray-900 mt-1">
                        <span x-text="formatDate(selectedRent?.start_date)"></span>
                        <span class="text-gray-400 mx-1">→</span>
                        <span x-text="formatDate(selectedRent?.end_date)"></span>
                        <span class="ml-1 text-xs font-medium text-blue-600" x-text="'(' + (selectedRent?.total_day || 0) + ' days)'"></span>
                    </div>
                </div>

                {{-- Billing Breakdown --}}
                <div class="w-full sm:w-auto text-left sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-blue-200/60">
                    <template x-if="evChargeTotal > 0">
                        <div class="space-y-0.5 mb-1.5 text-xs">
                            <div class="flex sm:justify-end gap-2 text-gray-600">
                                <span>Garage Rent:</span>
                                <span class="font-semibold text-gray-800" x-text="formatCurrency(selectedRent?.total_price)"></span>
                            </div>
                            <div class="flex sm:justify-end gap-2 text-emerald-700">
                                <span>⚡ EV Charge:</span>
                                <span class="font-semibold text-emerald-700" x-text="formatCurrency(evChargeTotal)"></span>
                            </div>
                        </div>
                    </template>

                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-blue-800">Total Semua Transaksi</span>
                        <div class="text-xl font-black text-indigo-950 mt-0.5" x-text="formatCurrency(grandTotal)"></div>
                    </div>
                </div>
            </div>

            {{-- Optional Note --}}
            <template x-if="selectedRent?.note">
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900">
                    <span class="font-bold">Booking Note:</span>
                    <span x-text="selectedRent?.note"></span>
                </div>
            </template>

            {{-- 3. Rental Details Breakdown (rent_details) --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        Period Breakdown & Details (<span x-text="(selectedRent?.rent_details || selectedRent?.rentDetails || []).length"></span>)
                    </h3>
                    <span class="text-xs text-gray-400">Rules: Monthly = 25× Daily rate</span>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Date Range</th>
                                <th class="px-4 py-3">Days</th>
                                <th class="px-4 py-3 text-right">Period Price</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(detail, idx) in (selectedRent?.rent_details || selectedRent?.rentDetails || [])" :key="detail.id || idx">
                                <tr class="hover:bg-gray-50/70 transition">
                                    <td class="px-4 py-3 text-gray-400 font-medium" x-text="idx + 1"></td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="detail.type === 'monthly' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold capitalize"
                                            x-text="detail.type"
                                        ></span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        <span x-text="formatDate(detail.start_date)"></span>
                                        <span class="text-gray-400 mx-1">to</span>
                                        <span x-text="formatDate(detail.end_date)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 font-semibold" x-text="detail.total_day + ' days'"></td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900" x-text="formatCurrency(detail.total_price)"></td>
                                    <td class="px-4 py-3 text-center">
                                        <template x-if="detail.is_extension">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-700 border border-purple-200">
                                                Extension
                                            </span>
                                        </template>
                                        <template x-if="!detail.is_extension">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600">
                                                Initial Booking
                                            </span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="!(selectedRent?.rent_details || selectedRent?.rentDetails || []).length">
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                        No period details recorded.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Payment Receipts (rent_payments) --}}
            <div>
                <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                     Payment Receipts (<span x-text="(selectedRent?.payments || []).length"></span>)
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="(payment, pIdx) in (selectedRent?.payments || [])" :key="payment.id || pIdx">
                        <div class="p-3.5 rounded-xl border border-gray-200 bg-white shadow-sm flex items-start justify-between gap-3 hover:border-gray-300 transition">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">
                                        Receipt #<span x-text="pIdx + 1"></span>
                                    </span>
                                    <span class="text-[11px] text-gray-400" x-text="formatDate(payment.created_at)"></span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1 truncate" x-text="payment.remarks || 'Payment receipt transfer proof'"></p>
                            </div>

                            <a
                                :href="payment.url_payment_receipt"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-lg transition shrink-0"
                            >
                                <span>Open</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </template>
                </div>

                <template x-if="!(selectedRent?.payments || []).length">
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-center text-xs text-gray-400">
                        No payment receipts recorded for this booking.
                    </div>
                </template>
            </div>

            {{-- 5. EV Charges & Proof (if applicable) --}}
            <template x-if="(selectedRent?.ev_charges || selectedRent?.evCharges || []).length > 0">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-emerald-800 flex items-center gap-2">
                            <span>⚡</span> EV Charging Transactions & Proof
                        </h3>
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full" x-text="'Total: ' + formatCurrency(evChargeTotal)"></span>
                    </div>

                    <div class="border border-emerald-200 bg-emerald-50/40 rounded-xl p-4 divide-y divide-emerald-100 space-y-3">
                        <template x-for="(charge, cIdx) in (selectedRent?.ev_charges || selectedRent?.evCharges || [])" :key="charge.id || cIdx">
                            <div class="pt-3 first:pt-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-emerald-900 text-sm">
                                            Battery Level: <span class="text-emerald-700" x-text="Number(charge.current_battery).toFixed(0) + '%'"></span>
                                        </span>
                                        <span class="text-gray-400">&bull;</span>
                                        <span class="text-gray-500" x-text="formatDate(charge.created_at)"></span>
                                    </div>
                                    <p class="text-emerald-800 text-xs mt-1" x-text="charge.note || 'Electric vehicle charging session.'"></p>
                                </div>

                                <div class="flex items-center gap-3 sm:text-right shrink-0">
                                    <div class="text-left sm:text-right">
                                        <span class="text-[10px] text-gray-500 block uppercase font-medium">Charge Cost</span>
                                        <div class="font-extrabold text-emerald-700 text-sm" x-text="formatCurrency(charge.price_charge)"></div>
                                    </div>

                                    {{-- EV Receipt Button --}}
                                    <template x-if="charge.url_payment_receipt">
                                        <a
                                            :href="charge.url_payment_receipt"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-800 bg-emerald-100 hover:bg-emerald-700 hover:text-white rounded-lg transition shadow-sm"
                                        >
                                            <span>Bukti Cas</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

        </div>

        {{-- Modal Footer --}}
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50/70">
            <button
                type="button"
                @click="showDetailModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
                Close
            </button>

            @if ($isAdmin)
                <template x-if="selectedRent?.end_date && new Date(selectedRent.end_date.split('T')[0]) >= new Date(new Date().toDateString())">
                    <button
                        type="button"
                        @click="showDetailModal = false; openExtend(selectedRent)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition"
                    >
                        <span>+ Extend Rental Contract</span>
                    </button>
                </template>
            @endif
        </div>
    </div>
</div>
