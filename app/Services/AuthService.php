<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Authenticate a user by email and password.
     *
     * @param array $data Validated user login credentials containing 'email' and 'password'.
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
     * @return User Newly created User model instance.
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

    /**
     * Get user listing and summary verification metrics for admin verification.
     *
     * @param int $perPage Number of user items per page.
     * @return array{
     *     users: \Illuminate\Contracts\Pagination\LengthAwarePaginator,
     *     totalUsers: int,
     *     verifiedUsers: int,
     *     unverifiedUsers: int
     * } Array containing paginated users and verification metric counts.
     */
    public function getUserIndexData(int $perPage = 10): array
    {
        $baseQuery = User::where('role', 'user');
        $totalUsers = (clone $baseQuery)->count();
        $verifiedUsers = (clone $baseQuery)->where('is_verified', true)->count();
        $unverifiedUsers = (clone $baseQuery)->where('is_verified', false)->count();

        $users = $baseQuery->latest()->paginate($perPage);

        return compact('users', 'totalUsers', 'verifiedUsers', 'unverifiedUsers');
    }

    /**
     * Toggle manual verification status for a user.
     *
     * @param int $id Target User ID to toggle verification.
     * @return User Updated User model instance.
     */
    public function toggleVerify(int $id): User
    {
        $user = User::findOrFail($id);
        $user->is_verified = !$user->is_verified;
        $user->save();

        return $user;
    }
}
