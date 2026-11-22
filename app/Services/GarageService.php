<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Garage;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GarageService
{
    /**
     * Get garage listing with active rents and user's available vehicles for rent modal.
     *
     * @param int|null $userId
     * @param int $perPage
     * @return array{garages: \Illuminate\Contracts\Pagination\LengthAwarePaginator, vehicles: \Illuminate\Support\Collection}
     */
    public function getGarageIndexData(?int $userId = null, int $perPage = 10): array
    {
        
        $garages = Garage::with(['activeRent.renter'])->latest()->paginate($perPage);

        $vehicles = $userId
            ? Vehicle::where('owner_id', $userId)
                ->whereDoesntHave('rents.garage', fn($q) => $q->where('status', '!=', 'available'))
                ->get()
            : collect();

        return compact('garages', 'vehicles');
    }

    /**
     * Create a new garage facility with an auto-generated unique code.
     *
     * @param array $data Validated garage data.
     * @return Garage
     * @throws RuntimeException If category is invalid or maximum capacity is reached.
     */
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