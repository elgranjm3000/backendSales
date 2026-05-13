<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCajeroRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || $user->role->value !== 'admin') {
            abort(403, 'Acceso denegado. Solo usuarios con rol de admin pueden acceder.');
        }

        return $next($request);
    }
}
