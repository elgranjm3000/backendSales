<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckManagerRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || $user->role->value !== 'manager') {
            abort(403, 'Acceso denegado. Solo usuarios con rol de manager pueden acceder.');
        }

        return $next($request);
    }
}