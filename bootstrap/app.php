<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS middleware for API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiCors::class,
        ]);

        // Add token validation middleware for API routes
        $middleware->api(append: [
            \App\Http\Middleware\EnsureTokenIsValid::class,
        ]);

        // Temporarily exclude login and register from CSRF protection for testing
        $middleware->validateCsrfTokens(except: [
            'login',
            'register',
            'test-openai/*',  // Exclude OpenAI test endpoints (development only)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
