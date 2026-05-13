<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class HandleApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extraer token del header Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado',
                'error' => 'token_missing'
            ], 401);
        }

        // Verificar token con Sanctum
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado',
                'error' => 'token_invalid'
            ], 401);
        }

        // Verificar si el token ha expirado
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado',
                'error' => 'token_expired'
            ], 401);
        }

        // Autenticar al usuario
        $user = $accessToken->tokenable;
        auth()->setUser($user);

        return $next($request);
    }
}
