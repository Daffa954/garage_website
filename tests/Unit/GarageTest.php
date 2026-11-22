<?php

namespace Tests\Unit;

use App\Models\Garage;
use App\Models\User;
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
    // 1. HAPPY PATH (POSITIVE TESTS)
    // =========================================================================

    /**
     * Test garage model availability lifecycle: available -> used -> available.
     */
    public function test_garage_availability_status_transitions(): void
    {
        $garage = Garage::create([
            'garage_code' => 'C001',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'available',
        ]);

        $this->assertTrue($garage->isAvailable());

        // Mark as used
        $garage->markAsUsed();
        $this->assertEquals('used', $garage->fresh()->status);
        $this->assertFalse($garage->isAvailable());

        // Mark back as available
        $garage->markAsAvailable();
        $this->assertEquals('available', $garage->fresh()->status);
        $this->assertTrue($garage->isAvailable());
    }

    /**
     * Test sequential code generation for all categories: S (Bike/Motor), C (Car), E (EV).
     */
    public function test_garage_code_generation_for_all_categories(): void
    {
        $bike = $this->garageService->create(['price' => 20000, 'category' => 'Bike']);
        $motorcycle = $this->garageService->create(['price' => 30000, 'category' => 'Motor Cycle']);
        $car1 = $this->garageService->create(['price' => 100000, 'category' => 'Car']);
        $car2 = $this->garageService->create(['price' => 100000, 'category' => 'Car']);
        $ev = $this->garageService->create(['price' => 150000, 'price_kwh' => 2500, 'category' => 'Electric Car']);

        $this->assertEquals('S001', $bike->garage_code);
        $this->assertEquals('S002', $motorcycle->garage_code);
        $this->assertEquals('C001', $car1->garage_code);
        $this->assertEquals('C002', $car2->garage_code);
        $this->assertEquals('E001', $ev->garage_code);
        $this->assertEquals(2500, $ev->price_kwh);
    }

    /**
     * Test admin can create a single garage and batch create multiple garages.
     */
    public function test_admin_can_create_single_and_batch_garages(): void
    {
        $admin = $this->createAdmin();

        // 1. Single creation via HTTP API
        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.garage.store'), [
            'price' => 100000,
            'category' => 'Car',
            'quantity' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('garages', ['garage_code' => 'C001', 'status' => 'available']);

        // 2. Batch creation via Service
        $batch = $this->garageService->createBatch([
            'category' => 'Car',
            'price' => 100000,
            'quantity' => 4,
        ]);

        $this->assertCount(4, $batch);
        $this->assertEquals('C002', $batch[0]->garage_code);
        $this->assertEquals('C005', $batch[3]->garage_code);
        $this->assertDatabaseCount('garages', 5);
    }

    /**
     * Test creating a non-electric garage automatically nullifies price_kwh.
     */
    public function test_creating_non_electric_car_sets_price_kwh_to_null(): void
    {
        $admin = $this->createAdmin();

        $response = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.garage.store'), [
            'price' => 80000,
            'category' => 'Car',
            'price_kwh' => 2500, // Should be nullified for Car
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('garages', [
            'garage_code' => 'C001',
            'category' => 'Car',
            'price_kwh' => null,
        ]);
    }

    // =========================================================================
    // 2. NEGATIVE TESTS
    // =========================================================================

    /**
     * Test garage creation fails on invalid category or exceeding maximum capacity 999.
     */
    public function test_garage_creation_fails_on_invalid_category_or_exceeding_capacity_999(): void
    {
        // 1. Invalid category
        try {
            $this->garageService->create(['price' => 100000, 'category' => 'Truck']);
            $this->fail('Expected exception for invalid category not thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Invalid garage category', $e->getMessage());
        }

        // 2. Capacity reached 999
        Garage::create([
            'garage_code' => 'C999',
            'price' => 100000,
            'category' => 'Car',
            'status' => 'available',
        ]);

        try {
            $this->garageService->create(['price' => 100000, 'category' => 'Car']);
            $this->fail('Expected exception for capacity limit not thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Maximum garage capacity reached', $e->getMessage());
        }
    }

    /**
     * Test garage creation is restricted to admin only (rejects guest and regular user).
     */
    public function test_garage_creation_is_admin_only(): void
    {
        // 1. Guest
        $guestResponse = $this->post(route('api.garage.store'), [
            'price' => 100000,
            'category' => 'Car',
        ]);
        $guestResponse->assertUnauthorized();

        // 2. Regular User
        $renter = $this->createRenter();
        $userResponse = $this->withSession([
            'user_id' => $renter->id,
            'user_role' => 'user',
        ])->post(route('api.garage.store'), [
            'price' => 100000,
            'category' => 'Car',
        ]);
        $userResponse->assertRedirect(route('welcome'));
        $userResponse->assertSessionHas('error', 'This route is for admin only');

        $this->assertDatabaseCount('garages', 0);
    }

    /**
     * Test garage creation validation rules reject invalid price and missing EV price_kwh.
     */
    public function test_garage_creation_validation_rules(): void
    {
        $admin = $this->createAdmin();

        // Invalid negative price
        $resNegative = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.garage.store'), [
            'price' => -5000,
            'category' => 'Car',
        ]);
        $resNegative->assertRedirect();
        $resNegative->assertSessionHasErrors(['price']);

        // Missing price_kwh for Electric Car
        $resMissingKwh = $this->withSession([
            'user_id' => $admin->id,
            'user_role' => 'admin',
        ])->post(route('api.garage.store'), [
            'price' => 150000,
            'category' => 'Electric Car',
            'price_kwh' => null,
        ]);
        $resMissingKwh->assertRedirect();
        $resMissingKwh->assertSessionHasErrors(['price_kwh']);

        $this->assertDatabaseCount('garages', 0);
    }
}
