<?php

namespace Database\Seeders;

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\RentDetail;
use App\Models\RentPayment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates realistic rental transactions covering:
     * 1. Active Car Rental (Daily)
     * 2. Active Electric Car Rental with EV Fast Charging
     * 3. Active Motorcycle Rental (Monthly flat rate)
     * 4. Active Extended Rental (Initial 1 Month + 10 Days Extension)
     * 5. Active Bike Rental (Short-term daily)
     * 6. Past/Completed Rentals for analytics and report revenue
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->where('is_verified', true)->orderBy('id')->get();
        if ($users->isEmpty()) {
            return;
        }

        // Fetch users by email for accurate relationship mapping
        $budi = User::where('email', 'budi@example.com')->first() ?? $users->first();
        $siti = User::where('email', 'siti@example.com')->first() ?? $users->get(1);
        $ahmad = User::where('email', 'ahmad@example.com')->first() ?? $users->get(2);
        $dewi = User::where('email', 'dewi@example.com')->first() ?? $users->get(3);
        $citra = User::where('email', 'citra@example.com')->first() ?? $users->get(4);

        // Fetch compatible vehicles
        $avanza = Vehicle::where('brand', 'like', '%Avanza%')->first();
        $hrv = Vehicle::where('brand', 'like', '%HR-V%')->first() ?? $avanza;
        $ioniq5 = Vehicle::where('category', 'Electric Car')->first();
        $nmax = Vehicle::where('brand', 'like', '%NMAX%')->first();
        $polygonBike = Vehicle::where('category', 'Bike')->first();

        // Fetch garages
        $garageC001 = Garage::where('garage_code', 'C001')->first();
        $garageC002 = Garage::where('garage_code', 'C002')->first();
        $garageC003 = Garage::where('garage_code', 'C003')->first();
        $garageE001 = Garage::where('garage_code', 'E001')->first();
        $garageE002 = Garage::where('garage_code', 'E002')->first();
        $garageS500 = Garage::where('garage_code', 'S500')->first();
        $garageS001 = Garage::where('garage_code', 'S001')->first();

        $today = Carbon::today();

        // =====================================================================
        // TRANSAKSI 1: Sewa Mobil Harian (Aktif)
        // Budi menyewa Garage Car 1 untuk Honda HR-V selama 5 hari
        // =====================================================================
        if ($budi && $hrv && $garageC001) {
            $startDate = $today->copy()->subDays(1);
            $endDate = $today->copy()->addDays(4);
            $dailyPrice = $garageC001->price; // 50,000

            $rent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'note' => 'Parkir mingguan keperluan dinas luar kota',
                'renter_id' => $budi->id,
                'garage_id' => $garageC001->id,
                'vehicle_id' => $hrv->id,
            ]);
            $periods = $rent->calculatePeriods($dailyPrice);
            $rent->total_day = (int) $startDate->diffInDays($endDate);
            $rent->total_price = array_sum(array_column($periods, 'total_price'));
            $rent->save();

            foreach ($periods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            $rent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/transfer_bca_budi_hrv.jpg',
                'remarks' => 'Pembayaran lunas via BCA Mobile m-Transfer Ref #BCA-98127391',
            ]);

            $garageC001->markAsUsed();
        }

        // =====================================================================
        // TRANSAKSI 2: Sewa Mobil Listrik + Fast Charging EV (Aktif)
        // Siti menyewa Garage EV 1 untuk Hyundai Ioniq 5 dengan pengisian baterai
        // =====================================================================
        if ($siti && $ioniq5 && $garageE001) {
            $startDate = $today->copy()->subDays(2);
            $endDate = $today->copy()->addDays(5);
            $dailyPrice = $garageE001->price; // 60,000

            $rent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'note' => 'Titip mobil listrik sekalian charge sampai penuh 100%',
                'renter_id' => $siti->id,
                'garage_id' => $garageE001->id,
                'vehicle_id' => $ioniq5->id,
            ]);
            $periods = $rent->calculatePeriods($dailyPrice);
            $rent->total_day = (int) $startDate->diffInDays($endDate);
            $rent->total_price = array_sum(array_column($periods, 'total_price'));
            $rent->save();

            foreach ($periods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            // Pembayaran sewa garasi
            $rent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/qris_mandiri_siti_ev.png',
                'remarks' => 'Sewa garasi EV 7 hari via QRIS Mandiri Livin',
            ]);

            // Pengisian EV: Baterai 35%, kapasitas 72.6 kWh, tarif 2.500/kWh
            // Kebutuhan = 72.6 * (100 - 35)% = 47.19 kWh
            // Biaya charge = 47.19 * 2500 = 117.975
            $currentBattery = 35.0;
            $energyNeeded = round((float) $ioniq5->total_kwh * ((100 - $currentBattery) / 100), 2);
            $chargingCost = (int) round($energyNeeded * ($garageE001->price_kwh ?? 2500));

            $rent->evCharges()->create([
                'current_battery' => $currentBattery,
                'price_charge' => $chargingCost,
                'note' => "Pengisian daya EV dari {$currentBattery}% ke 100% ({$energyNeeded} kWh)",
                'url_payment_receipt' => '/storage/receipts/qris_charging_siti_ioniq.png',
            ]);

            $garageE001->markAsUsed();
        }

        // =====================================================================
        // TRANSAKSI 3: Sewa Motor Bulanan (Aktif)
        // Ahmad menyewa Garage Motor 1 untuk Yamaha NMAX selama 1 bulan penuh
        // =====================================================================
        if ($ahmad && $nmax && $garageS500) {
            $startDate = $today->copy()->subDays(5);
            $endDate = $startDate->copy()->addMonth(); // 1 bulan penuh
            $dailyPrice = $garageS500->price; // 20,000

            $rent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'note' => 'Langganan parkir bulanan karyawan kantor',
                'renter_id' => $ahmad->id,
                'garage_id' => $garageS500->id,
                'vehicle_id' => $nmax->id,
            ]);
            $periods = $rent->calculatePeriods($dailyPrice);
            $rent->total_day = (int) $startDate->diffInDays($endDate);
            $rent->total_price = array_sum(array_column($periods, 'total_price'));
            $rent->save();

            foreach ($periods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            $rent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/transfer_bni_ahmad_nmax.jpg',
                'remarks' => 'Pembayaran bulanan (paket hemat 25 hari tarif) via BNI Mobile',
            ]);

            $garageS500->markAsUsed();
        }

        // =====================================================================
        // TRANSAKSI 4: Sewa Mobil dengan Perpanjangan / Extension (Aktif)
        // Dewi menyewa Garage Car 2: 1 bulan awal, lalu diperpanjang 10 hari
        // =====================================================================
        if ($dewi && $avanza && $garageC002) {
            $startDate = $today->copy()->subDays(25);
            $initialEndDate = $startDate->copy()->addMonth(); // Selesai beberapa hari lagi
            $extendedEndDate = $initialEndDate->copy()->addDays(10); // Diperpanjang 10 hari ke depan
            $dailyPrice = $garageC002->price; // 50,000

            // 1. Kontrak Awal
            $rent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $initialEndDate->toDateString(),
                'note' => 'Sewa bulanan mobil dinas operasional',
                'renter_id' => $dewi->id,
                'garage_id' => $garageC002->id,
                'vehicle_id' => $avanza->id,
            ]);
            $initialPeriods = $rent->calculatePeriods($dailyPrice);
            $rent->total_day = (int) $startDate->diffInDays($initialEndDate);
            $rent->total_price = array_sum(array_column($initialPeriods, 'total_price'));
            $rent->save();

            foreach ($initialPeriods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            $rent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/transfer_bca_dewi_initial.jpg',
                'remarks' => 'Pembayaran kontrak awal 1 bulan via BCA Virtual Account',
            ]);

            // 2. Perpanjangan (Extension)
            $extensionPeriods = $rent->calculateExtensionPeriods($extendedEndDate, $dailyPrice);
            $additionalDays = (int) $initialEndDate->diffInDays($extendedEndDate);
            $additionalPrice = array_sum(array_column($extensionPeriods, 'total_price'));

            foreach ($extensionPeriods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => true,
                ]);
            }

            $rent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/transfer_bca_dewi_extension.jpg',
                'remarks' => 'Pembayaran perpanjangan sewa 10 hari tambahan via BCA',
            ]);

            $rent->update([
                'end_date' => $extendedEndDate->toDateString(),
                'total_day' => $rent->total_day + $additionalDays,
                'total_price' => $rent->total_price + $additionalPrice,
            ]);

            $garageC002->markAsUsed();
        }

        // =====================================================================
        // TRANSAKSI 5: Sewa Sepeda Harian (Aktif)
        // Citra menyewa Garage Bike 1 untuk Polygon Path 3
        // =====================================================================
        if ($citra && $polygonBike && $garageS001) {
            $startDate = $today->copy();
            $endDate = $today->copy()->addDays(3);
            $dailyPrice = $garageS001->price; // 10,000

            $rent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'note' => 'Titip sepeda commuter harian',
                'renter_id' => $citra->id,
                'garage_id' => $garageS001->id,
                'vehicle_id' => $polygonBike->id,
            ]);
            $periods = $rent->calculatePeriods($dailyPrice);
            $rent->total_day = (int) $startDate->diffInDays($endDate);
            $rent->total_price = array_sum(array_column($periods, 'total_price'));
            $rent->save();

            foreach ($periods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            $rent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/gopay_citra_bike.png',
                'remarks' => 'Pembayaran titip sepeda 3 hari via GoPay Ref #GOPAY-55412',
            ]);

            $garageS001->markAsUsed();
        }

        // =====================================================================
        // TRANSAKSI 6: Sewa Historis / Selesai (Bulan Lalu)
        // Untuk kelengkapan data analitik, riwayat sewa, dan laporan pendapatan
        // =====================================================================
        if ($budi && $avanza && $garageC003) {
            $startDate = $today->copy()->subMonths(2)->startOfMonth();
            $endDate = $startDate->copy()->addDays(15);
            $dailyPrice = $garageC003->price; // 50,000

            $pastRent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'note' => 'Sewa masa liburan akhir tahun lalu (Selesai)',
                'renter_id' => $budi->id,
                'garage_id' => $garageC003->id,
                'vehicle_id' => $avanza->id,
            ]);
            $periods = $pastRent->calculatePeriods($dailyPrice);
            $pastRent->total_day = (int) $startDate->diffInDays($endDate);
            $pastRent->total_price = array_sum(array_column($periods, 'total_price'));
            $pastRent->save();

            foreach ($periods as $period) {
                $pastRent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            $pastRent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/transfer_bca_past_1.jpg',
                'remarks' => 'Lunas via Transfer Bank BCA',
            ]);

            // Garasi historis sudah selesai, status tetap available
            $garageC003->markAsAvailable();
        }

        // =====================================================================
        // TRANSAKSI 7: Sewa Historis EV Charging (Bulan Lalu)
        // =====================================================================
        if ($siti && $ioniq5 && $garageE002) {
            $startDate = $today->copy()->subMonth()->startOfMonth();
            $endDate = $startDate->copy()->addDays(7);
            $dailyPrice = $garageE002->price; // 60,000

            $pastEvRent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'note' => 'Sewa EV dan charging bulan lalu (Selesai)',
                'renter_id' => $siti->id,
                'garage_id' => $garageE002->id,
                'vehicle_id' => $ioniq5->id,
            ]);
            $periods = $pastEvRent->calculatePeriods($dailyPrice);
            $pastEvRent->total_day = (int) $startDate->diffInDays($endDate);
            $pastEvRent->total_price = array_sum(array_column($periods, 'total_price'));
            $pastEvRent->save();

            foreach ($periods as $period) {
                $pastEvRent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            $pastEvRent->payments()->create([
                'url_payment_receipt' => '/storage/receipts/transfer_past_ev.jpg',
                'remarks' => 'Lunas via Mandiri Virtual Account',
            ]);

            // Charging record lampau
            $currentBattery = 50.0;
            $energyNeeded = round((float) $ioniq5->total_kwh * ((100 - $currentBattery) / 100), 2);
            $chargingCost = (int) round($energyNeeded * ($garageE002->price_kwh ?? 2500));

            $pastEvRent->evCharges()->create([
                'current_battery' => $currentBattery,
                'price_charge' => $chargingCost,
                'note' => "Pengisian baterai EV bulan lalu ({$energyNeeded} kWh)",
                'url_payment_receipt' => '/storage/receipts/qris_past_charge.png',
            ]);

            $garageE002->markAsAvailable();
        }
    }
}
