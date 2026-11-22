<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $brand
 * @property string|null $license_plate
 * @property float|null $total_kwh
 * @property string|null $image
 * @property string $category
 * @property int $owner_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rent> $rents
 */
class ElectricCar extends Vehicle
{
    protected $table = 'vehicles';

    protected $attributes = [
        'category' => 'Electric Car',
    ];

    /**
     * The "booted" method of the model to register global scope for Electric Car category.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope('electric_car', function (Builder $builder) {
            $builder->where('category', 'Electric Car');
        });
    }

    /**
     * Determine if this vehicle is electric.
     *
     * @return bool Always true for ElectricCar instances.
     */
    public function isElectric(): bool
    {
        return true;
    }

    /**
     * Determine if this electric car can park in the specified garage.
     * Electric cars can park in Electric Car garages or regular Car garages.
     *
     * @param Garage $garage Target garage instance.
     * @return bool True if garage category is 'Electric Car' or 'Car', false otherwise.
     */
    public function canParkInGarage(Garage $garage): bool
    {
        return in_array($garage->category, ['Electric Car', 'Car'], true);
    }

    /**
     * Check if the given battery level is considered fully charged (100%).
     *
     * @param float $currentBattery Current battery percentage level.
     * @return bool True if battery level is 100% or greater, false otherwise.
     */
    public function isBatteryFull(float $currentBattery): bool
    {
        return $currentBattery >= 100;
    }

    /**
     * Calculate required kWh energy to charge battery up to 100% full capacity.
     *
     * @param float $currentBattery Current battery level percentage.
     * @return float Required energy in kWh rounded to 2 decimal places.
     */
    public function calculateEnergyNeeded(float $currentBattery): float
    {
        if ($this->isBatteryFull($currentBattery) || empty($this->total_kwh)) {
            return 0.0;
        }

        $percentageNeeded = (100 - $currentBattery) / 100;
        return round((float) $this->total_kwh * $percentageNeeded, 2);
    }
}
