<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAccesoSync
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 10, int $decayMinutes = 1): Response
    {
        $acceso = $request->attributes->get('acceso_company');

        if (!$acceso) {
            return $next($request);
        }

        $key = "acceso_sync_throttle:{$acceso->id}";
        $window = $this->getWindow($key, $decayMinutes);

        if ($window['attempts'] >= $maxAttempts) {
            $remaining = $window['expires_at'] - now()->timestamp;

            return response()->json([
                'success' => false,
                'message' => "Demasiadas solicitudes. Límite: {$maxAttempts} por {$decayMinutes} minuto(s)",
                'error' => 'rate_limited',
                'data' => [
                    'retry_after_seconds' => max(0, $remaining),
                ]
            ], 429);
        }

        $this->incrementAttempts($key, $window, $decayMinutes);

        return $next($request);
    }

    private function getWindow(string $key, int $decayMinutes): array
    {
        $data = Cache::get($key);

        if (!$data || !isset($data['expires_at']) || $data['expires_at'] <= now()->timestamp) {
            return [
                'attempts' => 0,
                'expires_at' => now()->timestamp + ($decayMinutes * 60),
            ];
        }

        return $data;
    }

    private function incrementAttempts(string $key, array $window, int $decayMinutes): void
    {
        $ttl = max(0, $window['expires_at'] - now()->timestamp);

        if ($ttl <= 0) {
            $window['expires_at'] = now()->timestamp + ($decayMinutes * 60);
            $window['attempts'] = 1;
        } else {
            $window['attempts']++;
        }

        Cache::put($key, $window, $ttl > 0 ? $ttl : $decayMinutes * 60);
    }
}