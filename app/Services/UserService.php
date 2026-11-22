<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * Get user listing and summary verification metrics for admin.
     *
     * @param int $perPage
     * @return array{
     *     users: \Illuminate\Contracts\Pagination\LengthAwarePaginator,
     *     totalUsers: int,
     *     verifiedUsers: int,
     *     unverifiedUsers: int
     * }
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
     * Toggle verification status for a user.
     *
     * @param int $id
     * @return User
     */
    public function toggleVerify(int $id): User
    {
        $user = User::findOrFail($id);
        $user->is_verified = !$user->is_verified;
        $user->save();

        return $user;
    }
}
