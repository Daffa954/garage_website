<?php

namespace App\Services;

use App\Models\Bike;
use App\Models\Car;
use App\Models\ElectricCar;
use App\Models\MotorCycle;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    /**
     * Create a new vehicle record using the appropriate subclass model.
     *
     * @param array $data Validated vehicle data.
     * @return Vehicle
     */
    public function create(array $data): Vehicle
    {
        $class = match ($data['category']) {
            'Bike' => Bike::class,
            'Motor Cycle' => MotorCycle::class,
            'Car' => Car::class,
            'Electric Car' => ElectricCar::class,
            default => Vehicle::class,
        };

        return $class::create([
            'brand' => $data['brand'],
            'license_plate' => $data['license_plate'] ?? null,
            'total_kwh' => $data['category'] === 'Electric Car' ? ($data['total_kwh'] ?? null) : null,
            'image' => $data['image'] ?? null,
            'category' => $data['category'],
            'owner_id' => $data['owner_id'],
        ]);
    }

    /**
     * Update an existing vehicle record.
     *
     * @param int $id Vehicle ID.
     * @param array $data Validated vehicle data.
     * @return Vehicle
     */
    public function update(int $id, array $data): Vehicle
    {
        $vehicle = Vehicle::findOrFail($id);

        $vehicle->update([
            'brand' => $data['brand'],
            'license_plate' => $data['license_plate'] ?? null,
            'total_kwh' => $data['total_kwh'] ?? null,
            'image' => $data['image'] ?? null,
            'category_id' => $data['category_id'],
        ]);

        return $vehicle;
    }

    /**
     * Delete an existing vehicle record.
     *
     * @param int $id Vehicle ID.
     * @return bool
     */
    public function delete(int $id): bool
    {
        $vehicle = Vehicle::findOrFail($id);

        return $vehicle->delete();
    }

    /**
     * Retrieve all vehicles owned by a specific user with pagination.
     *
     * @param int|null $userId User ID.
     * @param int $perPage Items per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllVehiclesByUserID(?int $userId, int $perPage = 10)
    {
        return Vehicle::where('owner_id', $userId)
            ->latest()
            ->paginate($perPage);
    }
}
