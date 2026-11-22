<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $nik
 * @property string $phone
 * @property string $role
 * @property bool $is_verified
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vehicle> $vehicles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Bike> $bikes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MotorCycle> $motorCycles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Car> $cars
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ElectricCar> $electricCars
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rent> $rents
 */
#[Fillable(['name', 'email', 'password', 'nik', 'role', 'phone', 'is_verified'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string> Array of attribute cast definitions.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Get the one-to-many relationship to all vehicles owned by this user.
     *
     * @return HasMany HasMany relationship instance to Vehicle model.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_id');
    }

    /**
     * Get the one-to-many relationship to bicycles owned by this user.
     *
     * @return HasMany HasMany relationship instance to Bike model.
     */
    public function bikes(): HasMany
    {
        return $this->hasMany(Bike::class, 'owner_id');
    }

    /**
     * Get the one-to-many relationship to motorcycles owned by this user.
     *
     * @return HasMany HasMany relationship instance to MotorCycle model.
     */
    public function motorCycles(): HasMany
    {
        return $this->hasMany(MotorCycle::class, 'owner_id');
    }

    /**
     * Get the one-to-many relationship to gasoline cars owned by this user.
     *
     * @return HasMany HasMany relationship instance to Car model.
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'owner_id');
    }

    /**
     * Get the one-to-many relationship to electric cars owned by this user.
     *
     * @return HasMany HasMany relationship instance to ElectricCar model.
     */
    public function electricCars(): HasMany
    {
        return $this->hasMany(ElectricCar::class, 'owner_id');
    }

    /**
     * Get the one-to-many relationship to all garage rental transactions made by this user.
     *
     * @return HasMany HasMany relationship instance to Rent model.
     */
    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class, 'renter_id');
    }
}
