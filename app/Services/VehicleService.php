<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    /**
     * Create a new vehicle record.
     *
     * @param array $data Validated vehicle data.
     * @return Vehicle
     */
    public function create(array $data): Vehicle
    {
        return Vehicle::create([
            'brand' => $data['brand'],
            'license_plate' => $data['license_plate'] ?? null,
            'total_kwh' => $data['total_kwh'] ?? null,
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
     * Retrieve all vehicles owned by a specific user.
     *
     * @param int $userId User ID.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllVehiclesByUserID(int $userId)
    {
        return Vehicle::where('owner_id', $userId)->get();
    }
}
