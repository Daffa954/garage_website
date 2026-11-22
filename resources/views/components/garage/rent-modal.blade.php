@props([
    'vehicles' => collect(),
])

<div
    x-data="{
        allVehicles: @js($vehicles),
        selectedVehicleId: '',
        startDate: '{{ date('Y-m-d') }}',
        endDate: '{{ date('Y-m-d', strtotime('+1 day')) }}',
        currentBattery: '',
        get isElectric() {
            return selectedGarage && selectedGarage.category === 'Electric Car';
        },
        get matchingVehicles() {
            if (!selectedGarage) return [];
            return this.allVehicles.filter(v => {
                if (v.category === selectedGarage.category) return true;
                // Electric Car can also park in a regular Car garage (no charging)
                if (v.category === 'Electric Car' && selectedGarage.category === 'Car') return true;
                return false;
            });
        }
    }"
    x-init="$watch('selectedGarage', () => { selectedVehicleId = ''; })"
    x-show="showRentModal"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6"
    @click.self="showRentModal = false"
    @keydown.escape.window="showRentModal = false"
>
    <div
        x-show="showRentModal"
        x-transition
        class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
    >
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Rent Garage
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Book <span class="font-medium text-gray-700" x-text="selectedGarage ? selectedGarage.name : ''"></span> (<span class="font-mono text-xs text-indigo-600" x-text="selectedGarage ? selectedGarage.garage_code : ''"></span>)
                </p>
            </div>

            <button
                type="button"
                @click="showRentModal = false"
                class="text-gray-400 hover:text-gray-600 text-2xl"
            >
                &times;
            </button>
        </div>

        {{-- Modal Form --}}
        <form action="{{ route('api.rent.store') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            <input type="hidden" name="garage_id" :value="selectedGarage ? selectedGarage.id : ''">
            <input type="hidden" name="type" :value="selectedGarage ? selectedGarage.category : ''">

            {{-- Garage Info Summary Box --}}
            <div class="mb-5 rounded-lg bg-gray-50 p-4 border border-gray-200 text-sm">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-500">Category:</span>
                    <span class="font-semibold text-gray-800" x-text="selectedGarage ? selectedGarage.category : '-'"></span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-500">Daily Rate:</span>
                    <span class="font-semibold text-blue-600" x-text="selectedGarage ? 'Rp ' + Number(selectedGarage.price).toLocaleString('id-ID') : '-'"></span>
                </div>
                <template x-if="isElectric && selectedGarage?.price_kwh">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">EV Rate / kWh:</span>
                        <span class="font-semibold text-emerald-600" x-text="'Rp ' + Number(selectedGarage.price_kwh).toLocaleString('id-ID')"></span>
                    </div>
                </template>
            </div>

            {{-- Vehicle Selection (Filtered by Garage Category) --}}
            <div class="mb-5">
                <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Your Vehicle (<span class="text-indigo-600 font-semibold" x-text="selectedGarage ? selectedGarage.category : ''"></span>)
                </label>

                {{-- Alert when user has no matching vehicles for the selected garage category --}}
                <div x-show="matchingVehicles.length === 0" class="p-3.5 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    <span>You do not have any available vehicle in the <strong x-text="selectedGarage ? selectedGarage.category : ''"></strong> category.</span>
                    <a href="{{ route('vehicles.index') }}" class="underline font-semibold hover:text-amber-900 block mt-1">
                        + Register a matching vehicle
                    </a>
                </div>

                {{-- Dropdown showing only matching vehicles --}}
                <div x-show="matchingVehicles.length > 0">
                    <select
                        name="vehicle_id"
                        id="vehicle_id"
                        x-model="selectedVehicleId"
                        ::required="matchingVehicles.length > 0"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
                    >
                        <option value="">-- Choose Vehicle --</option>
                        <template x-for="v in matchingVehicles" :key="v.id">
                            <option :value="v.id" x-text="`${v.brand} (${v.license_plate ? v.license_plate : 'No Plate'})${selectedGarage?.category === 'Car' && v.category === 'Electric Car' ? ' - EV (No Charging)' : ''}`"></option>
                        </template>
                    </select>
                </div>

                @error('vehicle_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Rental Dates (Grid 2 Cols) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date
                    </label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        x-model="startDate"
                        min="{{ date('Y-m-d') }}"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
                    >
                    @error('start_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                        End Date
                    </label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        x-model="endDate"
                        :min="startDate"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
                    >
                    @error('end_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- EV Charging Section (Conditionally Shown for Electric Car Garages) --}}
            <template x-if="isElectric">
                <div class="mb-5 p-4 rounded-lg bg-emerald-50 border border-emerald-200">
                    <h3 class="text-sm font-semibold text-emerald-800 mb-3 flex items-center gap-2">
                        <span>⚡</span> EV Charging Details
                    </h3>

                    <div class="mb-3">
                        <label for="current_battery" class="block text-xs font-medium text-emerald-900 mb-1">
                            Current Battery Level (%) <span class="text-gray-400 font-normal">(100% = full, no charging fee)</span>
                        </label>
                        <input
                            type="number"
                            name="ev_charge[current_battery]"
                            id="current_battery"
                            x-model="currentBattery"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="e.g. 45"
                            class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-emerald-500/20 transition"
                        >
                        <div x-show="Number(currentBattery) >= 100" class="mt-2 text-xs text-emerald-800 bg-emerald-100/80 px-2.5 py-1.5 rounded-md flex items-center gap-1.5">
                            <span>✓</span>
                            <span>Battery is full (100%). No charging is needed and no charging fee will be billed.</span>
                        </div>
                        @error('ev_charge.current_battery')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="ev_note" class="block text-xs font-medium text-emerald-900 mb-1">
                            Charging Note (Optional)
                        </label>
                        <input
                            type="text"
                            name="ev_charge[note]"
                            id="ev_note"
                            placeholder="e.g. Charge to 100% before 8 AM"
                            class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-emerald-500/20 transition"
                        >
                    </div>
                </div>
            </template>

            {{-- Payment Receipt Upload --}}
            <div class="mb-5">
                <label for="payment_receipt" class="block text-sm font-medium text-gray-700 mb-2">
                    Payment Receipt (Transfer Proof)
                </label>
                <input
                    type="file"
                    name="payment_receipt"
                    id="payment_receipt"
                    accept="image/*,.pdf"
                    required
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition border border-gray-300 rounded-lg p-1.5"
                >
                @error('payment_receipt')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes / Remarks --}}
            <div class="mb-6">
                <label for="rent_note" class="block text-sm font-medium text-gray-700 mb-2">
                    Note / Remarks (Optional)
                </label>
                <textarea
                    name="note"
                    id="rent_note"
                    rows="2"
                    placeholder="Any special request or note..."
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
                ></textarea>
                @error('note')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button
                    type="button"
                    @click="showRentModal = false"
                    class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    :disabled="matchingVehicles.length === 0"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    Confirm & Rent
                </button>
            </div>
        </form>
    </div>
</div>
