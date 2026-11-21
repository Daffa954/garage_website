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
        $users = User::where('role', 'user')->latest()->get();

        return view('admin.users', compact('users'));
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
