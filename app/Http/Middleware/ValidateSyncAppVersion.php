<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Acceso;
use App\Models\Company;
use App\Models\SyncAppVersion;
use Closure;
use Illuminate\Http\Request;

class ValidateSyncAppVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Validar versión mobile
        $appVersion = $request->header('X-App-Version');
        $appType = $request->header('X-App-Type', 'mobile');

        $appTypeChrystal = $request->header('X-App-Type-Chrystal', 'chrystal');
        $appVersionChrystal = $request->header('X-App-Version-Chrystal');

        $appUid = $request->header('X-Device-UUID');
        $ApiKey = $request->header('X-App-ApiKey');

        if (!$appUid) {
            return response()->json([
                'error' => 'UUID no especificado',
                'message' => 'El header X-Device-UUID es requerido para sincronizar.'
            ], 400);
        }
        if (!$ApiKey) {
            return response()->json([
                'error' => 'API Key no especificada',
                'message' => 'El header X-App-ApiKey es requerido para sincronizar.'
            ], 400);
        }

        if (!$appVersionChrystal) {
            return response()->json([
                'error' => 'Versión no especificada',
                'message' => 'El header X-App-Version-Chrystal es requerido para sincronizar.'
            ], 400);
        }

        if (!$appVersion) {
            return response()->json([
                'error' => 'Versión no especificada',
                'message' => 'El header X-App-Version es requerido para sincronizar.'
            ], 400);
        }

        $versionValidateChrystal = SyncAppVersion::where('version', $appVersionChrystal)
            ->where('typeapp', $appTypeChrystal)
            ->first();

        $version = SyncAppVersion::where('version', $appVersion)
            ->where('typeapp', $appType)
            ->first();

        $acceso = Acceso::where('api_key', $ApiKey)->first();

        if ($acceso) {
            $company = $acceso->company;

            if ($company && $company->uuid_hard_drive && $company->uuid_hard_drive !== $appUid) {
                return response()->json([
                    'error' => 'UUID no coincide',
                    'message' => 'El UUID del dispositivo no coincide con el registrado para esta empresa.',
                ], 403);
            }
        }

        if (!$versionValidateChrystal) {
            return response()->json([
                'error' => 'Versión no registrada',
                'message' => "La versión {$appVersionChrystal} para el tipo {$appTypeChrystal} no existe en el sistema.",
            ], 403);
        }
        if ($versionValidateChrystal->status !== 'active') {
            return response()->json([
                'error' => 'Versión inhabilitada',
                'message' => "La versión {$appVersionChrystal} está inhabilitada para sincronizar.",
            ], 403);
        }

        if (!$version) {
            return response()->json([
                'error' => 'Versión no registrada',
                'message' => "La versión {$appVersion} para el tipo {$appType} no existe en el sistema.",
            ], 403);
        }

        if ($version->status !== 'active') {
            return response()->json([
                'error' => 'Versión inhabilitada',
                'message' => "La versión {$appVersion} está inhabilitada para sincronizar.",
            ], 403);
        }

        return $next($request);
    }
}
