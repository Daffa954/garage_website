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
     * @param int|null $userId Optional authenticated user ID.
     * @param int $perPage Number of garage items per page.
     * @return array{garages: \Illuminate\Contracts\Pagination\LengthAwarePaginator, vehicles: \Illuminate\Support\Collection} Data array containing paginated garages and free vehicles.
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
     * Create a single new garage facility with an auto-generated unique code.
     *
     * @param array $data Validated garage attributes.
     * @return Garage Newly created Garage model instance.
     * @throws RuntimeException If category is invalid or maximum capacity is reached.
     */
    public function create(array $data): Garage
    {
        return $this->createBatch($data)->first();
    }

    /**
     * Create a batch of new garage facilities with auto-generated unique codes.
     *
     * @param array $data Validated garage data including 'category', 'price', and optional 'quantity'.
     * @return \Illuminate\Support\Collection<int, Garage> Collection of newly created Garage model instances.
     * @throws RuntimeException If category is invalid or maximum capacity is reached.
     */
    public function createBatch(array $data): \Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($data) {
            $category = $data['category'] === 'Motor Cycle' ? 'Bike' : $data['category'];

            // Generate garage Code Prefix
            $prefix = match ($category) {
                'Bike' => 'S',
                'Car' => 'C',
                'Electric Car' => 'E',
                default => throw new RuntimeException(
                    'Invalid garage category.'
                ),
            };

            $quantity = max(1, (int) ($data['quantity'] ?? 1));

            // Get Latest Number
            $lastNumber = Garage::where('garage_code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->selectRaw(
                    'MAX(CAST(SUBSTRING(garage_code, 2) AS UNSIGNED)) as last_number'
                )
                ->value('last_number');

            $currentNumber = ($lastNumber ?? 0);

            // Check Maximum amount
            if ($currentNumber >= Garage::MAX_GARAGE_TYPE) {
                throw new RuntimeException(
                    'Maximum garage capacity reached.'
                );
            }

            // If requested quantity exceeds remaining capacity, create as many as possible up to 999
            $availableSlots = Garage::MAX_GARAGE_TYPE - $currentNumber;
            $quantityToCreate = min($quantity, $availableSlots);

            $createdGarages = collect();

            for ($i = 1; $i <= $quantityToCreate; $i++) {
                $nextNumber = $currentNumber + $i;
                $garageCode = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $garage = Garage::create([
                    'garage_code' => $garageCode,
                    'price_kwh' => $data['price_kwh'] ?? null,
                    'price' => $data['price'],
                    'category' => $category,
                    'status' => 'available',
                ]);

                $createdGarages->push($garage);
            }

            return $createdGarages;
        });
    }
}
