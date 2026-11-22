<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $garage_code
 * @property int|null $price_kwh
 * @property int $price
 * @property string $category
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rent> $rents
 * @property-read \App\Models\Rent|null $activeRent
 */
class Garage extends Model
{
    const MAX_GARAGE_TYPE = 999;

    protected $fillable = [
        'garage_code',
        'price_kwh',
        'price',
        'category',
        'status',
    ];

    /**
     * Get the one-to-many relationship to all rental contracts for this garage.
     *
     * @return HasMany HasMany relationship instance to Rent model.
     */
    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class, 'garage_id');
    }

    /**
     * Get the latest active rental contract for this garage.
     *
     * @return HasOne HasOne relationship instance to the latest Rent model.
     */
    public function activeRent(): HasOne
    {
        return $this->hasOne(Rent::class, 'garage_id')->latestOfMany();
    }

    /**
     * Determine if this garage is currently available for rent.
     *
     * @return bool True if garage status equals 'available', false otherwise.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Mark this garage facility status as 'used'.
     *
     * @return void
     */
    public function markAsUsed(): void
    {
        $this->update(['status' => 'used']);
    }

    /**
     * Mark this garage facility status as 'available'.
     *
     * @return void
     */
    public function markAsAvailable(): void
    {
        $this->update(['status' => 'available']);
    }
}
