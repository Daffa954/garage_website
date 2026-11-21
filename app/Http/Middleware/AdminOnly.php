<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = session('user_id');

        $user = User::find($userId);

        if (!$user || $user->role !== "admin") {
            session()->invalidate();
            session()->regenerateToken();
            return redirect()->route('welcome')
                ->with('error', 'This route is for admin only');
        }

        return $next($request);
    }
}
