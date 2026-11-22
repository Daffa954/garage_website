<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RentService
{
    public function __construct(
        private EvChargeService $evChargeService
    ) {
    }

    /**
     * Create a new rental transaction with its breakdown details, payment receipt, and optional EV charging.
     *
     * @param array{
     *     garage_id: int,
     *     vehicle_id: int,
     *     start_date: string,
     *     end_date: string,
     *     url_payment_receipt?: string,
     *     note?: string,
     *     ev_charge?: array{
     *         current_battery: float|numeric,
     *         url_payment_receipt: string,
     *         note?: string
     *     }
     * } 
     * @param User $renter The authenticated user who is renting the garage.
     * @return Rent The created Rent model instance.
     *
     * @throws RuntimeException If the garage is not available.
     * @throws InvalidArgumentException If the rental dates are invalid.
     */
    public function create(array $data, User $renter): Rent
    {
        return DB::transaction(function () use ($data, $renter) {

            // 1. Get and lock garage
            $garage = Garage::query()
                ->lockForUpdate()
                ->findOrFail($data['garage_id']);

            if (! $garage->isAvailable()) {
                throw new RuntimeException('Garage is not available.');
            }

            // 2. Parse and validate rental dates
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->startOfDay();

            if ($endDate->lte($startDate)) {
                throw new InvalidArgumentException(
                    'End date must be later than start date.'
                );
            }

            // 3. Instantiate Rent model and calculate periods via model domain behavior
            $rent = new Rent([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'note' => $data['note'] ?? null,
                'renter_id' => $renter->id,
                'garage_id' => $garage->id,
                'vehicle_id' => $data['vehicle_id'],
            ]);

            $periods = $rent->calculatePeriods($garage->price);

            $rent->total_day = (int) $startDate->diffInDays($endDate);
            $rent->total_price = array_sum(
                array_column($periods, 'total_price')
            );
            $rent->save();

            // 5. Create rental period details
            foreach ($periods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => false,
                ]);
            }

            // 6. Store initial rental payment receipt
            if (!empty($data['url_payment_receipt'])) {
                $rent->payments()->create([
                    'url_payment_receipt' => $data['url_payment_receipt'],
                    'remarks' => $data['note'] ?? null,
                ]);
            }

            // 7. Create EV charging transaction if required (only if electric vehicle and battery < 100%)
            $isElectricVehicle = $rent->vehicle instanceof \App\Models\ElectricCar || $rent->vehicle?->isElectric();
            $batteryNeedsCharge = !empty($data['ev_charge']) && !$rent->vehicle?->isBatteryFull((float) ($data['ev_charge']['current_battery'] ?? 100));

            if ($garage->category === 'Electric Car' && $isElectricVehicle && $batteryNeedsCharge) {
                $this->evChargeService->create(
                    $rent,
                    $data['ev_charge']
                );
            }

            // 8. Update garage status
            $garage->markAsUsed();

            return $rent;
        });
    }

    /**
     * Extend an existing rental contract with additional period details and payment receipt.
     *
     * @param Rent $rent The existing rental transaction to extend.
     * @param array{
     *     new_end_date: string,
     *     url_payment_receipt?: string,
     *     note?: string
     * } $data Extension details.
     * @return Rent The updated Rent model instance.
     *
     * @throws InvalidArgumentException If the new end date is not after the current end date.
     */
    public function extend(Rent $rent, array $data): Rent
    {
        return DB::transaction(function () use ($rent, $data) {
            $currentEndDate = Carbon::parse($rent->end_date)->startOfDay();
            $newEndDate = Carbon::parse($data['new_end_date'])->startOfDay();

            if ($newEndDate->lte($currentEndDate)) {
                throw new InvalidArgumentException(
                    'New end date must be later than the current rental end date.'
                );
            }

            // Directly reject extension if another booking already exists for this garage in the requested date range
            $hasConflict = Rent::where('garage_id', $rent->garage_id)
                ->where('id', '!=', $rent->id)
                ->where('start_date', '<', $newEndDate->toDateString())
                ->where('end_date', '>', $currentEndDate->toDateString())
                ->exists();

            if ($hasConflict) {
                throw new InvalidArgumentException(
                    'Cannot extend rental: The garage is already booked by another reservation for the selected dates.'
                );
            }

            // Calculate extension periods using Rent model domain behavior
            $periods = $rent->calculateExtensionPeriods(
                $newEndDate,
                $rent->garage->price
            );

            $additionalDays = (int) $currentEndDate->diffInDays($newEndDate);
            $additionalPrice = array_sum(array_column($periods, 'total_price'));

            // Create rental extension period details
            foreach ($periods as $period) {
                $rent->rentDetails()->create([
                    ...$period,
                    'is_extension' => true,
                ]);
            }

            // Store extension payment receipt if uploaded
            if (!empty($data['url_payment_receipt'])) {
                $rent->payments()->create([
                    'url_payment_receipt' => $data['url_payment_receipt'],
                    'remarks' => $data['note'] ?? 'Rental extension payment',
                ]);
            }

            // Update main rent record with new end date, total days, and total price
            $rent->update([
                'end_date' => $newEndDate->toDateString(),
                'total_day' => $rent->total_day + $additionalDays,
                'total_price' => $rent->total_price + $additionalPrice,
            ]);

            // Ensure garage status remains occupied
            $rent->garage->markAsUsed();

            return $rent->fresh(['rentDetails', 'payments', 'garage', 'vehicle', 'renter']);
        });
    }

    /**
     * Calculate rental periods based on daily and monthly pricing rules.
     * Delegates to the Rent model domain behavior.
     *
     * A one-month calculation always follows the number of days in the month where the booking started.
     * - Monthly price = 25 * daily garage price.
     * - Daily max = number of days in the starting month - 1.
     *
     * @param Carbon $startDate Starting date of the rental.
     * @param Carbon $endDate Ending date of the rental.
     * @param int|float $dailyPrice Daily garage rental price.
     * @return array<int, array{type: string, start_date: string, end_date: string, total_day: int, total_price: int|float}>
     */
    private function calculatePeriods(
        Carbon $startDate,
        Carbon $endDate,
        int|float $dailyPrice
    ): array {
        $rent = new Rent([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $rent->calculatePeriods($dailyPrice);
    }
}
