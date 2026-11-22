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
        'category',
        'owner_id',
    ];

    /**
     * Get the owner user relationship for this vehicle.
     *
     * @return BelongsTo BelongsTo relationship instance to User model.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all rental transactions associated with this vehicle.
     *
     * @return HasMany HasMany relationship instance to Rent model.
     */
    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class, 'vehicle_id');
    }

    /**
     * Determine if this vehicle is an electric car.
     *
     * @return bool True if category equals 'Electric Car', false otherwise.
     */
    public function isElectric(): bool
    {
        return $this->category === 'Electric Car';
    }

    /**
     * Determine if this vehicle is compatible to park in the specified garage.
     *
     * @param Garage $garage Target garage instance.
     * @return bool True if parking is permitted, false otherwise.
     */
    public function canParkInGarage(Garage $garage): bool
    {
        if ($this->category === $garage->category) {
            return true;
        }

        // Both Bike (Sepeda) and Motor Cycle (Sepeda Motor) park in Bike (Sepeda) garage
        if (in_array($this->category, ['Bike', 'Motor Cycle']) && $garage->category === 'Bike') {
            return true;
        }

        // Electric Car can also park in a regular Car garage (without charging)
        if ($this->isElectric() && $garage->category === 'Car') {
            return true;
        }

        return false;
    }

    /**
     * Determine if a battery percentage is considered fully charged (100%).
     *
     * @param float $battery Current battery level percentage.
     * @return bool True if battery is 100% or greater, false otherwise.
     */
    public function isBatteryFull(float $battery): bool
    {
        return $battery >= 100;
    }

    /**
     * Single Table Inheritance: instantiate the appropriate subclass model based on category.
     *
     * @param array $attributes Raw attributes array from the database.
     * @param string|null $connection Database connection name.
     * @return static Specific model subclass instance (Bike, MotorCycle, Car, ElectricCar, or Vehicle).
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
