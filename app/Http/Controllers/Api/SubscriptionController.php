<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Obtener todas las suscripciones del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $subscriptions = $user->subscriptions()->with('company')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'active_count' => $user->subscriptions()->active()->count(),
        ]);
    }

    /**
     * Obtener suscripción activa actual
     */
    public function active(Request $request)
    {
        $user = auth()->user();
        $companyId = $request->query('company_id');

        $subscription = $user->activeSubscription($companyId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }

    /**
     * Crear nueva suscripción
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan' => 'required|in:trial,monthly,annual,lifetime',
            'company_id' => 'nullable|integer|exists:companies,id',
            'payment_method' => 'nullable|string|max:50',
            'payment_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $plan = $request->plan;

        // Obtener configuración del plan
        $planConfig = Subscription::getPlanConfig($plan);

        // Si es company role, usar su primera compañía si no se especifica
        $companyId = $request->company_id;
        if (!$companyId && $user->role === User::ROLE_COMPANY) {
            $companyId = $user->companies->first()?->id;
        }

        // Verificar si ya tiene una suscripción activa para esta compañía
        $existingSubscription = $user->activeSubscription($companyId);
        if ($existingSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'User already has an active subscription for this company',
                'data' => [
                    'current_subscription' => $existingSubscription,
                ]
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Calcular fecha de expiración
            $expiresAt = $planConfig['duration_days']
                ? now()->addDays($planConfig['duration_days'])
                : null;

            // Crear suscripción
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'plan' => $plan,
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'status' => Subscription::STATUS_ACTIVE,
                'features' => $planConfig['features'],
                'payment_method' => $request->payment_method,
                'payment_id' => $request->payment_id,
                'amount' => $planConfig['price'],
                'currency' => 'USD',
                'notes' => $request->notes,
            ]);

            DB::commit();

            Log::info('Subscription created', [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'plan' => $plan,
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'data' => $subscription->load('company'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating subscription', [
                'user_id' => $user->id,
                'plan' => $plan,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar una suscripción específica
     */
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $subscription = $user->subscriptions()->with('company')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }

    /**
     * Actualizar suscripción (upgrade/downgrade)
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'plan' => 'required|in:monthly,annual,lifetime',
            'payment_method' => 'nullable|string|max:50',
            'payment_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $subscription = $user->subscriptions()->findOrFail($id);
        $newPlan = $request->plan;

        // Obtener configuración del nuevo plan
        $planConfig = Subscription::getPlanConfig($newPlan);

        try {
            DB::beginTransaction();

            // Calcular nueva fecha de expiración
            $expiresAt = $planConfig['duration_days']
                ? now()->addDays($planConfig['duration_days'])
                : null;

            // Actualizar suscripción
            $subscription->update([
                'plan' => $newPlan,
                'expires_at' => $expiresAt,
                'features' => $planConfig['features'],
                'status' => Subscription::STATUS_ACTIVE,
                'payment_method' => $request->payment_method ?? $subscription->payment_method,
                'payment_id' => $request->payment_id ?? $subscription->payment_id,
                'amount' => $planConfig['price'],
                'notes' => $request->notes ?? $subscription->notes,
            ]);

            DB::commit();

            Log::info('Subscription updated', [
                'subscription_id' => $subscription->id,
                'old_plan' => $subscription->plan,
                'new_plan' => $newPlan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully',
                'data' => $subscription->fresh()->load('company'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating subscription', [
                'subscription_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extender suscripción (agregar días)
     */
    public function extend(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'required|integer|min:1|max:3650',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $subscription = $user->subscriptions()->findOrFail($id);
        $days = $request->days;

        try {
            $subscription->extend($days);

            Log::info('Subscription extended', [
                'subscription_id' => $id,
                'days' => $days,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Subscription extended by {$days} days",
                'data' => $subscription->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error extending subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(Request $request, $id)
    {
        $user = auth()->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        try {
            $subscription->cancel();

            Log::info('Subscription cancelled', [
                'subscription_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'data' => $subscription->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reactivar suscripción
     */
    public function reactivate(Request $request, $id)
    {
        $user = auth()->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        try {
            $subscription->reactivate();

            Log::info('Subscription reactivated', [
                'subscription_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription reactivated successfully',
                'data' => $subscription->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reactivating subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener planes disponibles
     */
    public function plans()
    {
        $plans = Subscription::getPlanConfig();

        return response()->json([
            'success' => true,
            'data' => array_map(function ($plan, $key) {
                return [
                    'key' => $key,
                    'name' => $plan['name'],
                    'duration_days' => $plan['duration_days'],
                    'price' => $plan['price'],
                    'features' => $plan['features'],
                ];
            }, $plans, array_keys($plans)),
        ]);
    }
}
