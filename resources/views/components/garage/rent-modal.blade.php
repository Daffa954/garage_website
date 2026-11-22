@props([
    'vehicles' => collect(),
])

<div
    x-data="{
        allVehicles: @js($vehicles),
        selectedVehicleId: '',
        rentType: 'daily',
        dailyDays: 1,
        monthlyMonths: 1,
        startDate: '{{ date('Y-m-d') }}',
        currentBattery: '',
        get selectedVehicle() {
            return this.allVehicles.find(v => String(v.id) === String(this.selectedVehicleId)) || null;
        },
        get isElectric() {
            return selectedGarage && selectedGarage.category === 'Electric Car';
        },
        get matchingVehicles() {
            if (!selectedGarage) return [];
            return this.allVehicles.filter(v => {
                if (v.category === selectedGarage.category) return true;
                // Both Bike and Motor Cycle park in Bike (Sepeda) garage
                if ((v.category === 'Bike' || v.category === 'Motor Cycle') && selectedGarage.category === 'Bike') return true;
                // Electric Car can also park in regular Car garage (without charging)
                if (v.category === 'Electric Car' && selectedGarage.category === 'Car') return true;
                return false;
            });
        },
        get occupiedUntilDate() {
            if (this.selectedGarage && this.selectedGarage.active_rent && this.selectedGarage.active_rent.end_date) {
                const end = this.selectedGarage.active_rent.end_date.split('T')[0];
                const today = '{{ date('Y-m-d') }}';
                return end > today ? end : null;
            }
            return null;
        },
        get minStartDate() {
            const today = '{{ date('Y-m-d') }}';
            return this.occupiedUntilDate || today;
        },
        formatLocalYMD(d) {
            const pad = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        },
        get calculatedEndDate() {
            const baseStr = this.startDate || this.minStartDate;
            const parts = baseStr.split('-');
            const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
            if (this.rentType === 'daily') {
                const days = Math.min(30, Math.max(1, parseInt(this.dailyDays) || 1));
                d.setDate(d.getDate() + days);
            } else {
                const months = Math.max(1, parseInt(this.monthlyMonths) || 1);
                d.setMonth(d.getMonth() + months);
            }
            return this.formatLocalYMD(d);
        },
        get totalDays() {
            const start = new Date((this.startDate || this.minStartDate) + 'T00:00:00');
            const end = new Date(this.calculatedEndDate + 'T00:00:00');
            return Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24)));
        },
        get rentalPrice() {
            if (!selectedGarage) return 0;
            const price = Number(selectedGarage.price);
            if (this.rentType === 'daily') {
                const days = Math.min(30, Math.max(1, parseInt(this.dailyDays) || 1));
                return days * price;
            } else {
                const months = Math.max(1, parseInt(this.monthlyMonths) || 1);
                return months * (25 * price);
            }
        },
        get evChargingPrice() {
            if (!this.isElectric || !selectedGarage?.price_kwh) return 0;
            if (!this.selectedVehicle || !this.selectedVehicle.total_kwh) return 0;
            const battery = parseFloat(this.currentBattery);
            if (isNaN(battery) || battery >= 100 || battery < 0) return 0;
            const kwhNeeded = ((100 - battery) / 100) * Number(this.selectedVehicle.total_kwh);
            return Math.round(kwhNeeded * Number(selectedGarage.price_kwh));
        },
        get grandTotal() {
            return this.rentalPrice + this.evChargingPrice;
        }
    }"
    x-init="
        $watch('selectedGarage', (garage) => {
            selectedVehicleId = '';
            const today = '{{ date('Y-m-d') }}';
            let nextStart = today;
            if (garage && garage.active_rent && garage.active_rent.end_date) {
                const occupiedEnd = garage.active_rent.end_date.split('T')[0];
                if (occupiedEnd > today) {
                    nextStart = occupiedEnd;
                }
            }
            startDate = nextStart;
            rentType = 'daily';
            dailyDays = 1;
            monthlyMonths = 1;
            currentBattery = '';
        });
    "
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
                    Sewa Garasi Baru
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Kode: <span class="font-mono font-bold text-green-700" x-text="selectedGarage ? selectedGarage.garage_code : ''"></span> &bull;
                    <span class="text-gray-700 font-medium" x-text="selectedGarage ? (selectedGarage.category === 'Bike' ? 'Garasi Sepeda' : (selectedGarage.category === 'Car' ? 'Garasi Mobil' : 'Garasi Mobil Listrik')) : ''"></span>
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
            <input type="hidden" name="start_date" :value="startDate">
            <input type="hidden" name="end_date" :value="calculatedEndDate">

            {{-- Garage Info Summary Box --}}
            <div class="mb-5 rounded-lg bg-gray-50 p-4 border border-gray-200 text-sm">
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-gray-500">Tipe Garasi:</span>
                    <span class="font-semibold text-gray-800" x-text="selectedGarage ? (selectedGarage.category === 'Bike' ? 'Sepeda & Sepeda Motor' : (selectedGarage.category === 'Car' ? 'Mobil (Bensin)' : 'Mobil Listrik')) : '-'"></span>
                </div>
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-gray-500">Tarif Harian:</span>
                    <span class="font-semibold text-green-600" x-text="selectedGarage ? 'Rp ' + Number(selectedGarage.price).toLocaleString('id-ID') + ' / hari' : '-'"></span>
                </div>
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-gray-500">Tarif Bulanan (25x):</span>
                    <span class="font-semibold text-green-700" x-text="selectedGarage ? 'Rp ' + Number(selectedGarage.price * 25).toLocaleString('id-ID') + ' / bulan' : '-'"></span>
                </div>
                <template x-if="isElectric && selectedGarage?.price_kwh">
                    <div class="flex justify-between items-center pt-1.5 border-t border-gray-200">
                        <span class="text-gray-500">Tarif Pengisian Listrik:</span>
                        <span class="font-semibold text-emerald-600" x-text="'Rp ' + Number(selectedGarage.price_kwh).toLocaleString('id-ID') + ' / kWh'"></span>
                    </div>
                </template>
            </div>

            {{-- Occupied Notice Banner --}}
            <template x-if="occupiedUntilDate">
                <div class="mb-5 p-3.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-start gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <div class="font-semibold text-amber-900">Garasi sedang dipinjam saat ini</div>
                        <div class="mt-0.5">
                            Garasi ini sedang disewa sampai <strong x-text="new Date(occupiedUntilDate).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })"></strong>. Tanggal sewa otomatis dimulai dari tanggal tersebut.
                        </div>
                    </div>
                </div>
            </template>

            {{-- Vehicle Selection (Filtered by Garage Category) --}}
            <div class="mb-5">
                <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Pilih Kendaraan Anda (<span class="text-green-600 font-semibold" x-text="selectedGarage ? (selectedGarage.category === 'Bike' ? 'Sepeda / Motor' : (selectedGarage.category === 'Car' ? 'Mobil' : 'Mobil Listrik')) : ''"></span>)
                </label>

                {{-- Alert when user has no matching vehicles for the selected garage category --}}
                <div x-show="matchingVehicles.length === 0" class="p-3.5 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    <span>Anda belum memiliki kendaraan yang cocok untuk tipe <strong x-text="selectedGarage ? (selectedGarage.category === 'Bike' ? 'Sepeda / Motor' : (selectedGarage.category === 'Car' ? 'Mobil' : 'Mobil Listrik')) : ''"></strong>.</span>
                    <a href="{{ route('vehicles.index') }}" class="underline font-semibold hover:text-amber-900 block mt-1">
                        + Daftarkan kendaraan yang sesuai
                    </a>
                </div>

                {{-- Dropdown showing only matching vehicles --}}
                <div x-show="matchingVehicles.length > 0">
                    <select
                        name="vehicle_id"
                        id="vehicle_id"
                        x-model="selectedVehicleId"
                        ::required="matchingVehicles.length > 0"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition"
                    >
                        <option value="">-- Pilih Kendaraan --</option>
                        <template x-for="v in matchingVehicles" :key="v.id">
                            <option :value="v.id" x-text="`${v.brand} (${v.license_plate ? v.license_plate : 'Tanpa Plat'})${selectedGarage?.category === 'Car' && v.category === 'Electric Car' ? ' - Mobil Listrik (Tanpa Charge)' : ''}`"></option>
                        </template>
                    </select>
                </div>

                @error('vehicle_id')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipe Sewa: Harian / Bulanan --}}
            <div class="mb-5">
                <label for="rent_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipe Sewa
                </label>
                <select
                    id="rent_type"
                    name="type"
                    x-model="rentType"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition"
                >
                    <option value="daily">Harian</option>
                    <option value="monthly">Bulanan</option>
                </select>
            </div>

            {{-- Durasi Sewa (Jumlah Hari / Bulan) --}}
            <div class="mb-5">
                <template x-if="rentType === 'daily'">
                    <div>
                        <label for="daily_days" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Jumlah Hari Sewa <span class="text-xs text-gray-500">(1 - 30 hari)</span>
                        </label>
                        <input
                            type="number"
                            id="daily_days"
                            x-model.number="dailyDays"
                            min="1"
                            max="30"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition"
                        >
                    </div>
                </template>

                <template x-if="rentType === 'monthly'">
                    <div>
                        <label for="monthly_months" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Jumlah Bulan Sewa
                        </label>
                        <input
                            type="number"
                            id="monthly_months"
                            x-model.number="monthlyMonths"
                            min="1"
                            max="12"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition"
                        >
                    </div>
                </template>
            </div>

            {{-- Rental Dates (Otomatis) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">
                        Tanggal Mulai Sewa (Hari Ini)
                    </label>
                    <input
                        type="date"
                        :value="startDate"
                        readonly
                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-700 cursor-not-allowed outline-none"
                    >
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">
                        Tanggal Selesai (Otomatis)
                    </label>
                    <input
                        type="date"
                        :value="calculatedEndDate"
                        readonly
                        class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-700 cursor-not-allowed outline-none font-medium"
                    >
                </div>
            </div>

            {{-- EV Charging Section (Conditionally Shown for Electric Car Garages) --}}
            <template x-if="isElectric">
                <div class="mb-5 p-4 rounded-lg bg-emerald-50 border border-emerald-200">
                    <h3 class="text-sm font-semibold text-emerald-800 mb-3 flex items-center gap-2">
                        <span>⚡</span> Detail Pengisian Daya Listrik (Wajib Isi Penuh)
                    </h3>

                    <div class="mb-3">
                        <label for="current_battery" class="block text-xs font-medium text-emerald-900 mb-1">
                            Kapasitas Baterai Saat Ini (%) <span class="text-gray-400 font-normal">(100% = penuh, tanpa biaya listrik)</span>
                        </label>
                        <input
                            type="number"
                            name="ev_charge[current_battery]"
                            id="current_battery"
                            x-model="currentBattery"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="Contoh: 45"
                            class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-emerald-500/20 transition"
                        >
                        <div x-show="Number(currentBattery) >= 100" class="mt-2 text-xs text-emerald-800 bg-emerald-100/80 px-2.5 py-1.5 rounded-md flex items-center gap-1.5">
                            <span>✓</span>
                            <span>Baterai sudah penuh (100%). Tidak ada biaya tambahan listrik.</span>
                        </div>
                    </div>

                    <div>
                        <label for="ev_note" class="block text-xs font-medium text-emerald-900 mb-1">
                            Catatan Pengisian Listrik (Opsional)
                        </label>
                        <input
                            type="text"
                            name="ev_charge[note]"
                            id="ev_note"
                            placeholder="Contoh: Tolong charge sampai 100% sebelum jam 08.00"
                            class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-emerald-500/20 transition"
                        >
                    </div>
                </div>
            </template>

            {{-- Live Price Summary Box (Dihitung Oleh Sistem) --}}
            <div class="mb-5 p-4 bg-green-50/70 border border-green-200 rounded-xl space-y-2">
                <div class="flex justify-between text-xs text-gray-600">
                    <span>Biaya Sewa Garasi (<span x-text="rentType === 'daily' ? `${dailyDays} hari` : `${monthlyMonths} bulan`"></span>):</span>
                    <span class="font-semibold text-gray-800" x-text="'Rp ' + Number(rentalPrice).toLocaleString('id-ID')"></span>
                </div>

                <template x-if="isElectric && evChargingPrice > 0">
                    <div class="flex justify-between text-xs text-emerald-700">
                        <span>Biaya Listrik EV (Isi hingga 100%):</span>
                        <span class="font-semibold" x-text="'Rp ' + Number(evChargingPrice).toLocaleString('id-ID')"></span>
                    </div>
                </template>

                <div class="flex justify-between items-center pt-2 border-t border-green-200 text-sm font-bold text-gray-900">
                    <span>Total Biaya Pelunasan:</span>
                    <span class="text-base text-green-700" x-text="'Rp ' + Number(grandTotal).toLocaleString('id-ID')"></span>
                </div>
            </div>

            {{-- Payment Receipt Upload --}}
            <div class="mb-5">
                <label for="payment_receipt" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Bukti Pelunasan Biaya (Upload Gambar Transfer)
                </label>
                <input
                    type="file"
                    name="payment_receipt"
                    id="payment_receipt"
                    accept="image/*,.pdf"
                    required
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 transition border border-gray-300 rounded-lg p-1.5"
                >
                @error('payment_receipt')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes / Remarks --}}
            <div class="mb-6">
                <label for="rent_note" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Catatan Sewa (Opsional)
                </label>
                <textarea
                    name="note"
                    id="rent_note"
                    rows="2"
                    placeholder="Catatan tambahan bila ada..."
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition"
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
                    Batal
                </button>

                <button
                    type="submit"
                    :disabled="matchingVehicles.length === 0"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    Simpan & Sewa Garasi
                </button>
            </div>
        </form>
    </div>
</div>
