<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    /**
     * Display a listing of registered users for admin verification.
     */
    public function index()
    {
        $data = $this->userService->getUserIndexData();

        return view('admin.users', $data);
    }

    /**
     * Toggle manual verification status for a user.
     */
    public function toggleVerify(int $id)
    {
        $user = $this->userService->toggleVerify($id);

        $status = $user->is_verified ? 'verified' : 'unverified';

        return redirect()->back()->with('success', "User {$user->name} has been successfully {$status}.");
    }
}
