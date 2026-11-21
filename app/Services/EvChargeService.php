<?php

namespace App\Services;

use App\Models\EvCharge;
use App\Models\Garage;
use App\Models\Rent;
use App\Models\Vehicle;
use InvalidArgumentException;
use RuntimeException;

class EvChargeService
{
    /**
     * Create a new EV charging transaction record for an Electric Car rental.
     *
     * @param Rent $rent The related rental transaction.
     * @param array|Vehicle $dataOrVehicle EV charge data payload or vehicle instance.
     * @param mixed $garageOrData Related garage or charge payload.
     * @param array|null $data EV charge payload if 4 arguments passed.
     * @return EvCharge
     * @throws InvalidArgumentException If vehicle or garage is not an Electric Car, or battery is invalid.
     * @throws RuntimeException If garage kWh price or vehicle battery capacity is unconfigured.
     */
    public function create(
        Rent $rent,
        array|Vehicle $dataOrVehicle,
        mixed $garageOrData = null,
        ?array $data = null
    ): EvCharge {
        if ($dataOrVehicle instanceof Vehicle) {
            $vehicle = $dataOrVehicle;
            $garage = $garageOrData;
            $chargeData = $data ?? [];
        } else {
            $vehicle = $rent->vehicle;
            $garage = $rent->garage;
            $chargeData = $dataOrVehicle;
        }

        if (!$vehicle || $vehicle->category !== 'Electric Car') {
            throw new InvalidArgumentException(
                'Only electric vehicles can use EV charging.'
            );
        }

        if (!$garage || $garage->category !== 'Electric Car') {
            throw new InvalidArgumentException(
                'EV charging is only available in Electric Car garages.'
            );
        }

        $currentBattery = (float) ($chargeData['current_battery'] ?? 0);

        if ($currentBattery < 0 || $currentBattery > 100) {
            throw new InvalidArgumentException(
                'Battery percentage must be between 0 and 100.'
            );
        }

        if ($garage->price_kwh === null) {
            throw new RuntimeException(
                'Charging price per kWh has not been configured.'
            );
        }

        $batteryCapacity = (float) $vehicle->total_kwh;

        if ($batteryCapacity <= 0) {
            throw new RuntimeException(
                'Invalid vehicle battery capacity.'
            );
        }

        // Calculate required energy to reach 100%
        $energyNeeded = $batteryCapacity
            * ((100 - $currentBattery) / 100);

        // Calculate charging cost
        $chargingCost = (int) round(
            $energyNeeded * $garage->price_kwh
        );

        return EvCharge::create([
            'current_battery' => $currentBattery,
            'price_charge' => $chargingCost,
            'note' => $chargeData['note'] ?? null,
            'url_payment_receipt' => $chargeData['url_payment_receipt'],
            'rent_id' => $rent->id,
        ]);
    }
}
