<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserLoggedIn
{
    /**
     * Handle an incoming request and ensure user is logged in via session.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('user_id')) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated. Please log in to continue.',
                ], 401);
            }

            return redirect()->route('welcome')
                ->with('error', 'Please log in to continue.');
        }

        return $next($request);
    }
}
