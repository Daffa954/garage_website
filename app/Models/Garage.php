<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $garage_code
 * @property string $name
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
        'name',
        'price_kwh',
        'price',
        'category',
        'status',
    ];

    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class, 'garage_id');
    }

    public function activeRent(): HasOne
    {
        return $this->hasOne(Rent::class, 'garage_id')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today());
    }
}
