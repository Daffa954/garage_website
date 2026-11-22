<?php

namespace Database\Seeders;

use App\Models\Garage;
use Illuminate\Database\Seeder;

class GarageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates 998 garages per prefix/type following GarageService rules:
     * - Prefix 'C': Car (C001 - C998)
     * - Prefix 'E': Electric Car (E001 - E998)
     * - Prefix 'S': Bike (S001 - S499) and Motor Cycle (S500 - S998)
     *
     * Leaves exactly 1 slot available per prefix (C999, E999, S999)
     * to allow testing the MAX_GARAGE_TYPE (999) capacity limit in GarageService.
     */
    public function run(): void
    {
        $now = now();
        $garages = [];

        // 1. Car Garages (C001 - C998)
        for ($i = 1; $i <= 998; $i++) {
            $code = 'C' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $garages[] = [
                'garage_code' => $code,
                'price_kwh' => null,
                'price' => 50000,
                'category' => 'Car',
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 2. Electric Car Garages (E001 - E998)
        for ($i = 1; $i <= 998; $i++) {
            $code = 'E' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $garages[] = [
                'garage_code' => $code,
                'price_kwh' => 2500,
                'price' => 60000,
                'category' => 'Electric Car',
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 3. Bike Garages (Sepeda & Sepeda Motor: S001 - S998)
        for ($i = 1; $i <= 998; $i++) {
            $code = 'S' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $garages[] = [
                'garage_code' => $code,
                'price_kwh' => null,
                'price' => 15000,
                'category' => 'Bike',
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Chunk insert using insertOrIgnore to safely preserve existing garages and relations
        foreach (array_chunk($garages, 500) as $chunk) {
            Garage::insertOrIgnore($chunk);
        }
    }
}
