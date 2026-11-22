<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create an admin user.
     */
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

    /**
     * Helper to create a regular verified renter user.
     */
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

    /**
     * Helper to create a garage facility.
     */
    protected function createGarage(array $attributes = []): Garage
    {
        return Garage::create(array_merge([
            'name' => 'Alpha Garage',
            'category' => 'Car',
            'garage_code' => 'C001',
            'price' => 100000,
            'price_kwh' => null,
            'status' => 'available',
        ], $attributes));
    }

    /**
     * Helper to create a vehicle owned by a user.
     */
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

    /**
     * Helper to create an electric car owned by a user.
     */
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
    // 1. UNIT / SERVICE LOGIC: PRICING FORMULAS (DAILY vs 25x MONTHLY)
    // =========================================================================

    public function test_it_calculates_daily_rental_pricing_correctly(): void
    {
        /** @var RentService $service */
        $service = app(RentService::class);

        // Booking 5 days: 2026-03-01 to 2026-03-06
        $startDate = Carbon::parse('2026-03-01');
        $endDate = Carbon::parse('2026-03-06');
        $dailyPrice = 100000;

        // Use reflection to test private calculatePeriods method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculatePeriods');
        $method->setAccessible(true);

        $periods = $method->invoke($service, $startDate, $endDate, $dailyPrice);

        $this->assertCount(1, $periods);
        $this->assertEquals('daily', $periods[0]['type']);
        $this->assertEquals(5, $periods[0]['total_day']);
        $this->assertEquals(500000, $periods[0]['total_price']);
    }

    public function test_it_calculates_monthly_rental_pricing_as_25_times_daily_price(): void
    {
        /** @var RentService $service */
        $service = app(RentService::class);

        // March has 31 days: 2026-03-01 to 2026-04-01 = 31 days
        $startDate = Carbon::parse('2026-03-01');
        $endDate = Carbon::parse('2026-04-01');
        $dailyPrice = 100000;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculatePeriods');
        $method->setAccessible(true);

        $periods = $method->invoke($service, $startDate, $endDate, $dailyPrice);

        $this->assertCount(1, $periods);
        $this->assertEquals('monthly', $periods[0]['type']);
        $this->assertEquals(31, $periods[0]['total_day']);
        // Monthly price rule: exactly 25 * dailyPrice
        $this->assertEquals(25 * 100000, $periods[0]['total_price']);
    }

    public function test_it_breaks_down_mixed_monthly_and_daily_periods(): void
    {
        /** @var RentService $service */
        $service = app(RentService::class);

        // 45 days in March: 31 days monthly + 14 days daily
        $startDate = Carbon::parse('2026-03-01');
        $endDate = Carbon::parse('2026-04-15');
        $dailyPrice = 100000;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculatePeriods');
        $method->setAccessible(true);

        $periods = $method->invoke($service, $startDate, $endDate, $dailyPrice);

        $this->assertCount(2, $periods);

        // Period 1: Monthly (March = 31 days)
        $this->assertEquals('monthly', $periods[0]['type']);
        $this->assertEquals(31, $periods[0]['total_day']);
        $this->assertEquals(2500000, $periods[0]['total_price']);

        // Period 2: Daily (Remaining 14 days)
        $this->assertEquals('daily', $periods[1]['type']);
        $this->assertEquals(14, $periods[1]['total_day']);
        $this->assertEquals(1400000, $periods[1]['total_price']);

        // Grand Total
        $totalPrice = array_sum(array_column($periods, 'total_price'));
        $this->assertEquals(3900000, $totalPrice);
    }

    public function test_rent_model_calculates_periods_directly_as_domain_behavior(): void
    {
        $rent = new Rent([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-06',
        ]);

        $periods = $rent->calculatePeriods(100000);

        $this->assertCount(1, $periods);
        $this->assertEquals('daily', $periods[0]['type']);
        $this->assertEquals(5, $periods[0]['total_day']);
        $this->assertEquals(500000, $periods[0]['total_price']);
    }

    public function test_rent_model_calculates_extension_periods_as_domain_behavior(): void
    {
        $rent = new Rent([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-06',
        ]);

        $extensionPeriods = $rent->calculateExtensionPeriods('2026-03-10', 100000);

        $this->assertCount(1, $extensionPeriods);
        $this->assertEquals('daily', $extensionPeriods[0]['type']);
        $this->assertEquals(4, $extensionPeriods[0]['total_day']);
        $this->assertEquals(400000, $extensionPeriods[0]['total_price']);
    }

    // =========================================================================
    // 2. HTTP BOOKING TRANSACTION: POST /api/rent
    // =========================================================================

    public function test_verified_renter_can_book_rental(): void
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
            'note' => 'Booking test transaction',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify rental record created in database
        $this->assertDatabaseHas('rents', [
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
            'total_day' => 5,
        ]);

        // Verify garage status changed to 'used'
        $this->assertEquals('used', $garage->fresh()->status);

        // Verify rent_details created with is_extension = false
        $this->assertDatabaseHas('rent_details', [
            'is_extension' => false,
            'total_day' => 5,
        ]);
    }

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

        // Ensure no rental record or garage status was modified
        $this->assertDatabaseCount('rents', 0);
        $this->assertEquals('available', $garage->fresh()->status);
    }

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

    public function test_booking_creates_multiple_rent_details_breakdown_in_database(): void
    {
        Carbon::setTestNow('2026-03-01');

        $renter = $this->createRenter();
        $garage = $this->createGarage(['price' => 100000]);
        $vehicle = $this->createVehicle($renter);

        // 45 days: 2026-03-02 to 2026-04-16
        $startDate = '2026-03-02';
        $endDate = '2026-04-16';

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rent = Rent::with('rentDetails')->first();
        $this->assertNotNull($rent);
        $this->assertEquals(45, $rent->total_day);

        // Should create 2 breakdown details: 1 monthly (31 days) + 1 daily (14 days)
        $this->assertCount(2, $rent->rentDetails);

        $monthlyPeriod = $rent->rentDetails->firstWhere('type', 'monthly');
        $this->assertNotNull($monthlyPeriod);
        $this->assertEquals(31, $monthlyPeriod->total_day);
        $this->assertEquals(25 * 100000, $monthlyPeriod->total_price);
        $this->assertFalse($monthlyPeriod->is_extension);

        $dailyPeriod = $rent->rentDetails->firstWhere('type', 'daily');
        $this->assertNotNull($dailyPeriod);
        $this->assertEquals(14, $dailyPeriod->total_day);
        $this->assertEquals(14 * 100000, $dailyPeriod->total_price);
        $this->assertFalse($dailyPeriod->is_extension);

        $this->assertEquals(3900000, $rent->total_price);

        Carbon::setTestNow(); // reset
    }

    public function test_booking_electric_garage_with_ev_creates_ev_charge_record(): void
    {
        $renter = $this->createRenter();
        $evGarage = $this->createGarage([
            'name' => 'Fast EV Bay',
            'category' => 'Electric Car',
            'garage_code' => 'E001',
            'price' => 150000,
            'price_kwh' => 2500,
        ]);
        $evVehicle = $this->createElectricCar($renter, [
            'total_kwh' => 60.0,
        ]);

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
            'url_payment_receipt' => '/storage/receipts/proof.jpg',
            'ev_charge' => [
                'current_battery' => 40,
                'note' => 'Charge to full capacity',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rent = Rent::first();
        $this->assertNotNull($rent);

        // Required energy = 60 kWh * (100 - 40)% = 36 kWh
        // Charging cost = 36 * 2500 = 90000
        $this->assertDatabaseHas('ev_charges', [
            'rent_id' => $rent->id,
            'current_battery' => 40,
            'price_charge' => 90000,
            'note' => 'Charge to full capacity',
        ]);
    }

    public function test_booking_ev_garage_does_not_create_ev_charge_if_battery_is_already_full(): void
    {
        $renter = $this->createRenter();
        $evGarage = $this->createGarage([
            'name' => 'Fast EV Bay',
            'category' => 'Electric Car',
            'garage_code' => 'E001',
            'price' => 150000,
            'price_kwh' => 2500,
        ]);
        $evVehicle = $this->createElectricCar($renter, [
            'total_kwh' => 60.0,
        ]);

        $startDate = today()->addDay()->toDateString();
        $endDate = today()->addDays(4)->toDateString();

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $evGarage->id,
            'vehicle_id' => $evVehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ev_charge' => [
                'current_battery' => 100, // 100% full, should not trigger EV charge
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('rents', 1);
        $this->assertDatabaseCount('ev_charges', 0);
    }

    public function test_booking_regular_car_garage_with_ev_does_not_create_ev_charge(): void
    {
        $renter = $this->createRenter();
        // Regular Car garage (no charging capability)
        $carGarage = $this->createGarage([
            'name' => 'Standard Car Bay',
            'category' => 'Car',
            'garage_code' => 'C001',
            'price' => 100000,
            'price_kwh' => null,
        ]);
        $evVehicle = $this->createElectricCar($renter);

        $startDate = today()->addDay()->toDateString();
        $endDate = today()->addDays(4)->toDateString();

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.store'), [
            'garage_id' => $carGarage->id,
            'vehicle_id' => $evVehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ev_charge' => [
                'current_battery' => 50,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('rents', 1);
        $this->assertDatabaseCount('ev_charges', 0);
    }

    // =========================================================================
    // 3. AUTHORIZATION: ONLY ADMIN CAN EXTEND RENTAL CONTRACT
    // =========================================================================

    public function test_admin_can_extend_active_rental_contract(): void
    {
        $admin = $this->createAdmin();
        $renter = $this->createRenter();
        $garage = $this->createGarage(['status' => 'used']);
        $vehicle = $this->createVehicle($renter);

        // Initial 3-day rent: today to +3 days
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

        $rent->rentDetails()->create([
            'type' => 'daily',
            'start_date' => $initialStartDate->toDateString(),
            'end_date' => $initialEndDate->toDateString(),
            'total_day' => 3,
            'total_price' => 300000,
            'is_extension' => false,
        ]);

        // Admin extends rental by 2 more days (+3 days -> +5 days)
        $newEndDate = today()->addDays(5)->toDateString();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.rent.extend', $rent->id), [
            'new_end_date' => $newEndDate,
            'note' => 'Extended by admin for 2 extra days',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check that rents table end_date is updated directly
        $rent->refresh();
        $this->assertEquals($newEndDate, $rent->end_date->toDateString());
        $this->assertEquals(5, $rent->total_day);
        $this->assertEquals(500000, $rent->total_price);

        // Check that an extension record exists in rent_details
        $this->assertDatabaseHas('rent_details', [
            'rent_id' => $rent->id,
            'is_extension' => true,
            'total_day' => 2,
            'total_price' => 200000,
        ]);
    }

    public function test_extension_is_rejected_if_another_user_already_booked_the_garage_on_extension_dates(): void
    {
        $admin = $this->createAdmin();
        $userA = $this->createRenter(['email' => 'user_a@test.com']);
        $userB = $this->createRenter(['email' => 'user_b@test.com']);
        $garage = $this->createGarage(['price' => 100000, 'status' => 'used']);

        $vehicleA = $this->createVehicle($userA, ['license_plate' => 'B 1111 AAA']);
        $vehicleB = $this->createVehicle($userB, ['license_plate' => 'B 2222 BBB']);

        // User A rents garage from today to +4 days (e.g. May 11 - May 15)
        $rentA = Rent::create([
            'total_day' => 4,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(4)->toDateString(),
            'total_price' => 400000,
            'renter_id' => $userA->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicleA->id,
        ]);

        // User B already booked the same garage from +4 days to +9 days (e.g. May 15 - May 20)
        Rent::create([
            'total_day' => 5,
            'start_date' => today()->addDays(4)->toDateString(),
            'end_date' => today()->addDays(9)->toDateString(),
            'total_price' => 500000,
            'renter_id' => $userB->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicleB->id,
        ]);

        // User A attempts to extend to +7 days (May 18), which overlaps with User B's booking
        $newEndDate = today()->addDays(7)->toDateString();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.rent.extend', $rentA->id), [
            'new_end_date' => $newEndDate,
            'note' => 'Attempting extension into User B booked dates',
        ]);

        // Extension must be immediately rejected with an error message
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'The garage is already booked by another reservation',
            session('error')
        );

        // Rent A must NOT be extended (end_date remains +4 days)
        $rentA->refresh();
        $this->assertEquals(today()->addDays(4)->toDateString(), $rentA->end_date->toDateString());
        $this->assertEquals(4, $rentA->total_day);
        $this->assertEquals(400000, $rentA->total_price);

        // No extension details created
        $this->assertDatabaseMissing('rent_details', [
            'rent_id' => $rentA->id,
            'is_extension' => true,
        ]);
    }

    public function test_regular_user_cannot_extend_rental_contract(): void
    {
        $renter = $this->createRenter();
        $garage = $this->createGarage(['status' => 'used']);
        $vehicle = $this->createVehicle($renter);

        $rent = Rent::create([
            'total_day' => 3,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(3)->toDateString(),
            'total_price' => 300000,
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // Regular user attempts to extend -> admin.only middleware redirects to welcome
        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.rent.extend', $rent->id), [
            'new_end_date' => today()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect(route('welcome'));
        $response->assertSessionHas('error', 'This route is for admin only');

        // Verify end_date was NOT modified
        $this->assertEquals(today()->addDays(3)->toDateString(), $rent->fresh()->end_date->toDateString());
    }

    public function test_unauthenticated_guest_cannot_extend_rental_contract(): void
    {
        $renter = $this->createRenter();
        $garage = $this->createGarage();
        $vehicle = $this->createVehicle($renter);

        $rent = Rent::create([
            'total_day' => 2,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(2)->toDateString(),
            'total_price' => 200000,
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response = $this->post(route('api.rent.extend', $rent->id), [
            'new_end_date' => today()->addDays(4)->toDateString(),
        ]);

        // Guest is blocked by auth.session middleware with 401 Unauthorized for api/* routes
        $response->assertStatus(401);
    }

    // =========================================================================
    // 4. REPORTING & FILTERING: GET /rents
    // =========================================================================

    public function test_filter_rentals_by_date_range_using_start_date_and_end_date(): void
    {
        $admin = $this->createAdmin();
        $renter = $this->createRenter();
        $garage = $this->createGarage();
        $vehicle = $this->createVehicle($renter);

        // Rent 1: In range (2026-05-01 to 2026-05-10)
        Rent::create([
            'total_day' => 9,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-10',
            'total_price' => 900000,
            'note' => 'Target Match',
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // Rent 2: Outside range (2026-06-01 to 2026-06-15)
        Rent::create([
            'total_day' => 14,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
            'total_price' => 1400000,
            'note' => 'Out of range',
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->get(route('rents.index', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-20',
        ]));

        $response->assertOk();
        $response->assertViewHas('rents', function ($rents) {
            return $rents->total() === 1 && $rents->first()->note === 'Target Match';
        });
        $response->assertViewHas('reportRentalRevenue', 900000);
    }

    public function test_filter_rentals_by_garage_type(): void
    {
        $admin = $this->createAdmin();
        $renter = $this->createRenter();

        $carGarage = $this->createGarage(['category' => 'Car', 'garage_code' => 'C001']);
        $bikeGarage = $this->createGarage(['category' => 'Bike', 'garage_code' => 'S001']);

        $car = $this->createVehicle($renter, ['category' => 'Car']);
        $bike = $this->createVehicle($renter, ['category' => 'Bike']);

        // Car Rent
        Rent::create([
            'total_day' => 2,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(2)->toDateString(),
            'total_price' => 200000,
            'renter_id' => $renter->id,
            'garage_id' => $carGarage->id,
            'vehicle_id' => $car->id,
        ]);

        // Bike Rent
        Rent::create([
            'total_day' => 2,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(2)->toDateString(),
            'total_price' => 100000,
            'renter_id' => $renter->id,
            'garage_id' => $bikeGarage->id,
            'vehicle_id' => $bike->id,
        ]);

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->get(route('rents.index', [
            'garage_type' => 'Car',
        ]));

        $response->assertOk();
        $response->assertViewHas('rents', function ($rents) {
            return $rents->total() === 1 && $rents->first()->garage->category === 'Car';
        });
    }

    public function test_regular_user_only_sees_their_own_rentals(): void
    {
        $userA = $this->createRenter(['email' => 'user_a@garage.com']);
        $userB = $this->createRenter(['email' => 'user_b@garage.com']);

        $garageA = $this->createGarage(['garage_code' => 'C001']);
        $garageB = $this->createGarage(['garage_code' => 'C002']);

        $vehicleA = $this->createVehicle($userA);
        $vehicleB = $this->createVehicle($userB);

        // Rent belonging to User A
        Rent::create([
            'total_day' => 3,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(3)->toDateString(),
            'total_price' => 300000,
            'renter_id' => $userA->id,
            'garage_id' => $garageA->id,
            'vehicle_id' => $vehicleA->id,
        ]);

        // Rent belonging to User B
        Rent::create([
            'total_day' => 3,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(3)->toDateString(),
            'total_price' => 300000,
            'renter_id' => $userB->id,
            'garage_id' => $garageB->id,
            'vehicle_id' => $vehicleB->id,
        ]);

        // User A visits /rents
        $response = $this->withSession([
            'user_id' => $userA->id,
            'user_role' => 'user',
        ])->get(route('rents.index'));

        $response->assertOk();
        // User A must only see 1 rental (their own)
        $response->assertViewHas('rents', function ($rents) use ($userA) {
            return $rents->total() === 1 && $rents->first()->renter_id === $userA->id;
        });
    }
}
