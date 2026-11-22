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
class MotorCycle extends Vehicle
{
    protected $table = 'vehicles';

    protected $attributes = [
        'category' => 'Motor Cycle',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('motorcycle', function (Builder $builder) {
            $builder->where('category', 'Motor Cycle');
        });
    }

    /**
     * Determine if this vehicle requires a motor license plate.
     */
    public function requiresLicensePlate(): bool
    {
        return true;
    }
}
