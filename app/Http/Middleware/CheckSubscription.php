<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $feature
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'auth_required'
            ], 401);
        }

        // Obtener company_id del request o de la primera compañía del usuario
        $companyId = $request->input('company_id')
                    ?? $request->route('company_id')
                    ?? optional($user->companies->first())->id;

        // Buscar suscripción activa
        $subscription = $user->activeSubscription($companyId);

        // Verificar si tiene suscripción activa
        if (!$subscription) {
            $message = $user->subscriptions()->exists()
                ? 'No active subscription found. Your subscription may have expired.'
                : 'No subscription found. Please choose a plan to continue.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => 'no_subscription',
                'data' => [
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'has_any_subscription' => $user->subscriptions()->exists(),
                ]
            ], 403);
        }

        // Verificar si la suscripción está activa y no expiró
        if (!$subscription->isActive()) {
            if ($subscription->isExpired()) {
                // Actualizar estado a expired si lo está
                if ($subscription->status === 'active') {
                    $subscription->markAsExpired();
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Your subscription has expired',
                    'error' => 'subscription_expired',
                    'data' => [
                        'plan' => $subscription->plan,
                        'plan_name' => $subscription->plan_name,
                        'expired_at' => $subscription->expires_at?->toIso8601String(),
                        'days_since_expiry' => now()->diffInDays($subscription->expires_at),
                    ]
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'Subscription is not active',
                'error' => 'subscription_inactive',
                'data' => [
                    'status' => $subscription->status,
                ]
            ], 403);
        }

        // Verificar feature específica si se solicita
        if ($feature && !$subscription->hasFeature($feature)) {
            $planConfig = \App\Models\Subscription::getPlanConfig();

            // Buscar el plan más bajo que tiene esta feature
            $requiredPlan = null;
            foreach ($planConfig as $planKey => $config) {
                if (!empty($config['features'][$feature])) {
                    $requiredPlan = $planKey;
                    break;
                }
            }

            return response()->json([
                'success' => false,
                'message' => "Feature '{$feature}' is not available in your current plan ({$subscription->plan_name})",
                'error' => 'feature_not_available',
                'data' => [
                    'requested_feature' => $feature,
                    'current_plan' => $subscription->plan,
                    'current_plan_name' => $subscription->plan_name,
                    'required_plan' => $requiredPlan,
                    'required_plan_name' => $requiredPlan ? $planConfig[$requiredPlan]['name'] : 'N/A',
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                    'days_remaining' => $subscription->days_remaining,
                ]
            ], 403);
        }

        // Adjuntar suscripción al request para uso posterior
        $request->attributes->set('subscription', $subscription);

        // Log de acceso exitoso (opcional, para analytics)
        Log::debug('Subscription access granted', [
            'user_id' => $user->id,
            'company_id' => $companyId,
            'plan' => $subscription->plan,
            'feature' => $feature ?? 'all',
            'days_remaining' => $subscription->days_remaining,
        ]);

        return $next($request);
    }
}
