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
            ->with('success', 'Registrasi berhasil!');
    }

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

    public function logout()
    {
        session()->invalidate();
        session()->regenerateToken();

        return redirect()
            ->route('welcome')
            ->with('success', 'Logout berhasil!');
    }
}