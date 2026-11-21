<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();

        if ($users->isEmpty()) {
            return;
        }

        // Helper to get user ID safely by index (1 to 10)
        $userId = fn(int $index) => $users->get(($index - 1) % $users->count())->id;

        $vehicles = [
            // User 1
            [
                'brand' => 'Toyota Avanza',
                'license_plate' => 'B 1101 ABC',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(1),
            ],
            [
                'brand' => 'Honda Vario 160',
                'license_plate' => 'B 3101 DEF',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(1),
            ],
            [
                'brand' => 'Hyundai Ioniq 5',
                'license_plate' => 'B 2101 EV',
                'category' => 'Electric Car',
                'total_kwh' => 72.60,
                'owner_id' => $userId(1),
            ],

            // User 2
            [
                'brand' => 'Honda HR-V',
                'license_plate' => 'B 1202 GHI',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(2),
            ],
            [
                'brand' => 'Yamaha NMAX 155',
                'license_plate' => 'B 3202 JKL',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(2),
            ],
            [
                'brand' => 'Polygon Path 3',
                'license_plate' => null,
                'category' => 'Bike',
                'total_kwh' => null,
                'owner_id' => $userId(2),
            ],

            // User 3
            [
                'brand' => 'Mitsubishi Xpander',
                'license_plate' => 'B 1303 MNO',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(3),
            ],
            [
                'brand' => 'Wuling Air EV',
                'license_plate' => 'B 2303 EV',
                'category' => 'Electric Car',
                'total_kwh' => 26.70,
                'owner_id' => $userId(3),
            ],
            [
                'brand' => 'Vespa Primavera 150',
                'license_plate' => 'B 3303 PQR',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(3),
            ],

            // User 4
            [
                'brand' => 'Toyota Innova Zenix',
                'license_plate' => 'B 1404 STU',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(4),
            ],
            [
                'brand' => 'Honda PCX 160',
                'license_plate' => 'B 3404 VWX',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(4),
            ],
            [
                'brand' => 'United Clovis 5.1',
                'license_plate' => null,
                'category' => 'Bike',
                'total_kwh' => null,
                'owner_id' => $userId(4),
            ],

            // User 5
            [
                'brand' => 'BYD Dolphin',
                'license_plate' => 'B 2505 EV',
                'category' => 'Electric Car',
                'total_kwh' => 44.90,
                'owner_id' => $userId(5),
            ],
            [
                'brand' => 'Yamaha Aerox 155',
                'license_plate' => 'B 3505 YZA',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(5),
            ],
            [
                'brand' => 'Suzuki Ertiga',
                'license_plate' => 'B 1505 BCD',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(5),
            ],

            // User 6
            [
                'brand' => 'Daihatsu Terios',
                'license_plate' => 'B 1606 EFG',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(6),
            ],
            [
                'brand' => 'Kawasaki Ninja 250',
                'license_plate' => 'B 3606 HIJ',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(6),
            ],
            [
                'brand' => 'Pacific Noris 2.3',
                'license_plate' => null,
                'category' => 'Bike',
                'total_kwh' => null,
                'owner_id' => $userId(6),
            ],

            // User 7
            [
                'brand' => 'Hyundai Kona Electric',
                'license_plate' => 'B 2707 EV',
                'category' => 'Electric Car',
                'total_kwh' => 64.80,
                'owner_id' => $userId(7),
            ],
            [
                'brand' => 'Honda Scoopy',
                'license_plate' => 'B 3707 KLM',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(7),
            ],
            [
                'brand' => 'Mazda CX-5',
                'license_plate' => 'B 1707 NOP',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(7),
            ],

            // User 8
            [
                'brand' => 'Honda Civic RS',
                'license_plate' => 'B 1808 QRS',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(8),
            ],
            [
                'brand' => 'Yamaha XMAX 250',
                'license_plate' => 'B 3808 TUV',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(8),
            ],
            [
                'brand' => 'Polygon Strattos S3',
                'license_plate' => null,
                'category' => 'Bike',
                'total_kwh' => null,
                'owner_id' => $userId(8),
            ],

            // User 9
            [
                'brand' => 'Chery Omoda E5',
                'license_plate' => 'B 2909 EV',
                'category' => 'Electric Car',
                'total_kwh' => 61.00,
                'owner_id' => $userId(9),
            ],
            [
                'brand' => 'Honda Beat',
                'license_plate' => 'B 3909 WXY',
                'category' => 'Motor Cycle',
                'total_kwh' => null,
                'owner_id' => $userId(9),
            ],
            [
                'brand' => 'Giant Escape 3',
                'license_plate' => null,
                'category' => 'Bike',
                'total_kwh' => null,
                'owner_id' => $userId(9),
            ],

            // User 10
            [
                'brand' => 'MG 4 EV',
                'license_plate' => 'B 2010 EV',
                'category' => 'Electric Car',
                'total_kwh' => 51.00,
                'owner_id' => $userId(10),
            ],
            [
                'brand' => 'Toyota Yaris Cross',
                'license_plate' => 'B 1010 ZAB',
                'category' => 'Car',
                'total_kwh' => null,
                'owner_id' => $userId(10),
            ],
            [
                'brand' => 'Trek Marlin 5',
                'license_plate' => null,
                'category' => 'Bike',
                'total_kwh' => null,
                'owner_id' => $userId(10),
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::create($vehicle);
        }
    }
}
