<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GarageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GarageTest extends TestCase
{
    use RefreshDatabase;

    private GarageService $garageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->garageService = app(GarageService::class);
    }

    protected function createRenter(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->unique()->numerify('08##########'),
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_verified' => true,
        ], $attributes));
    }

    protected function createAdmin(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Admin Boss',
            'email' => fake()->unique()->safeEmail(),
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->unique()->numerify('08##########'),
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_verified' => true,
        ], $attributes));
    }

    // =========================================================================
    // 1. MODEL BEHAVIOR & RELATIONSHIPS
    // =========================================================================

    public function test_it_checks_if_garage_is_available(): void
    {
        $availableGarage = Garage::create([
            'garage_code' => 'C001',
            'name' => 'Car Bay 1',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'available',
        ]);

        $usedGarage = Garage::create([
            'garage_code' => 'C002',
            'name' => 'Car Bay 2',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'used',
        ]);

        $this->assertTrue($availableGarage->isAvailable());
        $this->assertFalse($usedGarage->isAvailable());
    }

    public function test_it_marks_garage_as_used(): void
    {
        $garage = Garage::create([
            'garage_code' => 'C001',
            'name' => 'Car Bay 1',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'available',
        ]);

        $garage->markAsUsed();

        $this->assertEquals('used', $garage->fresh()->status);
        $this->assertFalse($garage->isAvailable());
    }

    public function test_it_marks_garage_as_available(): void
    {
        $garage = Garage::create([
            'garage_code' => 'C001',
            'name' => 'Car Bay 1',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'used',
        ]);

        $garage->markAsAvailable();

        $this->assertEquals('available', $garage->fresh()->status);
        $this->assertTrue($garage->isAvailable());
    }

    public function test_garage_has_many_rents_and_active_rent_relationship(): void
    {
        $renter = $this->createRenter();
        $garage = Garage::create([
            'garage_code' => 'C001',
            'name' => 'Car Bay 1',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'used',
        ]);
        $vehicle = Vehicle::create([
            'brand' => 'Toyota Avanza',
            'license_plate' => 'B 1234 ABC',
            'category' => 'Car',
            'owner_id' => $renter->id,
        ]);

        $firstRent = Rent::create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-05',
            'total_day' => 4,
            'total_price' => 400000,
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $secondRent = Rent::create([
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-05',
            'total_day' => 4,
            'total_price' => 400000,
            'renter_id' => $renter->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertCount(2, $garage->rents);
        $this->assertEquals($secondRent->id, $garage->activeRent->id);
    }

    // =========================================================================
    // 2. GARAGE SERVICE LOGIC: CODE GENERATION & CAPACITY
    // =========================================================================

    public function test_it_generates_code_with_prefix_S_for_bike_and_motorcycle(): void
    {
        $bike = $this->garageService->create([
            'name' => 'Bike Spot 1',
            'price' => 20000,
            'category' => 'Bike',
        ]);

        $motorcycle = $this->garageService->create([
            'name' => 'Motor Spot 2',
            'price' => 30000,
            'category' => 'Motor Cycle',
        ]);

        $this->assertEquals('S001', $bike->garage_code);
        $this->assertEquals('S002', $motorcycle->garage_code);
    }

    public function test_it_generates_code_with_prefix_C_for_car(): void
    {
        $car = $this->garageService->create([
            'name' => 'Car Space 1',
            'price' => 100000,
            'category' => 'Car',
        ]);

        $this->assertEquals('C001', $car->garage_code);
    }

    public function test_it_generates_code_with_prefix_E_for_electric_car(): void
    {
        $ev = $this->garageService->create([
            'name' => 'EV Spot 1',
            'price' => 150000,
            'price_kwh' => 2500,
            'category' => 'Electric Car',
        ]);

        $this->assertEquals('E001', $ev->garage_code);
        $this->assertEquals(2500, $ev->price_kwh);
    }

    public function test_it_increments_code_number_sequentially_per_category(): void
    {
        $car1 = $this->garageService->create([
            'name' => 'Car 1',
            'price' => 100000,
            'category' => 'Car',
        ]);

        $car2 = $this->garageService->create([
            'name' => 'Car 2',
            'price' => 100000,
            'category' => 'Car',
        ]);

        $bike1 = $this->garageService->create([
            'name' => 'Bike 1',
            'price' => 20000,
            'category' => 'Bike',
        ]);

        $this->assertEquals('C001', $car1->garage_code);
        $this->assertEquals('C002', $car2->garage_code);
        $this->assertEquals('S001', $bike1->garage_code);
    }

    public function test_it_throws_exception_if_category_is_invalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid garage category.');

        $this->garageService->create([
            'name' => 'Truck Spot',
            'price' => 200000,
            'category' => 'Truck',
        ]);
    }

    public function test_it_throws_exception_when_capacity_exceeds_maximum_999(): void
    {
        // Seed a garage with number 999
        Garage::create([
            'garage_code' => 'C999',
            'name' => 'Car 999',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'available',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Maximum garage capacity reached.');

        $this->garageService->create([
            'name' => 'Car 1000',
            'price' => 100000,
            'category' => 'Car',
        ]);
    }

    public function test_newly_created_garage_has_status_available(): void
    {
        $garage = $this->garageService->create([
            'name' => 'Fresh Garage',
            'price' => 100000,
            'category' => 'Car',
        ]);

        $this->assertEquals('available', $garage->status);
        $this->assertTrue($garage->isAvailable());
    }

    // =========================================================================
    // 3. HTTP / CONTROLLER: AUTHORIZATION (ONLY ADMIN CAN CREATE GARAGE)
    // =========================================================================

    public function test_guest_cannot_create_garage(): void
    {
        $response = $this->post(route('api.garage.store'), [
            'name' => 'Alpha Garage',
            'price' => 100000,
            'category' => 'Car',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('garages', 0);
    }

    public function test_regular_user_cannot_create_garage(): void
    {
        $renter = $this->createRenter();

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.garage.store'), [
            'name' => 'Alpha Garage',
            'price' => 100000,
            'category' => 'Car',
        ]);

        $response->assertRedirect(route('welcome'));
        $response->assertSessionHas('error', 'This route is for admin only');
        $this->assertDatabaseCount('garages', 0);
    }

    public function test_admin_can_create_garage_successfully(): void
    {
        $admin = $this->createAdmin();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->from(route('garages.index'))->post(route('api.garage.store'), [
            'name' => 'Alpha Garage',
            'price' => 100000,
            'category' => 'Car',
        ]);

        $response->assertRedirect(route('garages.index'));
        $response->assertSessionHas('success', 'Garage created successfully.');

        $this->assertDatabaseHas('garages', [
            'garage_code' => 'C001',
            'name' => 'Alpha Garage',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'available',
        ]);
    }

    // =========================================================================
    // 4. HTTP / CONTROLLER: EV PRICE_KWH & FORM VALIDATION
    // =========================================================================

    public function test_creating_electric_car_garage_requires_price_kwh(): void
    {
        $admin = $this->createAdmin();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.garage.store'), [
            'name' => 'EV Fast Charging Bay',
            'price' => 150000,
            'category' => 'Electric Car',
            'price_kwh' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['price_kwh']);
        $this->assertDatabaseCount('garages', 0);
    }

    public function test_creating_non_electric_car_sets_price_kwh_to_null(): void
    {
        $admin = $this->createAdmin();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.garage.store'), [
            'name' => 'Standard Car Bay',
            'price' => 80000,
            'category' => 'Car',
            'price_kwh' => 2500, // Should be ignored/nullified for non-EV
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('garages', [
            'garage_code' => 'C001',
            'category' => 'Car',
            'price_kwh' => null,
        ]);
    }

    public function test_garage_creation_validation_rules(): void
    {
        $admin = $this->createAdmin();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.garage.store'), [
            'name' => '',
            'price' => -5000,
            'category' => 'InvalidCategory',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'price', 'category']);
        $this->assertDatabaseCount('garages', 0);
    }

    // =========================================================================
    // 5. HTTP / CONTROLLER: GARAGE LISTING (INDEX)
    // =========================================================================

    public function test_authenticated_user_can_view_garage_index_page(): void
    {
        $renter = $this->createRenter();

        Garage::create([
            'garage_code' => 'C001',
            'name' => 'Bay 1',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'available',
        ]);

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->get(route('garages.index'));

        $response->assertOk();
        $response->assertViewIs('garage');
        $response->assertViewHas('garages');
    }

    public function test_garage_index_filters_out_vehicles_parked_in_occupied_garages(): void
    {
        $renter = $this->createRenter();

        $occupiedGarage = Garage::create([
            'garage_code' => 'C001',
            'name' => 'Occupied Bay',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'used',
        ]);

        $parkedVehicle = Vehicle::create([
            'brand' => 'Car in Use',
            'license_plate' => 'B 1111 USE',
            'category' => 'Car',
            'owner_id' => $renter->id,
        ]);

        Rent::create([
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(5)->toDateString(),
            'total_day' => 5,
            'total_price' => 500000,
            'renter_id' => $renter->id,
            'garage_id' => $occupiedGarage->id,
            'vehicle_id' => $parkedVehicle->id,
        ]);

        $freeVehicle = Vehicle::create([
            'brand' => 'Available Car',
            'license_plate' => 'B 2222 FREE',
            'category' => 'Car',
            'owner_id' => $renter->id,
        ]);

        $response = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->get(route('garages.index'));

        $response->assertOk();
        $vehiclesInView = $response->viewData('vehicles');

        $this->assertTrue($vehiclesInView->contains('id', $freeVehicle->id));
        $this->assertFalse($vehiclesInView->contains('id', $parkedVehicle->id));
    }
}
