<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Admin User',
            'email' => fake()->unique()->safeEmail(),
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->unique()->numerify('08##########'),
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_verified' => true,
        ], $attributes));
    }

    protected function createRenter(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'John Renter',
            'email' => fake()->unique()->safeEmail(),
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->unique()->numerify('08##########'),
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_verified' => true,
        ], $attributes));
    }

    protected function createGarage(array $attributes = []): Garage
    {
        return Garage::create(array_merge([
            'category' => 'Car',
            'garage_code' => 'C001',
            'price' => 100000,
            'price_kwh' => null,
            'status' => 'available',
        ], $attributes));
    }

    protected function createVehicle(User $owner, array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'brand' => 'Toyota Avanza',
            'license_plate' => 'B 1234 ABC',
            'category' => 'Car',
            'total_kwh' => null,
            'owner_id' => $owner->id,
        ], $attributes));
    }

    protected function createElectricCar(User $owner, array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'brand' => 'Hyundai Ioniq 5',
            'license_plate' => 'B 9999 EV',
            'category' => 'Electric Car',
            'total_kwh' => 60.0,
            'owner_id' => $owner->id,
        ], $attributes));
    }

    // =========================================================================
    // 1. HAPPY PATH (POSITIVE TESTS)
    // =========================================================================

    /**
     * Test rent model domain behavior: calculates pricing breakdown into daily and monthly packages.
     */
    public function test_rent_pricing_breakdown_daily_and_monthly(): void
    {
        // 5 days: purely daily (5 * 100.000 = 500.000)
        $rentDaily = new Rent([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-06',
        ]);
        $periods = $rentDaily->calculatePeriods(100000);
        $this->assertCount(1, $periods);
        $this->assertEquals('daily', $periods[0]['type']);
        $this->assertEquals(5, $periods[0]['total_day']);
        $this->assertEquals(500000, $periods[0]['total_price']);

        // 31 days in March: monthly rate is calculated as 25x daily price = 2.500.000
        $rentMonthly = new Rent([
            'start_date' => '2026-03-01',
            'end_date' => '2026-04-01',
        ]);
        $monthlyPeriods = $rentMonthly->calculatePeriods(100000);
        $this->assertCount(1, $monthlyPeriods);
        $this->assertEquals('monthly', $monthlyPeriods[0]['type']);
        $this->assertEquals(31, $monthlyPeriods[0]['total_day']);
        $this->assertEquals(2500000, $monthlyPeriods[0]['total_price']);

        // 2 Months (Nov 22 to Jan 22 = 61 days: Nov 30 days + Dec 31 days)
        // Should produce exactly 2 monthly periods without residual daily period
        $rentTwoMonths = new Rent([
            'start_date' => '2026-11-22',
            'end_date' => '2027-01-22',
        ]);
        $twoMonthPeriods = $rentTwoMonths->calculatePeriods(100000);
        $this->assertCount(2, $twoMonthPeriods);
        $this->assertEquals('monthly', $twoMonthPeriods[0]['type']);
        $this->assertEquals(30, $twoMonthPeriods[0]['total_day']);
        $this->assertEquals(2500000, $twoMonthPeriods[0]['total_price']);
        $this->assertEquals('monthly', $twoMonthPeriods[1]['type']);
        $this->assertEquals(31, $twoMonthPeriods[1]['total_day']);
        $this->assertEquals(2500000, $twoMonthPeriods[1]['total_price']);
    }

    /**
     * Test verified renter successfully books a garage, creating breakdown details and updating status.
     */
    public function test_verified_renter_can_book_rental_with_details(): void
    {
        $renter = $this->createRenter();
        $garage = $this->createGarage();
        $vehicle = $this->createVehicle($renter);

        $startDate = today()->addDay()->toDateString();
        $endDate = today()->addDays(6)->toDateString();

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'url_payment_receipt' => '/storage/receipts/proof.jpg',
            'note' => 'Booking test transaction',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rents', [
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
            'total_day' => 5,
        ]);

        $this->assertEquals('used', $garage->fresh()->status);
        $this->assertDatabaseHas('rent_details', ['is_extension' => false, 'total_day' => 5]);
        $this->assertDatabaseHas('rent_payments', ['url_payment_receipt' => '/storage/receipts/proof.jpg']);
    }

    /**
     * Test booking an electric garage with an EV creates an ev_charges record.
     */
    public function test_booking_electric_garage_with_ev_creates_ev_charge(): void
    {
        $renter = $this->createRenter();
        $evGarage = $this->createGarage([
            'category' => 'Electric Car',
            'garage_code' => 'E001',
            'price' => 150000,
            'price_kwh' => 2500,
        ]);
        $evVehicle = $this->createElectricCar($renter, ['total_kwh' => 60.0]);

        $startDate = today()->addDay()->toDateString();
        $endDate = today()->addDays(6)->toDateString();

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $evGarage->id,
            'vehicle_id' => $evVehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ev_charge' => [
                'current_battery' => 40,
                'note' => 'Charge to full',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rent = Rent::first();
        // Required energy = 60 kWh * (100 - 40)% = 36 kWh -> Cost = 36 * 2500 = 90.000
        $this->assertDatabaseHas('ev_charges', [
            'rent_id' => $rent->id,
            'current_battery' => 40,
            'price_charge' => 90000,
        ]);
    }

    /**
     * Test admin can extend an active rental contract.
     */
    public function test_admin_can_extend_active_rental_contract(): void
    {
        $admin = $this->createAdmin();
        $renter = $this->createRenter();
        $garage = $this->createGarage(['status' => 'used']);
        $vehicle = $this->createVehicle($renter);

        $initialStartDate = today();
        $initialEndDate = today()->addDays(3);

        $rent = Rent::create([
            'total_day' => 3,
            'start_date' => $initialStartDate->toDateString(),
            'end_date' => $initialEndDate->toDateString(),
            'total_price' => 300000,
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $newEndDate = today()->addDays(5)->toDateString();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.rent.extend', $rent->id), [
            'new_end_date' => $newEndDate,
            'note' => 'Extended by admin',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rent->refresh();
        $this->assertEquals($newEndDate, $rent->end_date->toDateString());
        $this->assertEquals(5, $rent->total_day);
        $this->assertEquals(500000, $rent->total_price);
        $this->assertDatabaseHas('rent_details', ['rent_id' => $rent->id, 'is_extension' => true]);
    }

    // =========================================================================
    // 2. NEGATIVE TESTS
    // =========================================================================

    /**
     * Test booking fails when renter is not verified.
     */
    public function test_unverified_renter_cannot_book_rental(): void
    {
        $unverifiedRenter = $this->createRenter(['is_verified' => false]);
        $garage = $this->createGarage();
        $vehicle = $this->createVehicle($unverifiedRenter);

        $response = $this->withSession([
            'user_id' => $unverifiedRenter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => today()->addDay()->toDateString(),
            'end_date' => today()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect(route('welcome'));
        $response->assertSessionHas('error', 'Your account is not verified');
        $this->assertDatabaseCount('rents', 0);
    }

    /**
     * Test booking fails when vehicle is incompatible with the garage category.
     */
    public function test_booking_fails_if_vehicle_is_incompatible_with_garage(): void
    {
        $renter = $this->createRenter();
        $garage = $this->createGarage(['category' => 'Bike', 'garage_code' => 'S001']);
        $car = $this->createVehicle($renter, ['category' => 'Car']); // Car cannot park in Bike garage

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $garage->id,
            'vehicle_id' => $car->id,
            'start_date' => today()->addDay()->toDateString(),
            'end_date' => today()->addDays(4)->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['vehicle_id']);
        $this->assertDatabaseCount('rents', 0);
    }

    /**
     * Test booking cannot be made for dates overlapping an existing booking.
     */
    public function test_cannot_rent_garage_for_overlapping_dates(): void
    {
        $renter1 = $this->createRenter(['email' => 'renter1@test.com']);
        $renter2 = $this->createRenter(['email' => 'renter2@test.com']);
        $garage = $this->createGarage();

        $vehicle1 = $this->createVehicle($renter1, ['license_plate' => 'B 1111 AAA']);
        $vehicle2 = $this->createVehicle($renter2, ['license_plate' => 'B 2222 BBB']);

        // Existing booking: Day 1 to Day 5
        Rent::create([
            'total_day' => 4,
            'start_date' => today()->addDay()->toDateString(),
            'end_date' => today()->addDays(5)->toDateString(),
            'total_price' => 400000,
            'renter_id' => $renter1->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle1->id,
        ]);

        // Attempt overlapping booking: Day 3 to Day 7
        $response = $this->withSession([
            'user_id' => $renter2->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle2->id,
            'start_date' => today()->addDays(3)->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('already rented for the selected dates', session('error'));
        $this->assertDatabaseCount('rents', 1);
    }

    /**
     * Test extension fails if requested by non-admin or if overlapping another booking.
     */
    public function test_extension_fails_for_non_admin_or_overlapping_dates(): void
    {
        $admin = $this->createAdmin();
        $renter = $this->createRenter(['email' => 'renter_a@test.com']);
        $otherUser = $this->createRenter(['email' => 'renter_b@test.com']);
        $garage = $this->createGarage(['status' => 'used']);
        $vehicle = $this->createVehicle($renter);
        $otherVehicle = $this->createVehicle($otherUser, ['license_plate' => 'B 9999 OTH']);

        $rent = Rent::create([
            'total_day' => 4,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(4)->toDateString(),
            'total_price' => 400000,
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // 1. Regular user cannot extend (admin only)
        $userResponse = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.extend', $rent->id), [
            'new_end_date' => today()->addDays(6)->toDateString(),
        ]);
        $userResponse->assertRedirect(route('welcome'));
        $userResponse->assertSessionHas('error', 'This route is for admin only');

        // 2. Other user already booked future dates (+4 to +9)
        Rent::create([
            'total_day' => 5,
            'start_date' => today()->addDays(4)->toDateString(),
            'end_date' => today()->addDays(9)->toDateString(),
            'total_price' => 500000,
            'renter_id' => $otherUser->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $otherVehicle->id,
        ]);

        // Admin attempts extension into overlapping dates
        $adminResponse = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.rent.extend', $rent->id), [
            'new_end_date' => today()->addDays(7)->toDateString(),
        ]);
        $adminResponse->assertRedirect();
        $adminResponse->assertSessionHas('error');
        $this->assertStringContainsString('already booked by another reservation', session('error'));
    }
}
