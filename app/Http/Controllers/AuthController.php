<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\AuthService;

class AuthController extends Controller
{
    /**
     * AuthController constructor.
     *
     * @param AuthService $authService Service handling authentication and user verification operations.
     */
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Handle an incoming user login request.
     *
     * @param Request $request Incoming HTTP request containing 'email' and 'password'.
     * @return \Illuminate\Http\RedirectResponse Redirect response to garages index on success, or back with errors.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = $this->authService->login($data);

        if (!$user) {
            return back()
                ->withErrors([
                    'email' => 'Email or password wrong.',
                ])
                ->onlyInput('email');
        }

        $this->createSession($user);

        return redirect()
            ->route('garages.index')
            ->with('success', 'Login Successful!');
    }

    /**
     * Handle a new user registration request.
     *
     * @param Request $request Incoming HTTP request containing user registration fields.
     * @return \Illuminate\Http\RedirectResponse Redirect response to garages index with success flash.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nik' => 'required|string|min:16|max:16',
            'phone' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->authService->register($data);

        $this->createSession($user);

        return redirect()
            ->route('garages.index')
            ->with('success', 'Registration successful!');
    }

    /**
     * Display a listing of registered users for admin verification.
     *
     * @return \Illuminate\View\View Admin users verification view with metrics and user list.
     */
    public function users()
    {
        $data = $this->authService->getUserIndexData();

        return view('users', $data);
    }

    /**
     * Toggle manual verification status for a user (Admin only).
     *
     * @param int $id Target User ID to verify or unverify.
     * @return \Illuminate\Http\RedirectResponse Redirect back with success flash message.
     */
    public function toggleVerify(int $id)
    {
        $user = $this->authService->toggleVerify($id);

        $status = $user->is_verified ? 'verified' : 'unverified';

        return redirect()->back()->with('success', "User {$user->name} has been successfully {$status}.");
    }

    /**
     * Log the authenticated user out by clearing and regenerating the session.
     *
     * @return \Illuminate\Http\RedirectResponse Redirect response to welcome page.
     */
    public function logout()
    {
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()
            ->route('welcome')
            ->with('success', 'Logout Successful!');
    }

    /**
     * Establish session data for the authenticated user.
     *
     * @param User $user Authenticated user model instance.
     * @return void
     */
    private function createSession(User $user): void
    {
        session()->regenerate();

        session([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'user_email' => $user->email,
            'user_phone' => $user->phone
        ]);
    }
}
