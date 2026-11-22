<?php

namespace Tests\Unit;

use App\Models\Bike;
use App\Models\Car;
use App\Models\ElectricCar;
use App\Models\Garage;
use App\Models\MotorCycle;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    private VehicleService $vehicleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vehicleService = app(VehicleService::class);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'John Doe',
            'email' => fake()->unique()->safeEmail(),
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->unique()->numerify('08##########'),
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_verified' => true,
        ], $attributes));
    }

    // =========================================================================
    // HAPPY PATH (POSITIVE TESTS)
    // =========================================================================

    /**
     * Test creating a regular Car and an Electric Car with total battery kWh capacity.
     */
    public function test_user_can_create_car_and_electric_car_vehicles(): void
    {
        $user = $this->createUser();

        // 1. Regular Car
        $car = $this->vehicleService->create([
            'brand' => 'Toyota Yaris',
            'license_plate' => 'B 1234 XYZ',
            'category' => 'Car',
            'owner_id' => $user->id,
        ]);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertEquals('Toyota Yaris', $car->brand);
        $this->assertEquals('Car', $car->category);
        $this->assertNull($car->total_kwh);
        $this->assertFalse($car->isElectric());

        // 2. Electric Car
        $ev = $this->vehicleService->create([
            'brand' => 'Hyundai Ioniq 5',
            'license_plate' => 'B 5678 EV',
            'total_kwh' => 72.6,
            'category' => 'Electric Car',
            'owner_id' => $user->id,
        ]);

        $this->assertInstanceOf(ElectricCar::class, $ev);
        $this->assertEquals('Hyundai Ioniq 5', $ev->brand);
        $this->assertEquals(72.6, (float) $ev->total_kwh);
        $this->assertTrue($ev->isElectric());
    }

    /**
     * Test creating Bike and MotorCycle vehicles using Single Table Inheritance.
     */
    public function test_user_can_create_bike_and_motorcycle_vehicles(): void
    {
        $user = $this->createUser();

        $bike = $this->vehicleService->create([
            'brand' => 'Polygon Siskiu',
            'category' => 'Bike',
            'owner_id' => $user->id,
        ]);

        $motorcycle = $this->vehicleService->create([
            'brand' => 'Honda Vario 160',
            'license_plate' => 'B 9999 MOT',
            'category' => 'Motor Cycle',
            'owner_id' => $user->id,
        ]);

        $this->assertInstanceOf(Bike::class, $bike);
        $this->assertEquals('Bike', $bike->category);

        $this->assertInstanceOf(MotorCycle::class, $motorcycle);
        $this->assertEquals('Motor Cycle', $motorcycle->category);
    }

    /**
     * Test the vehicle belongsTo owner relationship.
     */
    public function test_vehicle_belongs_to_owner(): void
    {
        $user = $this->createUser(['name' => 'Owner Person']);

        $vehicle = $this->vehicleService->create([
            'brand' => 'Mazda CX-5',
            'license_plate' => 'D 1234 ABC',
            'category' => 'Car',
            'owner_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $vehicle->owner);
        $this->assertEquals($user->id, $vehicle->owner->id);
        $this->assertEquals('Owner Person', $vehicle->owner->name);
    }

    /**
     * Test vehicle compatibility check with various garage types.
     */
    public function test_vehicle_compatibility_with_different_garages(): void
    {
        $user = $this->createUser();

        $car = $this->vehicleService->create(['brand' => 'Honda Civic', 'category' => 'Car', 'owner_id' => $user->id]);
        $ev = $this->vehicleService->create(['brand' => 'Tesla Model 3', 'total_kwh' => 60.0, 'category' => 'Electric Car', 'owner_id' => $user->id]);
        $bike = $this->vehicleService->create(['brand' => 'Brompton', 'category' => 'Bike', 'owner_id' => $user->id]);
        $motorcycle = $this->vehicleService->create(['brand' => 'Yamaha NMAX', 'category' => 'Motor Cycle', 'owner_id' => $user->id]);

        $carGarage = new Garage(['category' => 'Car']);
        $evGarage = new Garage(['category' => 'Electric Car']);
        $bikeGarage = new Garage(['category' => 'Bike']);

        // Car: can park only in Car garage
        $this->assertTrue($car->canParkInGarage($carGarage));
        $this->assertFalse($car->canParkInGarage($bikeGarage));

        // Electric Car: can park in Electric Car garage AND regular Car garage
        $this->assertTrue($ev->canParkInGarage($evGarage));
        $this->assertTrue($ev->canParkInGarage($carGarage));
        $this->assertFalse($ev->canParkInGarage($bikeGarage));

        // Bike and MotorCycle: can park in Bike garage
        $this->assertTrue($bike->canParkInGarage($bikeGarage));
        $this->assertTrue($motorcycle->canParkInGarage($bikeGarage));
        $this->assertFalse($bike->canParkInGarage($carGarage));
    }

    /**
     * Test domain behaviors on ElectricCar model for calculating energy needed and battery checks.
     */
    public function test_electric_car_calculates_energy_needed_and_battery_checks(): void
    {
        $user = $this->createUser();

        /** @var ElectricCar $ev */
        $ev = $this->vehicleService->create([
            'brand' => 'Wuling Air EV',
            'total_kwh' => 26.7,
            'category' => 'Electric Car',
            'owner_id' => $user->id,
        ]);

        // Current battery 40% -> needs 60% of 26.7 kWh = 16.02 kWh
        $this->assertEquals(16.02, $ev->calculateEnergyNeeded(40.0));

        // When battery is already 100% or more -> needs 0 kWh
        $this->assertEquals(0.0, $ev->calculateEnergyNeeded(100.0));
        $this->assertEquals(0.0, $ev->calculateEnergyNeeded(105.0));

        // Battery full checks
        $this->assertTrue($ev->isBatteryFull(100.0));
        $this->assertTrue($ev->isBatteryFull(100.5));
        $this->assertFalse($ev->isBatteryFull(99.9));
        $this->assertFalse($ev->isBatteryFull(40.0));
    }
}
