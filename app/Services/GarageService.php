<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Garage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GarageService
{

    public function create(array $data): Garage
    {
        return DB::transaction(function () use ($data) {
            // Generate garage Code
            $prefix = match ($data['category']) {
                'Bike', 'Motor Cycle' => 'S',
                'Car' => 'C',
                'Electric Car' => 'E',
                default => throw new RuntimeException(
                    'Invalid garage category.'
                ),
            };
            // Get Latest Number
            $lastNumber = Garage::where('garage_code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->selectRaw(
                    'MAX(CAST(SUBSTRING(garage_code, 2) AS UNSIGNED)) as last_number'
                )
                ->value('last_number');

            $nextNumber = ($lastNumber ?? 0) + 1;
            // Check Maximum ammount type
            if ($nextNumber > Garage::MAX_GARAGE_TYPE) {
                throw new RuntimeException(
                    'Maximum garage capacity reached.'
                );
            }
            // Build code
            $garageCode = $prefix . str_pad(
                $nextNumber,
                3,
                '0',
                STR_PAD_LEFT
            );

            return Garage::create([
                'garage_code' => $garageCode,
                'name' => $data['name'],
                'price_kwh' => $data['price_kwh'] ?? null,
                'price' => $data['price'],
                'category' => $data['category'],
                'status' => 'available',
            ]);
        });
    }

}