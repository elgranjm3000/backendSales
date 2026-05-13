<?php

namespace App\Http\Middleware;

use App\Models\Acceso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAccesoToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API key requerida. Envíe el token en el header Authorization: Bearer <api_key>',
                'error' => 'token_missing'
            ], 401);
        }

        $acceso = Acceso::where('api_key', $token)
            ->where(function ($q) {
                $q->whereNull('api_key_expires_at')
                  ->orWhere('api_key_expires_at', '>', now());
            })
            ->first();

        if (!$acceso) {
            Log::warning('Intento de sync con API key inválida', [
                'ip' => $request->ip(),
                'token_prefix' => substr($token, 0, 12) . '...'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API key inválida o expirada',
                'error' => 'token_invalid'
            ], 401);
        }

        // Verificar si la empresa está bloqueada
        if ($acceso->isBlocked()) {
            Log::warning('Sync bloqueado: empresa suspendida', [
                'acceso_id' => $acceso->id,
                'codigo' => $acceso->codigo,
                'nombre' => $acceso->nombre,
                'blocked_at' => $acceso->blocked_at,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Acceso suspendido. Contacte a su proveedor para reactivar el servicio.',
                'error' => 'access_blocked'
            ], 403);
        }

        // Dejar la empresa disponible para el resto del request
        $request->attributes->set('acceso_company', $acceso);
        $request->attributes->set('acceso_company_id', $acceso->id);

        // Log opcional de actividad
        Log::info('Sync autenticado', [
            'acceso_id' => $acceso->id,
            'codigo' => $acceso->codigo,
            'nombre' => $acceso->nombre,
        ]);

        return $next($request);
    }
}
