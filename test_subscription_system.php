<?php

/**
 * Script para probar el sistema de suscripciones
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SyncDataController;

echo "=== PRUEBAS DEL SISTEMA DE SUSCRIPCIONES ===\n\n";

// 1. Obtener usuario y suscripción
$user = \App\Models\User::where('email', 'admin@test.com')->first();
if (!$user) {
    die("❌ Usuario admin@test.com no encontrado\n");
}

$subscription = $user->activeSubscription();
$company = $user->companies->first();

echo "👤 Usuario: {$user->email}\n";
echo "🏢 Compañía: {$company->name} (ID: {$company->id})\n";
echo "📦 Plan: {$subscription->plan_name} (expira en {$subscription->days_remaining} días)\n";
echo "\n";

// Crear token
$tokenResult = $user->createToken('test-subscription', ['*'], $subscription->expires_at);
$token = $tokenResult->plainTextToken;
echo "🔑 Token creado: " . substr($token, 0, 50) . "...\n\n";

// 2. Crear una solicitud simulada
function makeRequest($method, $endpoint, $data = [])
{
    $request = Request::create($endpoint, $method, [], [], [], [],
        json_encode($data));
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Authorization', 'Bearer ' . func_get_arg(3));
    return $request;
}

$tests = [
    [
        'name' => 'GET /api/products (CRUD normal - solo auth)',
        'method' => 'GET',
        'endpoint' => '/api/products',
        'expected' => '✅ Debería funcionar (solo requiere auth:sanctum)',
    ],
    [
        'name' => 'POST /api/products (CRUD normal - solo auth)',
        'method' => 'POST',
        'endpoint' => '/api/products',
        'expected' => '✅ Debería funcionar (solo requiere auth:sanctum)',
    ],
    [
        'name' => 'GET /api/sync-data/products (con suscripción)',
        'method' => 'GET',
        'endpoint' => '/api/sync-data/products?company_id=1',
        'expected' => '✅ Debería funcionar (requiere suscripción activa)',
    ],
    [
        'name' => 'POST /api/sync-data/products (con feature sync_products)',
        'method' => 'POST',
        'endpoint' => '/api/sync-data/products',
        'data' => ['company_id' => $company->id, 'products' => []],
        'expected' => '✅ Debería funcionar (TRIAL tiene sync_products)',
    ],
    [
        'name' => 'POST /api/sync-data/quotes (sin feature sync_quotes)',
        'method' => 'POST',
        'endpoint' => '/api/sync-data/quotes',
        'data' => ['company_id' => $company->id, 'quotes' => []],
        'expected' => '❌ Debería fallar 403 (TRIAL NO tiene sync_quotes)',
    ],
];

foreach ($tests as $i => $test) {
    echo "TEST " . ($i + 1) . ": {$test['name']}\n";
    echo "Esperado: {$test['expected']}\n";

    try {
        $request = makeRequest(
            $test['method'],
            $test['endpoint'],
            $test['data'] ?? [],
            $token
        );

        // Procesar la ruta
        try {
            $response = app()->handle($request);
            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getContent(), true);

            echo "Resultado: HTTP {$statusCode}\n";

            if ($statusCode === 200) {
                echo "✅ EXITOSO\n";
            } elseif ($statusCode === 403) {
                echo "❌ PROHIBIDO: " . ($content['message'] ?? 'Unknown error') . "\n";
                if (isset($content['data']['current_plan'])) {
                    echo "   Plan actual: {$content['data']['current_plan']}\n";
                    echo "   Plan requerido: {$content['data']['required_plan']}\n";
                }
            } elseif ($statusCode === 401) {
                echo "❌ NO AUTENTICADO\n";
            } else {
                echo "⚠️  Otro resultado\n";
            }
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error creando request: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("-", 60) . "\n\n";
}

// 3. Resumen de features disponibles
echo "=== RESUMEN DE FEATURES DEL PLAN TRIAL ===\n";
foreach ($subscription->features as $feature => $enabled) {
    echo sprintf("%s %s\n", $enabled ? '✅' : '❌', $feature);
}

echo "\n✅ Pruebas completadas\n";

// Limpiar
$user->tokens()->where('name', 'test-subscription')->delete();
echo "🧹 Token de prueba eliminado\n";
