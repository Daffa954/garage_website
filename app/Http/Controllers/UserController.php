<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of registered users for admin verification.
     */
    public function index()
    {
        $baseQuery = User::where('role', 'user');
        $totalUsers = (clone $baseQuery)->count();
        $verifiedUsers = (clone $baseQuery)->where('is_verified', true)->count();
        $unverifiedUsers = (clone $baseQuery)->where('is_verified', false)->count();

        $users = $baseQuery->latest()->paginate(10);

        return view('admin.users', compact('users', 'totalUsers', 'verifiedUsers', 'unverifiedUsers'));
    }

    /**
     * Toggle manual verification status for a user.
     */
    public function toggleVerify(int $id)
    {
        $user = User::findOrFail($id);
        $user->is_verified = !$user->is_verified;
        $user->save();

        $status = $user->is_verified ? 'verified' : 'unverified';

        return redirect()->back()->with('success', "User {$user->name} has been successfully {$status}.");
    }
}
