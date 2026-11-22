<?php

use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\EnsureUserLoggedIn;
use App\Http\Middleware\IsUserVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.session' => EnsureUserLoggedIn::class,
            'verified.user' => IsUserVerified::class,
            'admin.only' => AdminOnly::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\RuntimeException $e, Request $request) {
            if (!$request->expectsJson()) {
                return back()->with('error', $e->getMessage())->withInput();
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->expectsJson(),
        );
    })->create();
