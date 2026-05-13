<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSyncApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API token required',
                'error' => 'token_missing'
            ], 401);
        }

        // Obtener token de configuración
        $validTokens = config('sync.api_tokens', []);

        // Token por defecto desde .env
        $envToken = env('SYNC_API_TOKEN');

        if ($envToken) {
            $validTokens[] = $envToken;
        }

        if (empty($validTokens)) {
            Log::warning('SYNC_API_TOKEN not configured');
            return response()->json([
                'success' => false,
                'message' => 'Sync API not configured',
                'error' => 'configuration_error'
            ], 500);
        }

        if (!in_array($token, $validTokens)) {
            Log::warning('Invalid sync API token attempt', [
                'ip' => $request->ip(),
                'token_prefix' => substr($token, 0, 8) . '...'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API token',
                'error' => 'token_invalid'
            ], 401);
        }

        // Token válido, agregar marca a request para logging
        $request->attributes->set('sync_api_authenticated', true);

        return $next($request);
    }
}
