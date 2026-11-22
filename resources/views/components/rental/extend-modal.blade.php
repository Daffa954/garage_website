<div
    x-show="showExtendModal"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6"
    @click.self="showExtendModal = false"
    @keydown.escape.window="showExtendModal = false"
>
    <div
        x-show="showExtendModal"
        x-transition
        class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
    >
        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Perpanjang Status Sewa Garasi
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Perpanjang durasi sewa untuk penyewa: <span class="font-medium text-gray-700" x-text="selectedRent?.renter?.name"></span>
                </p>
            </div>

            <button
                type="button"
                @click="showExtendModal = false"
                class="text-gray-400 hover:text-gray-600 text-2xl"
            >
                &times;
            </button>
        </div>

        {{-- Modal Form --}}
        <form
            :action="'/api/rent/' + (selectedRent ? selectedRent.id : '') + '/extend'"
            method="POST"
            enctype="multipart/form-data"
            class="p-6"
        >
            @csrf

            {{-- Rental Summary Info Box --}}
            <div class="mb-5 p-4 rounded-lg bg-gray-50 border border-gray-200 text-sm space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Kode Garasi:</span>
                    <span class="font-semibold text-gray-800 font-mono" x-text="selectedRent?.garage?.garage_code"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Kendaraan:</span>
                    <span class="font-medium text-gray-700" x-text="selectedRent?.vehicle?.brand + ' (' + (selectedRent?.vehicle?.license_plate || 'Tanpa Plat') + ')'"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Tanggal Selesai Saat Ini:</span>
                    <span class="font-semibold text-green-600" x-text="formatDate(selectedRent?.end_date)"></span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                    <span class="text-gray-500">Tarif Harian Garasi:</span>
                    <span class="font-semibold text-gray-800" x-text="'Rp ' + Number(selectedRent?.garage?.price || 0).toLocaleString('id-ID') + ' / hari'"></span>
                </div>
            </div>

            {{-- New End Date --}}
            <div class="mb-5">
                <label for="new_end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Tanggal Selesai Baru <span class="text-red-500 font-bold">*</span>
                </label>
                <input
                    type="date"
                    name="new_end_date"
                    id="new_end_date"
                    x-model="newEndDate"
                    :min="minEndDate"
                    required
                    class="w-full rounded-lg p-2 border-[0.5px] border-gray-500 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition"
                >
                <div class="mt-2 text-xs flex items-center justify-between">
                    <span class="text-gray-500">
                        Tambahan Hari: <strong class="text-green-600" x-text="daysDifference + ' hari'"></strong>
                    </span>
                    <span class="text-gray-400">
                        Harus lebih lama dari tanggal selesai saat ini
                    </span>
                </div>
                @error('new_end_date')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Live Additional Cost Box (Sisa Biaya yang Harus Dibayarkan) --}}
            <div class="mb-5 p-4 rounded-lg bg-green-50/80 border border-green-200">
                <div class="flex justify-between items-center text-sm">
                    <span class="font-medium text-gray-700">Sisa Biaya yang Harus Dibayarkan:</span>
                    <span class="text-lg font-bold text-green-700" x-text="'Rp ' + Number(daysDifference * (selectedRent?.garage?.price || 0)).toLocaleString('id-ID')"></span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Dihitung otomatis: <span x-text="daysDifference"></span> hari &times; Rp <span x-text="Number(selectedRent?.garage?.price || 0).toLocaleString('id-ID')"></span>
                </p>
            </div>

            {{-- Payment Receipt Upload --}}
            <div class="mb-5">
                <label for="extend_payment_receipt" class="block text-sm font-medium text-gray-700 mb-2">
                    Upload Bukti Pelunasan / Bukti Bayar Perpanjangan
                </label>
                <input
                    type="file"
                    name="payment_receipt"
                    id="extend_payment_receipt"
                    accept="image/png,image/jpeg,image/jpg,application/pdf"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 transition"
                >
                <p class="text-xs text-gray-400 mt-1">Format diterima: JPG, PNG, PDF (Maksimal 2MB)</p>
                @error('payment_receipt')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Extension Note --}}
            <div class="mb-6">
                <label for="extend_note" class="block text-sm font-medium text-gray-700 mb-2">
                    Catatan Perpanjangan (Opsional)
                </label>
                <textarea
                    name="note"
                    id="extend_note"
                    rows="3"
                    placeholder="Contoh: Perpanjangan sewa atas permintaan penyewa via telepon."
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition resize-none"
                ></textarea>
                @error('note')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button
                    type="button"
                    @click="showExtendModal = false"
                    class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    :disabled="daysDifference <= 0"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                >
                    Konfirmasi Perpanjangan Sewa
                </button>
            </div>
        </form>
    </div>
</div>
