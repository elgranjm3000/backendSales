<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActiveSession;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $currentToken = $request->user()->currentAccessToken();
            
            if ($currentToken) {
                $activeSession = ActiveSession::where('user_id', $request->user()->id)
                                            ->where('token_id', $currentToken->id)
                                            ->first();

                if (!$activeSession || !$activeSession->isActive()) {
                    $currentToken->delete();
                    
                    if ($activeSession) {
                        $activeSession->delete();
                    }

                    return response()->json([
                        'success' => false,
                        'code' => 'SESSION_EXPIRED',
                        'message' => 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.'
                    ], 401);
                }

                $activeSession->updateActivity();
            }
        }

        return $next($request);
    }
}