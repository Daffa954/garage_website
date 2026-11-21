<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class IsUserVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = session('user_id');

        $user = User::find($userId);

        if (!$user || !$user->is_verified) {
            return redirect()->route('welcome')
                ->with('error', 'Your account is not verified');
        }

        return $next($request);
    }
}