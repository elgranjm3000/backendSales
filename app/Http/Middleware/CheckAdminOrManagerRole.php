<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminOrManagerRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role->value, ['admin', 'manager'])) {
            abort(403, 'Acceso denegado. Solo usuarios con rol de admin o manager pueden acceder.');
        }

        return $next($request);
    }
}
