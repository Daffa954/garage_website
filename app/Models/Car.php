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
class Car extends Vehicle
{
    protected $table = 'vehicles';

    protected $attributes = [
        'category' => 'Car',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('car', function (Builder $builder) {
            $builder->where('category', 'Car');
        });
    }

    /**
     * Determine if this vehicle is electric.
     */
    public function isElectric(): bool
    {
        return false;
    }
}
