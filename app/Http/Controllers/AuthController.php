<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Handle an incoming user login request.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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
            ->route('dashboard')
            ->with('success', 'Login Successful!');
    }

    /**
     * Handle a new user registration request.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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
            ->route('dashboard')
            ->with('success', 'Registration successful!');
    }

    /**
     * Log the authenticated user out by clearing the session.
     *
     * @return \Illuminate\Http\RedirectResponse
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
     * @param User $user
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