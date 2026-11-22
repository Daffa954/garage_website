<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
class Vehicle extends Model
{
    protected $fillable = [
        'brand',
        'license_plate',
        'total_kwh',
        'image',
        'category',
        'owner_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class, 'vehicle_id');
    }

    /**
     * Determine if this vehicle is an electric car.
     */
    public function isElectric(): bool
    {
        return $this->category === 'Electric Car';
    }

    /**
     * Check if this vehicle is compatible to park in the specified garage.
     *
     * @param Garage $garage
     * @return bool
     */
    public function canParkInGarage(Garage $garage): bool
    {
        if ($this->category === $garage->category) {
            return true;
        }

        // Electric Car can also park in a regular Car garage (without charging)
        if ($this->isElectric() && $garage->category === 'Car') {
            return true;
        }

        return false;
    }

    /**
     * Determine if a battery percentage is considered full (100%).
     *
     * @param float $battery
     * @return bool
     */
    public function isBatteryFull(float $battery): bool
    {
        return $battery >= 100;
    }

    /**
     * Single Table Inheritance: instantiate the appropriate subclass model based on category.
     *
     * @param array $attributes
     * @param string|null $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $rawAttributes = (array) $attributes;
        $category = $rawAttributes['category'] ?? null;

        $class = match ($category) {
            'Bike' => Bike::class,
            'Motor Cycle' => MotorCycle::class,
            'Car' => Car::class,
            'Electric Car' => ElectricCar::class,
            default => static::class,
        };

        $model = new $class;
        $model->exists = true;
        $model->setRawAttributes($rawAttributes, true);
        $model->setConnection($connection ?: $this->getConnectionName());
        $model->fireModelEvent('retrieved', false);

        return $model;
    }
}
