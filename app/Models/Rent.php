<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $total_day
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int $total_price
 * @property string|null $note
 * @property int $renter_id
 * @property int $garage_id
 * @property int $vehicle_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $renter
 * @property-read \App\Models\Garage $garage
 * @property-read \App\Models\Vehicle $vehicle
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RentDetail> $rentDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RentPayment> $payments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EvCharge> $evCharges
 */
class Rent extends Model
{

    protected $fillable = [
        'total_day',
        'start_date',
        'end_date',
        'total_price',
        'note',
        'renter_id',
        'garage_id',
        'vehicle_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'garage_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function rentDetails(): HasMany
    {
        return $this->hasMany(RentDetail::class, 'rent_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class, 'rent_id');
    }

    public function evCharges(): HasMany
    {
        return $this->hasMany(EvCharge::class, 'rent_id');
    }
}
