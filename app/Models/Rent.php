<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * Calculate rental periods based on daily and monthly pricing rules.
     *
     * A one-month calculation always follows the number of days in the month where the booking started.
     * - Monthly price = 25 * daily garage price.
     * - Daily max = number of days in the starting month - 1.
     *
     * @param int|float $dailyPrice Daily garage rental price.
     * @param Carbon|null $startDate Optional custom start date (defaults to $this->start_date).
     * @param Carbon|null $endDate Optional custom end date (defaults to $this->end_date).
     * @return array<int, array{type: string, start_date: string, end_date: string, total_day: int, total_price: int|float}>
     */
    public function calculatePeriods(
        int|float $dailyPrice,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ? $startDate->copy()->startOfDay() : Carbon::parse($this->start_date)->startOfDay();
        $endDate = $endDate ? $endDate->copy()->startOfDay() : Carbon::parse($this->end_date)->startOfDay();

        $periods = [];

        $remainingDays = (int) $startDate->diffInDays($endDate);
        $currentStartDate = $startDate->copy();

        $daysInStartingMonth = $startDate->daysInMonth;
        $dailyMax = $daysInStartingMonth - 1;

        while ($remainingDays > 0) {
            if ($remainingDays > $dailyMax) {
                $type = 'monthly';
                $periodDays = $daysInStartingMonth;
                $totalPrice = 25 * $dailyPrice;
            } else {
                $type = 'daily';
                $periodDays = $remainingDays;
                $totalPrice = $dailyPrice * $periodDays;
            }

            $currentEndDate = $currentStartDate->copy()->addDays($periodDays);

            $periods[] = [
                'type' => $type,
                'start_date' => $currentStartDate->toDateString(),
                'end_date' => $currentEndDate->toDateString(),
                'total_day' => $periodDays,
                'total_price' => $totalPrice,
            ];

            $remainingDays -= $periodDays;
            $currentStartDate = $currentEndDate;
        }

        return $periods;
    }

    /**
     * Calculate rental extension periods from current rental end date to a new target end date.
     *
     * @param Carbon|string $newEndDate
     * @param int|float $dailyPrice
     * @return array<int, array{type: string, start_date: string, end_date: string, total_day: int, total_price: int|float}>
     */
    public function calculateExtensionPeriods(Carbon|string $newEndDate, int|float $dailyPrice): array
    {
        $currentEndDate = Carbon::parse($this->end_date)->startOfDay();
        $targetEndDate = $newEndDate instanceof Carbon
            ? $newEndDate->copy()->startOfDay()
            : Carbon::parse($newEndDate)->startOfDay();

        return $this->calculatePeriods($dailyPrice, $currentEndDate, $targetEndDate);
    }
}
