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
     * @param array $data Validated vehicle data attributes.
     * @return Vehicle Newly created vehicle instance.
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
            'category' => $data['category'],
            'owner_id' => $data['owner_id'],
        ]);
    }

   
    /**
     * Retrieve all vehicles owned by a specific user with pagination.
     *
     * @param int|null $userId Target owner User ID.
     * @param int $perPage Number of vehicle items per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated list of owned vehicles.
     */
    public function getAllVehiclesByUserID(?int $userId, int $perPage = 10)
    {
        return Vehicle::where('owner_id', $userId)
            ->latest()
            ->paginate($perPage);
    }
}
