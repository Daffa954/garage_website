<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Authenticate a user by email and password.
     *
     * @param array $data Contains 'email' and 'password'.
     * @return User|null The authenticated User instance or null on failure.
     */
    public function login(array $data): ?User
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * Register a new user in the system.
     *
     * @param array $data Validated user registration attributes.
     * @return User
     */
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'nik' => $data['nik'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
