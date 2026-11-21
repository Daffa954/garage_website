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

            if ($garage->status !== 'available') {
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

            // 3. Calculate rental periods
            $periods = $this->calculatePeriods(
                $startDate,
                $endDate,
                $garage->price
            );

            $totalDay = (int) $startDate->diffInDays($endDate);

            $totalPrice = array_sum(
                array_column($periods, 'total_price')
            );

            // 4. Create main rent transaction
            $rent = Rent::create([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_day' => $totalDay,
                'total_price' => $totalPrice,
                'note' => $data['note'] ?? null,
                'renter_id' => $renter->id,
                'garage_id' => $garage->id,
                'vehicle_id' => $data['vehicle_id'],
            ]);

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

            // 7. Create EV charging transaction if required
            if (
                $garage->category === 'Electric Car' &&
                !empty($data['ev_charge'])
            ) {
                $this->evChargeService->create(
                    $rent,
                    $data['ev_charge']
                );
            }

            // 8. Update garage status
            $garage->update([
                'status' => 'used',
            ]);

            return $rent;
        });
    }

    /**
     * Calculate rental periods based on daily and monthly pricing rules.
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
}
