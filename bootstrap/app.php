<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api', // Asegurar que el prefijo API esté configurado
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // No usar middleware statefulApi que causa redirects
        // El middleware auth:sanctum se maneja directamente en las rutas

        $middleware->alias([
            'check.active.session' => \App\Http\Middleware\CheckActiveSession::class,
            'auth.api' => \App\Http\Middleware\HandleApiAuth::class,
            'throttle.sync' => \App\Http\Middleware\ThrottleSyncRequests::class,
            'auth.sync.api' => \App\Http\Middleware\AuthenticateSyncApiToken::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'auth.acceso' => \App\Http\Middleware\AuthenticateAccesoToken::class,
            'throttle.acceso' => \App\Http\Middleware\ThrottleAccesoSync::class,
            'admin' => \App\Http\Middleware\CheckCajeroRole::class,
            'manager' => \App\Http\Middleware\CheckManagerRole::class,
            'admin.or.manager' => \App\Http\Middleware\CheckAdminOrManagerRole::class,
        ]);

        // IMPORTANTE: Excluir rutas API del CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',
            '*/api/*',
            'sales-apiWEB/public/api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autenticado. Se requiere un token válido.',
                        'error' => 'unauthenticated'
                    ], 401);
                }
            }
        });
    })->create();
