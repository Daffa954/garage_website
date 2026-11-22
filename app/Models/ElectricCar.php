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
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('electric_car', function (Builder $builder) {
            $builder->where('category', 'Electric Car');
        });
    }

    /**
     * Determine if this vehicle is electric.
     */
    public function isElectric(): bool
    {
        return true;
    }

    /**
     * Electric cars can park in Electric Car garages or regular Car garages.
     */
    public function canParkInGarage(Garage $garage): bool
    {
        return in_array($garage->category, ['Electric Car', 'Car'], true);
    }

    /**
     * Check if the given battery level is considered fully charged.
     */
    public function isBatteryFull(float $currentBattery): bool
    {
        return $currentBattery >= 100;
    }

    /**
     * Calculate required kWh energy to charge up to 100%.
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
