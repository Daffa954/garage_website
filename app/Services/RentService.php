<?php

namespace App\Services;

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RentService
{
    /**
     * RentService constructor.
     *
     * @param EvChargeService $evChargeService Service handling EV charging creation.
     */
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

            // 2. Parse and validate rental dates
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->startOfDay();

            if ($endDate->lte($startDate)) {
                throw new InvalidArgumentException(
                    'End date must be later than start date.'
                );
            }

            // Check if there is an overlapping rental for this garage on requested dates
            $hasConflict = Rent::where('garage_id', $garage->id)
                ->whereDate('start_date', '<', $endDate)
                ->whereDate('end_date', '>', $startDate)
                ->exists();

            if ($hasConflict) {
                throw new InvalidArgumentException(
                    'The garage is already rented for the selected dates.'
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
     * Get rental listings, summary metrics, and filter criteria.
     *
     * @param array{
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     garage_type?: string|null,
     *     renter_id?: int|string|null
     * } $filters Active filter values.
     * @param int|null $userId ID of the currently authenticated user.
     * @param bool $isAdmin Whether the user has admin privileges.
     * @param int $perPage Number of items per page for pagination.
     * @return array Array containing paginated rents, aggregated financial metrics, category list, and active filters.
     *
     * @throws InvalidArgumentException If start date is after end date.
     */
    public function getRentalReportData(
        array $filters = [],
        ?int $userId = null,
        bool $isAdmin = false,
        int $perPage = 10
    ): array {
        $baseQuery = Rent::query();
        if (!$isAdmin) {
            $baseQuery->where('renter_id', $userId);
        }

        $totalCount = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->whereDate('end_date', '>=', today())->count();
        $completedCount = (clone $baseQuery)->whereDate('end_date', '<', today())->count();

        // Build report query with active filters
        $query = Rent::with(['renter', 'garage', 'vehicle', 'rentDetails', 'payments', 'evCharges'])
            ->when(!$isAdmin, fn($q) => $q->where('renter_id', $userId));

        // 1. Filter by Garage Type
        if (!empty($filters['garage_type'])) {
            $query->whereHas('garage', fn($g) => $g->where('category', $filters['garage_type']));
        }

        // 2. Filter by Renter (Admin only)
        if ($isAdmin && !empty($filters['renter_id'])) {
            $query->where('renter_id', $filters['renter_id']);
        }

        // 3. Filter by Date Range
        if (!empty($filters['start_date'])) {
            $query->whereDate('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('end_date', '<=', $filters['end_date']);
        }

        // Aggregate Report Metrics across all matching records
        $reportCount = (clone $query)->count();
        $reportTotalDays = (clone $query)->sum('total_day');
        $reportRentalRevenue = (clone $query)->sum('total_price');

        $filteredRentIds = (clone $query)->pluck('id');
        $reportEvRevenue = EvCharge::whereIn('rent_id', $filteredRentIds)->sum('price_charge');
        $reportGrandTotal = $reportRentalRevenue + $reportEvRevenue;

        $isFiltered = !empty($filters['start_date'])
            || !empty($filters['end_date'])
            || !empty($filters['garage_type'])
            || ($isAdmin && !empty($filters['renter_id']));

        // Paginate results with persistent query string for pagination links
        $rents = (clone $query)->latest()->paginate($perPage)->withQueryString();

        $garageCategories = ['Car', 'Electric Car', 'Motor Cycle', 'Bike'];
        $renters = $isAdmin ? User::orderBy('name')->get(['id', 'name', 'email']) : collect();

        return [
            'rents' => $rents,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount,
            'completedCount' => $completedCount,
            'reportCount' => $reportCount,
            'reportTotalDays' => $reportTotalDays,
            'reportRentalRevenue' => $reportRentalRevenue,
            'reportEvRevenue' => $reportEvRevenue,
            'reportGrandTotal' => $reportGrandTotal,
            'isFiltered' => $isFiltered,
            'garageCategories' => $garageCategories,
            'renters' => $renters,
        ];
    }
}
